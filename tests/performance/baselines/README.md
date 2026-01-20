# Performance Baselines

Last updated: 2025-12-29T00:18:46Z

## Baseline Summary

| Runtime | p95 Latency | Warning (×1.10) | Critical (×1.25) | RPS |
|---------|-------------|-----------------|------------------|-----|
| **RoadRunner** | 3.48ms | 3.83ms | 4.35ms | 20.03 |
| PHP-FPM | 17.88ms | 19.67ms | 22.35ms | 19.03 |
| FrankenPHP | 20.54ms | 22.59ms | 25.68ms | 19.70 |

## Test Configuration

- **Test Type**: Smoke test (quick validation)
- **VUs**: 5 virtual users
- **Duration**: 30 seconds
- **Endpoints tested**: `/health`, `/api/v1/article/`

## Regression Detection

A performance regression is detected when:
- **Warning**: p95 latency exceeds baseline × 1.10
- **Critical**: p95 latency exceeds baseline × 1.25
- **Error rate**: Any errors > 1% is a failure

## Files

- `roadrunner.json` - RoadRunner baseline metrics
- `phpfpm.json` - PHP-FPM baseline metrics
- `frankenphp.json` - FrankenPHP baseline metrics

## Usage

Compare current test results against baselines:

```bash
# Run smoke test and compare
./scripts/perf/perf-baseline.sh compare roadrunner
./scripts/perf/perf-baseline.sh compare phpfpm
./scripts/perf/perf-baseline.sh compare frankenphp
```

## Key Findings

1. **RoadRunner is ~5x faster** than PHP-FPM and FrankenPHP
   - Persistent process model eliminates PHP bootstrap overhead
   - Go-based HTTP server with minimal syscalls

2. **FrankenPHP slightly slower than PHP-FPM** in this test
   - Worker mode may need tuning for this workload
   - Caddy HTTP server adds some overhead vs nginx
   - Could improve with OPcache preloading

3. **All runtimes achieve 0% error rate** under light load
   - 5 VUs is well within capacity for all runtimes
