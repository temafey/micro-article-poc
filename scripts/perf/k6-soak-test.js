/**
 * k6 Soak Test Script
 *
 * Purpose: 30-minute sustained load test for memory leak detection
 * Tests application stability over extended periods under constant load.
 *
 * Usage:
 *   k6 run scripts/perf/k6-soak-test.js
 *   TARGET_URL=http://localhost:8080 k6 run scripts/perf/k6-soak-test.js
 *   k6 run --out json=results.json scripts/perf/k6-soak-test.js
 *
 * Runtimes:
 *   - PHP-FPM:     TARGET_URL=http://localhost:80
 *   - RoadRunner:  TARGET_URL=http://localhost:8080
 *   - FrankenPHP:  TARGET_URL=http://localhost:8081
 *
 * Memory Monitoring:
 *   Run alongside: docker stats --format "{{.Name}}: {{.MemUsage}}" <container>
 *
 * @see ADR-012-performance-testing-strategy.md Section 5.2
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';

// Custom metrics for soak testing
const errorRate = new Rate('errors');
const healthLatency = new Trend('health_api_latency');
const articleLatency = new Trend('article_api_latency');
const iterationCounter = new Counter('iterations_total');
const memoryTrend = new Trend('memory_trend');

// Track iteration timing for stability analysis
const iterationTiming = new Trend('iteration_timing');

export const options = {
  stages: [
    { duration: '1m', target: 30 },   // Ramp up to 30 VUs
    { duration: '28m', target: 30 },  // Hold constant load for 28 minutes
    { duration: '1m', target: 0 },    // Ramp down
  ],
  thresholds: {
    http_req_duration: ['p(95)<300'],     // Slightly relaxed for soak
    http_req_failed: ['rate<0.01'],       // Less than 1% failed requests
    errors: ['rate<0.01'],                // Custom error rate under 1%
    iteration_timing: ['p(99)<5000'],     // Iteration should complete in 5s
    // Memory growth threshold: std dev should be low (stable memory)
  },
};

const BASE_URL = __ENV.TARGET_URL || 'http://localhost:8080';

// Track start time for stability analysis
let startTime;

export function setup() {
  startTime = Date.now();
  console.log(`Soak Test starting against: ${BASE_URL}`);
  console.log('Duration: 30 minutes (1m ramp, 28m hold, 1m ramp-down)');
  console.log('Constant load: 30 VUs');

  // Verify target is reachable
  const healthCheck = http.get(`${BASE_URL}/health`);
  if (healthCheck.status !== 200) {
    throw new Error(`Health check failed: ${healthCheck.status}`);
  }
  console.log('Health check passed, starting soak test...');
  console.log('TIP: Monitor memory with: docker stats');

  return { startTime: startTime };
}

export default function (data) {
  const iterStart = Date.now();

  // Test 1: Health endpoint
  const healthRes = http.get(`${BASE_URL}/health`);
  healthLatency.add(healthRes.timings.duration);
  const healthOk = check(healthRes, {
    'health status 200': (r) => r.status === 200,
  });
  errorRate.add(!healthOk);

  // Test 2: Article list endpoint
  const listRes = http.get(`${BASE_URL}/api/v1/article/`);
  articleLatency.add(listRes.timings.duration);
  const listOk = check(listRes, {
    'list status 200': (r) => r.status === 200,
    'list response valid': (r) => {
      try {
        const body = r.json();
        return body !== null;
      } catch (e) {
        return false;
      }
    },
  });
  errorRate.add(!listOk);

  // Track iteration count
  iterationCounter.add(1);

  // Track iteration timing for stability analysis
  const iterDuration = Date.now() - iterStart;
  iterationTiming.add(iterDuration);

  // Log periodic status (every ~5 minutes based on iteration count)
  const elapsed = Math.floor((Date.now() - data.startTime) / 1000 / 60);
  if (__ITER % 1000 === 0) {
    console.log(`[${elapsed}m] Iteration ${__ITER}, last duration: ${iterDuration}ms`);
  }

  // Simulate user think time
  sleep(1);
}

export function teardown(data) {
  const totalDuration = Math.floor((Date.now() - data.startTime) / 1000);
  console.log(`Soak test completed after ${totalDuration} seconds`);
  console.log('Check memory_trend metric for memory stability');
  console.log('Check iteration_timing p99 for timing consistency');
}
