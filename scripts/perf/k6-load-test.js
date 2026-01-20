/**
 * k6 Load Test Script
 *
 * Purpose: 5-minute load test with ramping virtual users (VUs)
 * Tests application behavior under sustained normal to moderate load.
 *
 * Usage:
 *   k6 run scripts/perf/k6-load-test.js
 *   TARGET_URL=http://localhost:8080 k6 run scripts/perf/k6-load-test.js
 *   k6 run --out json=results.json scripts/perf/k6-load-test.js
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
import { Rate, Trend } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');
const healthLatency = new Trend('health_api_latency');
const articleLatency = new Trend('article_api_latency');

export const options = {
  stages: [
    { duration: '1m', target: 50 },   // Ramp up to 50 VUs over 1 minute
    { duration: '2m', target: 50 },   // Hold at 50 VUs for 2 minutes
    { duration: '1m', target: 100 },  // Ramp up to 100 VUs over 1 minute
    { duration: '1m', target: 100 },  // Hold at 100 VUs for 1 minute
  ],
  thresholds: {
    http_req_duration: ['p(95)<200'],   // 95th percentile under 200ms
    http_req_failed: ['rate<0.01'],     // Less than 1% failed requests
    errors: ['rate<0.01'],              // Custom error rate under 1%
    health_api_latency: ['p(95)<100'],  // Health endpoint under 100ms
    article_api_latency: ['p(95)<500'],    // Article API under 500ms
  },
};

const BASE_URL = __ENV.TARGET_URL || 'http://localhost:8080';

export function setup() {
  console.log(`Load Test starting against: ${BASE_URL}`);
  console.log('Stages: 0→50 VUs (1m), hold 50 (2m), 50→100 (1m), hold 100 (1m)');

  // Verify target is reachable
  const healthCheck = http.get(`${BASE_URL}/health`);
  if (healthCheck.status !== 200) {
    throw new Error(`Health check failed: ${healthCheck.status}`);
  }
  console.log('Health check passed, starting load test...');
}

export default function () {
  // Test 1: Health endpoint (lightweight)
  const healthRes = http.get(`${BASE_URL}/health`);
  healthLatency.add(healthRes.timings.duration);
  const healthOk = check(healthRes, {
    'health status 200': (r) => r.status === 200,
  });
  errorRate.add(!healthOk);

  // Test 2: Article list endpoint (API workload)
  const listRes = http.get(`${BASE_URL}/api/v1/article/`);
  articleLatency.add(listRes.timings.duration);
  const listOk = check(listRes, {
    'list status 200': (r) => r.status === 200,
    'list has data': (r) => {
      try {
        const body = r.json();
        return body !== null && (body.data !== undefined || Array.isArray(body));
      } catch (e) {
        return false;
      }
    },
  });
  errorRate.add(!listOk);

  // Simulate user think time
  sleep(1);
}

export function teardown(data) {
  console.log('Load test completed');
}
