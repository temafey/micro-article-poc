# Performance Test Scripts

k6 performance test scripts and shell utilities for load, soak, stress testing, and runtime comparison across PHP runtimes.

## Prerequisites

- [k6](https://k6.io/docs/getting-started/installation/) installed locally, or Docker with `grafana/k6:latest` image
- [wrk](https://github.com/wg/wrk) for quick baseline testing
- `jq` and `bc` for JSON processing and calculations
- `curl` for endpoint verification

## Quick Start

```bash
# Quick baseline test with wrk (30 seconds)
./scripts/perf/wrk-baseline.sh

# Run k6 load test (5 minutes)
k6 run scripts/perf/k6-load-test.js

# Compare all runtimes
./scripts/perf/perf-compare.sh all quick

# Run against specific runtime
TARGET_URL=http://localhost:8080 k6 run scripts/perf/k6-load-test.js

# Run with JSON output for CI
k6 run --out json=results.json scripts/perf/k6-load-test.js
```

## Available Scripts

### k6 Test Scripts

| Script | Duration | Purpose | Peak VUs |
|--------|----------|---------|----------|
| `k6-smoke-test.js` | 30s | Quick CI/PR validation | 5 |
| `k6-load-test.js` | 5 min | Normal to moderate load | 100 |
| `k6-soak-test.js` | 30 min | Memory leak detection | 30 |
| `k6-stress-test.js` | 10 min | Extreme load and recovery | 300 |

### Shell Utility Scripts

| Script | Purpose | Description |
|--------|---------|-------------|
| `wrk-baseline.sh` | Quick baseline | Fast HTTP benchmarking with wrk (30s default) |
| `perf-compare.sh` | Runtime comparison | Compare performance across PHP-FPM, RoadRunner, FrankenPHP |
| `perf-baseline.sh` | Baseline management | Save, load, compare baselines for regression detection |

## Runtime Configuration

Set `TARGET_URL` environment variable to test different PHP runtimes:

| Runtime | URL | Command |
|---------|-----|---------|
| RoadRunner | `http://localhost:8080` | `TARGET_URL=http://localhost:8080 k6 run ...` |
| PHP-FPM | `http://localhost:80` | `TARGET_URL=http://localhost:80 k6 run ...` |
| FrankenPHP | `http://localhost:8081` | `TARGET_URL=http://localhost:8081 k6 run ...` |

Default: `http://localhost:8080` (RoadRunner)

## Test Descriptions

### Load Test (`k6-load-test.js`)

5-minute test simulating normal to moderate traffic:

- **Stages**: 0→50 VUs (1m), hold 50 (2m), 50→100 VUs (1m), hold 100 (1m)
- **Thresholds**: p95 latency < 200ms, failure rate < 1%
- **Endpoints**: `/health`, `/api/v1/article/`

### Soak Test (`k6-soak-test.js`)

30-minute sustained load for stability testing:

- **Stages**: Ramp to 30 VUs (1m), hold 30 VUs (28m), ramp down (1m)
- **Purpose**: Detect memory leaks, connection pool exhaustion
- **Metrics**: Iteration timing consistency, error rate stability

Run alongside memory monitoring:
```bash
docker stats --format "{{.Name}}: {{.MemUsage}}"
```

### Stress Test (`k6-stress-test.js`)

10-minute extreme load with recovery testing:

- **Pattern**: Warm up → Stress spike → Extreme (300 VUs) → Recovery
- **Thresholds**: Relaxed (10% failure allowed under extreme stress)
- **Purpose**: Find breaking points, test recovery capability

## Output Options

```bash
# Console output (default)
k6 run scripts/perf/k6-load-test.js

# JSON output for parsing
k6 run --out json=results.json scripts/perf/k6-load-test.js

# InfluxDB output
k6 run --out influxdb=http://localhost:8086/k6 scripts/perf/k6-load-test.js
```

## Verification

```bash
# Validate script syntax
k6 inspect scripts/perf/k6-load-test.js

# Quick smoke test (1 VU, 10 seconds)
k6 run --vus 1 --duration 10s scripts/perf/k6-load-test.js
```

## Shell Scripts Usage

### wrk-baseline.sh

Quick HTTP baseline testing using wrk for fast benchmarking:

```bash
# Test default (RoadRunner on port 8080)
./scripts/perf/wrk-baseline.sh

# Test PHP-FPM
TARGET_URL=http://localhost:80 ./scripts/perf/wrk-baseline.sh

# Test FrankenPHP with JSON output
TARGET_URL=http://localhost:8081 ./scripts/perf/wrk-baseline.sh --json

# Custom parameters
./scripts/perf/wrk-baseline.sh -t 2 -c 50 -d 10s
```

**Environment Variables:**
- `TARGET_URL` - Target URL (default: http://localhost:8080)
- `WRK_THREADS` - Thread count (default: 4)
- `WRK_CONNECTIONS` - Connection count (default: 100)
- `WRK_DURATION` - Test duration (default: 30s)

### perf-compare.sh

Compare performance across all PHP runtimes:

```bash
# Quick comparison with wrk (30s per runtime)
./scripts/perf/perf-compare.sh all quick

# Full load test comparison (5 min per runtime)
./scripts/perf/perf-compare.sh all load

# Test specific runtime
./scripts/perf/perf-compare.sh rr load

# Generate comparison report
./scripts/perf/perf-compare.sh all quick --report
```

**Test Types:**
- `quick` - wrk baseline (30s)
- `load` - k6 load test (5 min)
- `soak` - k6 soak test (30 min)
- `stress` - k6 stress test (10 min)

**Runtimes:**
- `fpm` - PHP-FPM (port 80)
- `rr` - RoadRunner (port 8080)
- `frank` - FrankenPHP (port 8081)
- `all` - Test all runtimes

### perf-baseline.sh

Baseline management for regression detection:

```bash
# Save baseline for RoadRunner
./scripts/perf/perf-baseline.sh save rr

# Compare current results against baseline
./scripts/perf/perf-baseline.sh compare rr

# CI mode - exit with error on regression
./scripts/perf/perf-baseline.sh compare rr --ci

# List all saved baselines
./scripts/perf/perf-baseline.sh list

# Save all baselines (force overwrite)
./scripts/perf/perf-baseline.sh save all --force
```

**Regression Thresholds:**
- Latency increase > 10% = REGRESSION
- Throughput decrease > 10% = REGRESSION
- Failure rate increase > 50% = REGRESSION

**Exit Codes:**
- `0` - Success / All metrics OK
- `1` - Regression detected
- `2` - Error (missing files, invalid data)

## Output Locations

| Type | Path | Description |
|------|------|-------------|
| Results | `tests/performance/results/` | Raw test results (gitignored) |
| Baselines | `tests/performance/baselines/` | Saved baselines (version controlled) |

## Smoke Test (`k6-smoke-test.js`)

Quick 30-second CI validation test designed for PR checks:

- **VUs**: 5 concurrent users
- **Duration**: 30 seconds
- **Purpose**: Fast validation without heavy load
- **Thresholds**:
  - p95 latency < 500ms
  - Failure rate < 5%
  - Health endpoint p95 < 200ms
  - Article API p95 < 1000ms

```bash
# Run smoke test
k6 run scripts/perf/k6-smoke-test.js

# With specific runtime
TARGET_URL=http://localhost:8081 k6 run scripts/perf/k6-smoke-test.js
```

## GitHub Actions CI Integration

Automated performance testing via `.github/workflows/performance.yml`:

### Triggers

| Trigger | Condition | Test Type |
|---------|-----------|-----------|
| Push to main | src/, config/, scripts/perf/ changes | Smoke test |
| Manual dispatch | workflow_dispatch | Configurable |
| Weekly schedule | Sundays 2AM UTC | Full comparison |

### Available Jobs

| Job | Duration | Purpose |
|-----|----------|---------|
| `smoke-test` | ~2 min | Quick PR validation (5 VUs, 30s) |
| `load-test` | ~10 min | Standard load test (100 VUs, 5m) |
| `runtime-comparison` | ~30 min | Matrix: fpm, rr, frank |
| `comparison-report` | ~2 min | Generate comparison summary |
| `extended-test` | ~45 min | Weekly soak/stress tests |

### Manual Workflow Dispatch

```bash
# Via GitHub CLI
gh workflow run performance.yml \
  --field test_type=load \
  --field runtime=rr \
  --field baseline_action=compare
```

**Dispatch Options:**
- `test_type`: smoke, load, stress, soak, comparison
- `runtime`: rr, fpm, frank, all
- `baseline_action`: compare, save, none

### Artifacts

| Artifact | Retention | Contents |
|----------|-----------|----------|
| smoke-test-results | 7 days | JSON summary |
| load-test-results | 14 days | Full k6 output |
| runtime-comparison-results | 30 days | All runtime results |
| extended-test-results | 30 days | Soak/stress results |

## Related Documentation

- [ADR-012: Performance Testing Strategy](../../docs/adr/ADR-012-performance-testing-strategy.md)
- [Performance Testing Guide](../../docs/testing/PERFORMANCE-TESTING.md)
- [GitHub Actions Workflow](../../.github/workflows/performance.yml)
- [k6 Documentation](https://k6.io/docs/)
- [wrk Documentation](https://github.com/wg/wrk)
