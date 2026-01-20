/**
 * k6 Smoke Test Script
 *
 * Purpose: Quick 30-second validation test for CI/CD pipelines.
 * Validates basic API functionality without heavy load.
 *
 * Usage:
 *   k6 run scripts/perf/k6-smoke-test.js
 *   TARGET_URL=http://localhost:8080 k6 run scripts/perf/k6-smoke-test.js
 *
 * Runtimes:
 *   - PHP-FPM:     TARGET_URL=http://localhost:80
 *   - RoadRunner:  TARGET_URL=http://localhost:8080
 *   - FrankenPHP:  TARGET_URL=http://localhost:8081
 *
 * @see ADR-012-performance-testing-strategy.md Section 5.1
 */

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');
const healthLatency = new Trend('health_api_latency');
const articleLatency = new Trend('article_api_latency');

export const options = {
  // Quick smoke test: 5 VUs for 30 seconds
  vus: 5,
  duration: '30s',

  // Strict thresholds for smoke test (must pass for PR to proceed)
  thresholds: {
    http_req_duration: ['p(95)<500'],   // 95th percentile under 500ms
    http_req_failed: ['rate<0.05'],     // Less than 5% failed requests
    errors: ['rate<0.05'],              // Custom error rate under 5%
    health_api_latency: ['p(95)<200'],  // Health endpoint under 200ms
    article_api_latency: ['p(95)<1000'],   // Article API under 1000ms
  },

  // Summary output configuration
  summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(90)', 'p(95)'],
};

const BASE_URL = __ENV.TARGET_URL || 'http://localhost:8080';

export function setup() {
  console.log(`Smoke Test starting against: ${BASE_URL}`);
  console.log('Configuration: 5 VUs for 30 seconds');
  console.log('');

  // Verify target is reachable with retries
  let healthCheck;
  let retries = 3;

  while (retries > 0) {
    try {
      healthCheck = http.get(`${BASE_URL}/health`, { timeout: '10s' });
      if (healthCheck.status === 200) {
        console.log('Health check passed, starting smoke test...');
        return { baseUrl: BASE_URL };
      }
    } catch (e) {
      console.log(`Health check attempt failed, ${retries - 1} retries left`);
    }
    retries--;
    sleep(2);
  }

  throw new Error(`Health check failed after retries: ${healthCheck ? healthCheck.status : 'no response'}`);
}

export default function (data) {
  // Test 1: Health endpoint (lightweight)
  const healthRes = http.get(`${BASE_URL}/health`);
  healthLatency.add(healthRes.timings.duration);
  const healthOk = check(healthRes, {
    'health status 200': (r) => r.status === 200,
    'health response time OK': (r) => r.timings.duration < 500,
  });
  errorRate.add(!healthOk);

  // Test 2: Article list endpoint (API workload)
  const listRes = http.get(`${BASE_URL}/api/v1/article/`);
  articleLatency.add(listRes.timings.duration);
  const listOk = check(listRes, {
    'list status 200': (r) => r.status === 200,
    'list response time OK': (r) => r.timings.duration < 1000,
    'list has valid response': (r) => {
      try {
        const body = r.json();
        return body !== null && (body.data !== undefined || Array.isArray(body));
      } catch (e) {
        return false;
      }
    },
  });
  errorRate.add(!listOk);

  // Short sleep between iterations
  sleep(0.5);
}

export function teardown(data) {
  console.log('');
  console.log('Smoke test completed');
  console.log('If thresholds passed, API is ready for load testing');
}

/**
 * Handle summary for CI integration
 * Returns exit code based on threshold checks
 */
export function handleSummary(data) {
  // Check for threshold failures
  const thresholdsFailed = data.root_group && data.root_group.checks
    ? Object.values(data.root_group.checks).some(c => c.fails > 0)
    : false;

  // Generate summary
  const summary = {
    timestamp: new Date().toISOString(),
    target: BASE_URL,
    duration_seconds: 30,
    vus: 5,
    metrics: {
      requests_total: data.metrics.http_reqs ? data.metrics.http_reqs.values.count : 0,
      requests_failed: data.metrics.http_req_failed ? data.metrics.http_req_failed.values.rate : 0,
      duration_p95: data.metrics.http_req_duration ? data.metrics.http_req_duration.values['p(95)'] : 0,
      health_latency_p95: data.metrics.health_api_latency ? data.metrics.health_api_latency.values['p(95)'] : 0,
      article_latency_p95: data.metrics.article_api_latency ? data.metrics.article_api_latency.values['p(95)'] : 0,
    },
    thresholds_passed: !thresholdsFailed,
  };

  return {
    'stdout': textSummary(data, { indent: ' ', enableColors: true }),
    'tests/performance/results/smoke-summary.json': JSON.stringify(summary, null, 2),
  };
}

// Simple text summary helper
function textSummary(data, options) {
  const lines = [
    '',
    '='.repeat(60),
    'SMOKE TEST SUMMARY',
    '='.repeat(60),
    '',
  ];

  if (data.metrics.http_reqs) {
    lines.push(`Total Requests: ${data.metrics.http_reqs.values.count}`);
  }
  if (data.metrics.http_req_failed) {
    lines.push(`Failed Rate: ${(data.metrics.http_req_failed.values.rate * 100).toFixed(2)}%`);
  }
  if (data.metrics.http_req_duration) {
    lines.push(`Latency (p95): ${data.metrics.http_req_duration.values['p(95)'].toFixed(2)}ms`);
  }

  lines.push('');

  return lines.join('\n');
}
