/**
 * k6 Stress Test Script
 *
 * Purpose: 10-minute stress test with spike pattern
 * Tests application behavior under extreme load and recovery capability.
 *
 * Usage:
 *   k6 run scripts/perf/k6-stress-test.js
 *   TARGET_URL=http://localhost:8080 k6 run scripts/perf/k6-stress-test.js
 *   k6 run --out json=results.json scripts/perf/k6-stress-test.js
 *
 * Runtimes:
 *   - PHP-FPM:     TARGET_URL=http://localhost:80
 *   - RoadRunner:  TARGET_URL=http://localhost:8080
 *   - FrankenPHP:  TARGET_URL=http://localhost:8081
 *
 * @see ADR-012-performance-testing-strategy.md Section 5.2
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend, Counter } from 'k6/metrics';

// Custom metrics for stress testing
const errorRate = new Rate('errors');
const healthLatency = new Trend('health_api_latency');
const articleLatency = new Trend('article_api_latency');
const failureCounter = new Counter('failures_total');
const recoveryTime = new Trend('recovery_time');

export const options = {
  stages: [
    // Phase 1: Warm up
    { duration: '30s', target: 50 },    // Ramp to normal load

    // Phase 2: Stress spike
    { duration: '1m', target: 100 },    // Increase to moderate stress
    { duration: '1m', target: 200 },    // Spike to high stress
    { duration: '2m', target: 200 },    // Hold at peak stress

    // Phase 3: Extreme spike
    { duration: '30s', target: 300 },   // Extreme spike
    { duration: '1m', target: 300 },    // Hold extreme

    // Phase 4: Recovery test
    { duration: '1m', target: 100 },    // Quick drop to test recovery
    { duration: '2m', target: 50 },     // Recovery phase
    { duration: '1m', target: 0 },      // Ramp down
  ],
  thresholds: {
    // Relaxed thresholds for stress test - we expect some failures
    http_req_duration: ['p(95)<1000'],    // 95th percentile under 1s
    http_req_failed: ['rate<0.10'],       // Allow up to 10% failures under stress
    errors: ['rate<0.10'],                // Custom error rate under 10%
    // Track but don't fail on these
    health_api_latency: ['p(99)<2000'],   // Health under 2s at p99
    article_api_latency: ['p(99)<3000'],     // Article API under 3s at p99
  },
};

const BASE_URL = __ENV.TARGET_URL || 'http://localhost:8080';

// Track stages for recovery analysis
let currentStage = 'warmup';
let lastStageChange = 0;

export function setup() {
  console.log(`Stress Test starting against: ${BASE_URL}`);
  console.log('Pattern: Warm up → Stress spike → Extreme spike → Recovery');
  console.log('Peak VUs: 300');
  console.log('Duration: 10 minutes');

  // Verify target is reachable
  const healthCheck = http.get(`${BASE_URL}/health`);
  if (healthCheck.status !== 200) {
    throw new Error(`Health check failed: ${healthCheck.status}`);
  }
  console.log('Health check passed, starting stress test...');
  console.log('WARNING: This test will push the system to its limits!');

  return { startTime: Date.now() };
}

export default function (data) {
  const elapsed = Date.now() - data.startTime;

  // Track stage transitions for logging
  const elapsedMin = elapsed / 1000 / 60;
  let stage = 'unknown';
  if (elapsedMin < 0.5) stage = 'warmup';
  else if (elapsedMin < 2.5) stage = 'stress';
  else if (elapsedMin < 5) stage = 'peak-stress';
  else if (elapsedMin < 6.5) stage = 'extreme';
  else if (elapsedMin < 7.5) stage = 'recovery-start';
  else stage = 'recovery';

  // Test 1: Health endpoint (critical path)
  const healthStart = Date.now();
  const healthRes = http.get(`${BASE_URL}/health`, {
    timeout: '10s', // Extended timeout for stress conditions
  });
  healthLatency.add(healthRes.timings.duration);

  const healthOk = check(healthRes, {
    'health status 200': (r) => r.status === 200,
    'health response time < 2s': (r) => r.timings.duration < 2000,
  });

  if (!healthOk) {
    errorRate.add(true);
    failureCounter.add(1);

    // Track recovery time if in recovery phase
    if (stage.startsWith('recovery')) {
      recoveryTime.add(Date.now() - healthStart);
    }
  } else {
    errorRate.add(false);
  }

  // Test 2: Article API endpoint
  const articleRes = http.get(`${BASE_URL}/api/v1/article/`, {
    timeout: '15s', // Extended timeout for stress conditions
  });
  articleLatency.add(articleRes.timings.duration);

  const articleOk = check(articleRes, {
    'article status 200': (r) => r.status === 200,
    'article response time < 3s': (r) => r.timings.duration < 3000,
  });

  if (!articleOk) {
    errorRate.add(true);
    failureCounter.add(1);
  } else {
    errorRate.add(false);
  }

  // Log stage transitions
  if (stage !== currentStage) {
    console.log(`[${elapsedMin.toFixed(1)}m] Stage: ${stage}, VUs: ${__VU}`);
    currentStage = stage;
  }

  // Minimal sleep under stress to maximize load
  sleep(0.5);
}

export function teardown(data) {
  const totalDuration = Math.floor((Date.now() - data.startTime) / 1000);
  console.log(`Stress test completed after ${totalDuration} seconds`);
  console.log('Review metrics:');
  console.log('  - http_req_failed: Should recover to low rate during recovery phase');
  console.log('  - recovery_time: Time to restore healthy responses');
  console.log('  - failures_total: Total failures during stress');
}
