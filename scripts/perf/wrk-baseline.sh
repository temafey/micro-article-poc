#!/bin/bash
#
# wrk-baseline.sh - Quick HTTP baseline testing with wrk
#
# Purpose: Fast HTTP benchmarking for quick baseline metrics across PHP runtimes.
# Implements ADR-012 Section 5.3 specification.
#
# Usage:
#   ./wrk-baseline.sh                           # Test default (localhost:8080)
#   TARGET_URL=http://localhost:80 ./wrk-baseline.sh   # Test PHP-FPM
#   ./wrk-baseline.sh --json                    # Output JSON summary
#   ./wrk-baseline.sh -h                        # Show help
#
# Runtimes:
#   PHP-FPM:     TARGET_URL=http://localhost:80
#   RoadRunner:  TARGET_URL=http://localhost:8080
#   FrankenPHP:  TARGET_URL=http://localhost:8081
#
# @see ADR-012-performance-testing-strategy.md Section 5.3

set -euo pipefail

# Configuration
TARGET="${TARGET_URL:-http://localhost:8080}"
RESULTS_DIR="tests/performance/results"
THREADS="${WRK_THREADS:-4}"
CONNECTIONS="${WRK_CONNECTIONS:-100}"
DURATION="${WRK_DURATION:-30s}"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Endpoints to test
ENDPOINTS=(
    "health:/health"
    "article:/api/v1/article/"
)

#######################################
# Display usage information
#######################################
usage() {
    cat << EOF
Usage: $(basename "$0") [OPTIONS]

Quick HTTP baseline testing using wrk.

Options:
    -h, --help      Show this help message
    -j, --json      Output results as JSON
    -q, --quiet     Suppress progress output
    -t THREADS      Number of threads (default: 4)
    -c CONNECTIONS  Number of connections (default: 100)
    -d DURATION     Test duration (default: 30s)

Environment Variables:
    TARGET_URL      Target URL (default: http://localhost:8080)
    WRK_THREADS     Override thread count
    WRK_CONNECTIONS Override connection count
    WRK_DURATION    Override test duration

Examples:
    # Test RoadRunner (default)
    ./wrk-baseline.sh

    # Test PHP-FPM
    TARGET_URL=http://localhost:80 ./wrk-baseline.sh

    # Test FrankenPHP with JSON output
    TARGET_URL=http://localhost:8081 ./wrk-baseline.sh --json

    # Quick test with custom parameters
    ./wrk-baseline.sh -t 2 -c 50 -d 10s

EOF
    exit 0
}

#######################################
# Log message with timestamp
#######################################
log() {
    local level=$1
    shift
    local color=""
    case $level in
        INFO)  color=$BLUE ;;
        OK)    color=$GREEN ;;
        WARN)  color=$YELLOW ;;
        ERROR) color=$RED ;;
    esac
    if [[ "${QUIET:-false}" != "true" ]]; then
        echo -e "${color}[$(date '+%H:%M:%S')] [$level]${NC} $*"
    fi
}

#######################################
# Check required dependencies
#######################################
check_dependencies() {
    local missing=()

    if ! command -v wrk >/dev/null 2>&1; then
        missing+=("wrk")
    fi
    if ! command -v curl >/dev/null 2>&1; then
        missing+=("curl")
    fi

    if [[ ${#missing[@]} -gt 0 ]]; then
        log ERROR "Missing dependencies: ${missing[*]}"
        echo "Install with:"
        echo "  Ubuntu/Debian: sudo apt-get install wrk curl"
        echo "  macOS: brew install wrk curl"
        exit 1
    fi
}

#######################################
# Check if target is reachable
#######################################
check_target() {
    log INFO "Checking target: $TARGET"

    if ! curl -sf --max-time 5 "$TARGET/health" >/dev/null 2>&1; then
        log ERROR "Target $TARGET is not reachable"
        log INFO "Ensure the runtime is running:"
        echo "  docker compose up -d"
        echo "  curl $TARGET/health"
        exit 1
    fi

    log OK "Target is reachable"
}

#######################################
# Detect runtime from URL
#######################################
detect_runtime() {
    local port
    port=$(echo "$TARGET" | grep -oE ':[0-9]+' | tr -d ':')

    case "${port:-80}" in
        80)   echo "fpm" ;;
        8080) echo "rr" ;;
        8081) echo "frank" ;;
        *)    echo "unknown" ;;
    esac
}

#######################################
# Parse wrk output and extract metrics
# Arguments:
#   $1 - wrk output text
# Outputs:
#   JSON object with metrics
#######################################
parse_wrk_output() {
    local output="$1"

    # Extract values using grep and awk
    local latency_avg latency_max latency_stdev req_sec transfer_sec requests

    latency_avg=$(echo "$output" | grep -E "^\s+Latency" | awk '{print $2}' | head -1)
    latency_stdev=$(echo "$output" | grep -E "^\s+Latency" | awk '{print $3}' | head -1)
    latency_max=$(echo "$output" | grep -E "^\s+Latency" | awk '{print $4}' | head -1)
    req_sec=$(echo "$output" | grep "Requests/sec:" | awk '{print $2}')
    transfer_sec=$(echo "$output" | grep "Transfer/sec:" | awk '{print $2}')
    requests=$(echo "$output" | grep "requests in" | awk '{print $1}')

    # Convert latency to milliseconds
    latency_avg_ms=$(convert_to_ms "$latency_avg")
    latency_max_ms=$(convert_to_ms "$latency_max")
    latency_stdev_ms=$(convert_to_ms "$latency_stdev")

    # Output JSON
    cat << EOF
{
    "latency_avg_ms": ${latency_avg_ms:-0},
    "latency_max_ms": ${latency_max_ms:-0},
    "latency_stdev_ms": ${latency_stdev_ms:-0},
    "requests_per_sec": ${req_sec:-0},
    "transfer_per_sec": "${transfer_sec:-0}",
    "total_requests": ${requests:-0}
}
EOF
}

