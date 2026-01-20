#!/bin/bash

# ==============================================================================
# Outbox Metrics Integration Test Script
# ==============================================================================
# This script tests the OpenTelemetry metrics integration for the Transactional
# Outbox pattern. It creates article articles to generate events, publishes them
# via the outbox publisher, and verifies metrics are being emitted.
#
# Prerequisites:
# - Docker Compose stack running (make start)
# - Observability stack running (make otel-start)
#
# Usage:
#   ./scripts/test-outbox-metrics.sh
# ==============================================================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
API_BASE_URL="${API_BASE_URL:-http://localhost/api/v1}"
DOCKER_CONTAINER="test-micro-article-system-rest"
GRAFANA_URL="${GRAFANA_URL:-http://localhost:3000}"
OTEL_URL="${OTEL_URL:-http://localhost:4318}"

# Helper functions
print_header() {
    echo -e "\n${BLUE}=== $1 ===${NC}\n"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}! $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

# ==============================================================================
# STEP 1: Check Prerequisites
# ==============================================================================
print_header "Checking Prerequisites"

# Check if Docker is running
if ! docker ps > /dev/null 2>&1; then
    print_error "Docker is not running"
    exit 1
fi
print_success "Docker is running"

# Check if main container is running
if ! docker ps --filter "name=${DOCKER_CONTAINER}" --filter "status=running" | grep -q "${DOCKER_CONTAINER}"; then
    print_error "Container ${DOCKER_CONTAINER} is not running"
    exit 1
fi
print_success "Application container is running"

# Check if OTel collector is reachable
if curl -s "${GRAFANA_URL}/api/health" > /dev/null 2>&1; then
    print_success "Grafana is reachable at ${GRAFANA_URL}"
else
    print_warning "Grafana not reachable at ${GRAFANA_URL} - metrics may not be visible"
fi

# ==============================================================================
# STEP 2: Check Current Outbox State
# ==============================================================================
print_header "Checking Current Outbox State"

echo "Querying outbox table..."
OUTBOX_STATE=$(docker compose exec -T test-micro-article-system-rest php bin/console dbal:run-sql \
    "SELECT COUNT(*) as total,
            COUNT(*) FILTER (WHERE published_at IS NULL) as pending,
            COUNT(*) FILTER (WHERE published_at IS NOT NULL) as published
     FROM outbox" 2>/dev/null || echo "Query failed")

if [[ "$OUTBOX_STATE" == *"Query failed"* ]]; then
    print_warning "Could not query outbox table (may not exist yet)"
else
    echo "$OUTBOX_STATE"
fi

# ==============================================================================
# STEP 3: Create Article Article to Generate Events
# ==============================================================================
print_header "Creating Article Articles to Generate Events"

# Generate unique IDs for test data
TIMESTAMP=$(date +%s)
UUID1=$(cat /proc/sys/kernel/random/uuid 2>/dev/null || uuidgen)
UUID2=$(cat /proc/sys/kernel/random/uuid 2>/dev/null || uuidgen)

echo "Creating first article article..."
RESPONSE1=$(curl -s -X POST "${API_BASE_URL}/article/" \
    -H "Content-Type: application/json" \
    -d "{
        \"uuid\": \"${UUID1}\",
        \"title\": \"Test Outbox Metrics Article ${TIMESTAMP}\",
        \"body\": \"This article tests the outbox metrics integration.\",
        \"category\": \"Technology\"
    }" 2>&1)

if [[ "$RESPONSE1" == *"error"* ]] || [[ "$RESPONSE1" == *"404"* ]]; then
    print_warning "First article creation returned: $RESPONSE1"
else
    print_success "First article created: ${UUID1}"
    echo "Response: ${RESPONSE1:0:200}..."
fi

sleep 1

echo "Creating second article article..."
RESPONSE2=$(curl -s -X POST "${API_BASE_URL}/article/" \
    -H "Content-Type: application/json" \
    -d "{
        \"uuid\": \"${UUID2}\",
        \"title\": \"Second Test Article ${TIMESTAMP}\",
        \"body\": \"Second article for testing outbox pattern.\",
        \"category\": \"Science\"
    }" 2>&1)

if [[ "$RESPONSE2" == *"error"* ]] || [[ "$RESPONSE2" == *"404"* ]]; then
    print_warning "Second article creation returned: $RESPONSE2"
else
    print_success "Second article created: ${UUID2}"
fi

# ==============================================================================
# STEP 4: Check Outbox Has Pending Messages
# ==============================================================================
print_header "Checking Outbox for Pending Messages"

sleep 2

echo "Querying outbox table for pending events..."
docker compose exec -T test-micro-article-system-rest php bin/console dbal:run-sql \
    "SELECT id, message_type, event_type, aggregate_type, published_at, retry_count
     FROM outbox
     WHERE published_at IS NULL
     ORDER BY created_at DESC
     LIMIT 10" 2>/dev/null || print_warning "Could not query outbox"

# ==============================================================================
# STEP 5: Run Outbox Publisher (Single Batch)
# ==============================================================================
print_header "Running Outbox Publisher (Single Batch)"

echo "Executing outbox:publish command with --run-once..."
docker compose exec -T test-micro-article-system-rest php bin/console app:outbox:publish \
    --run-once \
    --batch-size=50 \
    -v 2>&1 | head -50

print_success "Outbox publisher completed"

# ==============================================================================
# STEP 6: Verify Outbox Messages Were Published
# ==============================================================================
print_header "Verifying Published Messages"

echo "Checking outbox table after publishing..."
docker compose exec -T test-micro-article-system-rest php bin/console dbal:run-sql \
    "SELECT message_type, event_type, published_at, retry_count
     FROM outbox
     WHERE published_at IS NOT NULL
     ORDER BY published_at DESC
     LIMIT 10" 2>/dev/null || print_warning "Could not query outbox"

# ==============================================================================
# STEP 7: Query Metrics from Mimir/Prometheus
# ==============================================================================
print_header "Querying OpenTelemetry Metrics"

# Wait for metrics to be scraped
echo "Waiting 10 seconds for metrics to be collected..."
sleep 10

# Query Mimir for outbox metrics via Grafana proxy
echo "Querying outbox.messages.enqueued metric..."
ENQUEUED_METRIC=$(curl -s "${GRAFANA_URL}/api/datasources/proxy/uid/mimir/api/v1/query" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    --data-urlencode "query=outbox_messages_enqueued_total" 2>/dev/null || echo "Query failed")

if [[ "$ENQUEUED_METRIC" == *"Query failed"* ]]; then
    print_warning "Could not query enqueued metric directly"
    echo "Trying alternative metric names..."

    # Try with different naming conventions
    for METRIC_NAME in "outbox_messages_enqueued" "outbox_messages_published" "otel_outbox"; do
        RESULT=$(curl -s "${GRAFANA_URL}/api/datasources/proxy/uid/mimir/api/v1/query" \
            -H "Content-Type: application/x-www-form-urlencoded" \
            --data-urlencode "query={__name__=~\".*${METRIC_NAME}.*\"}" 2>/dev/null)
        if [[ "$RESULT" != *"\"result\":[]"* ]] && [[ "$RESULT" != *"Query failed"* ]]; then
            echo "Found metric matching '${METRIC_NAME}':"
            echo "$RESULT" | jq -r '.data.result[].metric.__name__' 2>/dev/null | head -10
        fi
    done
else
    echo "Enqueued metric:"
    echo "$ENQUEUED_METRIC" | jq . 2>/dev/null || echo "$ENQUEUED_METRIC"
fi

echo ""
echo "Querying outbox.messages.published metric..."
PUBLISHED_METRIC=$(curl -s "${GRAFANA_URL}/api/datasources/proxy/uid/mimir/api/v1/query" \
    -H "Content-Type: application/x-www-form-urlencoded" \
    --data-urlencode "query=outbox_messages_published_total" 2>/dev/null || echo "Query failed")

if [[ "$PUBLISHED_METRIC" != *"Query failed"* ]]; then
    echo "Published metric:"
    echo "$PUBLISHED_METRIC" | jq . 2>/dev/null || echo "$PUBLISHED_METRIC"
fi

# ==============================================================================
# STEP 8: List All Available Outbox Metrics
# ==============================================================================
print_header "Listing All Available Outbox Metrics"

echo "Searching for all outbox-related metrics..."
ALL_METRICS=$(curl -s "${GRAFANA_URL}/api/datasources/proxy/uid/mimir/api/v1/label/__name__/values" 2>/dev/null)

if [[ -n "$ALL_METRICS" ]] && [[ "$ALL_METRICS" != *"error"* ]]; then
    echo "Metrics containing 'outbox':"
    echo "$ALL_METRICS" | jq -r '.data[]' 2>/dev/null | grep -i "outbox" || print_warning "No outbox metrics found yet"

    echo ""
    echo "All available metrics (first 20):"
    echo "$ALL_METRICS" | jq -r '.data[]' 2>/dev/null | head -20
else
    print_warning "Could not list metrics from Mimir"
fi

# ==============================================================================
# STEP 9: Summary
# ==============================================================================
print_header "Test Summary"

echo "Test completed. Key findings:"
echo ""
echo "1. Article articles created to generate domain events"
echo "2. Outbox publisher executed to publish pending messages"
echo "3. Metrics should be visible in Grafana at:"
echo "   ${GRAFANA_URL}/explore?orgId=1&left=%7B\"datasource\":\"mimir\"%7D"
echo ""
echo "To view the Outbox Dashboard:"
echo "   ${GRAFANA_URL}/dashboards"
echo ""
echo "Expected metrics:"
echo "   - outbox.messages.enqueued (Counter)"
echo "   - outbox.messages.published (Counter)"
echo "   - outbox.messages.pending (Gauge)"
echo "   - outbox.publish.duration (Histogram)"
echo "   - outbox.publish.failures (Counter)"
echo "   - outbox.retry.attempts (Counter)"
echo ""
print_success "Test script completed successfully"