#######################################
# Convert time string to milliseconds
# Arguments:
#   $1 - Time string (e.g., "12.34ms", "1.5s", "500us")
#######################################
convert_to_ms() {
    local value="$1"

    if [[ -z "$value" ]]; then
        echo "0"
        return
    fi

    # Extract number and unit
    local num unit
    num=$(echo "$value" | grep -oE '[0-9.]+')
    unit=$(echo "$value" | grep -oE '[a-z]+$')

    case "$unit" in
        us) echo "scale=3; $num / 1000" | bc ;;
        ms) echo "$num" ;;
        s)  echo "scale=3; $num * 1000" | bc ;;
        *)  echo "$num" ;;
    esac
}

#######################################
# Run wrk test for an endpoint
# Arguments:
#   $1 - Endpoint name
#   $2 - Endpoint path
#######################################
run_endpoint_test() {
    local name="$1"
    local path="$2"
    local url="${TARGET}${path}"
    local runtime
    runtime=$(detect_runtime)

    log INFO "Testing endpoint: $name ($path)"
    log INFO "Parameters: $THREADS threads, $CONNECTIONS connections, $DURATION duration"

    # Run wrk and capture output
    local output_file="${RESULTS_DIR}/${runtime}-${name}-wrk-${TIMESTAMP}.txt"
    local output

    output=$(wrk -t"$THREADS" -c"$CONNECTIONS" -d"$DURATION" "$url" 2>&1) || true

    # Save raw output
    echo "$output" > "$output_file"
    log OK "Raw output saved: $output_file"

    # Parse and display results
    local metrics
    metrics=$(parse_wrk_output "$output")

    if [[ "${JSON_OUTPUT:-false}" == "true" ]]; then
        echo "$metrics"
    else
        # Display formatted results
        local latency_avg latency_max req_sec
        latency_avg=$(echo "$metrics" | grep -o '"latency_avg_ms": [0-9.]*' | cut -d' ' -f2)
        latency_max=$(echo "$metrics" | grep -o '"latency_max_ms": [0-9.]*' | cut -d' ' -f2)
        req_sec=$(echo "$metrics" | grep -o '"requests_per_sec": [0-9.]*' | cut -d' ' -f2)

        echo ""
        echo -e "  ${GREEN}Latency (avg):${NC} ${latency_avg}ms"
        echo -e "  ${GREEN}Latency (max):${NC} ${latency_max}ms"
        echo -e "  ${GREEN}Requests/sec:${NC}  ${req_sec}"
        echo ""
    fi

    # Store metrics for summary
    RESULTS["$name"]="$metrics"
}

#######################################
# Generate summary JSON
#######################################
generate_summary() {
    local runtime
    runtime=$(detect_runtime)

    local summary_file="${RESULTS_DIR}/${runtime}-summary-${TIMESTAMP}.json"

    cat << EOF > "$summary_file"
{
    "runtime": "$runtime",
    "target": "$TARGET",
    "timestamp": "$(date -Iseconds)",
    "parameters": {
        "threads": $THREADS,
        "connections": $CONNECTIONS,
        "duration": "$DURATION"
    },
    "endpoints": {
        "health": ${RESULTS["health"]:-{}},
        "article": ${RESULTS["article"]:-{}}
    }
}
EOF

    log OK "Summary saved: $summary_file"

    if [[ "${JSON_OUTPUT:-false}" == "true" ]]; then
        cat "$summary_file"
    fi
}

#######################################
# Main execution
#######################################
main() {
    # Parse arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help) usage ;;
            -j|--json) JSON_OUTPUT=true ;;
            -q|--quiet) QUIET=true ;;
            -t) THREADS="$2"; shift ;;
            -c) CONNECTIONS="$2"; shift ;;
            -d) DURATION="$2"; shift ;;
            *) log ERROR "Unknown option: $1"; usage ;;
        esac
        shift
    done

    # Declare associative array for results
    declare -A RESULTS

    # Pre-flight checks
    check_dependencies
    mkdir -p "$RESULTS_DIR"
    check_target

    local runtime
    runtime=$(detect_runtime)

    log INFO "Starting wrk baseline test"
    log INFO "Runtime: $runtime"
    log INFO "Target: $TARGET"
    echo ""

    # Run tests for each endpoint
    for endpoint in "${ENDPOINTS[@]}"; do
        local name="${endpoint%%:*}"
        local path="${endpoint#*:}"
        run_endpoint_test "$name" "$path"
    done

    # Generate summary
    generate_summary

    log OK "Baseline test complete!"
    echo ""
    echo "Results saved to: $RESULTS_DIR/"
    echo "Files:"
    ls -1 "${RESULTS_DIR}/${runtime}-"*"-${TIMESTAMP}"* 2>/dev/null | sed 's/^/  /'
}

# Run main function
main "$@"
