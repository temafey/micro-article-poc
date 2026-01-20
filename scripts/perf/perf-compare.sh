#!/bin/bash
#
# perf-compare.sh - Compare performance across PHP runtimes
#
# Purpose: Run tests against all PHP runtimes and generate comparison report.
# Implements ADR-012 Section 5.6 specification.
#
# Usage:
#   ./perf-compare.sh              # Quick comparison (wrk, 30s each)
#   ./perf-compare.sh load         # Load test comparison (k6, 5min each)
#   ./perf-compare.sh soak         # Soak test comparison (k6, 30min each)
#   ./perf-compare.sh stress       # Stress test comparison (k6, 10min each)
#   ./perf-compare.sh -h           # Show help
#
# @see ADR-012-performance-testing-strategy.md Section 5.6

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
RESULTS_DIR="$PROJECT_ROOT/tests/performance/results"
TIMESTAMP=$(date +%Y%m%d-%H%M%S)

# Runtime configuration
declare -A RUNTIMES=(
    ["fpm"]="http://localhost:80"
    ["rr"]="http://localhost:8080"
    ["frank"]="http://localhost:8081"
)

# Runtime display names
declare -A RUNTIME_NAMES=(
    ["fpm"]="PHP-FPM"
    ["rr"]="RoadRunner"
    ["frank"]="FrankenPHP"
)

# k6 script mapping
declare -A K6_SCRIPTS=(
    ["load"]="$SCRIPT_DIR/k6-load-test.js"
    ["soak"]="$SCRIPT_DIR/k6-soak-test.js"
    ["stress"]="$SCRIPT_DIR/k6-stress-test.js"
)

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

# Test results storage
declare -A LATENCY_P95
declare -A REQ_PER_SEC
declare -A FAILURE_RATE
declare -A AVAILABLE_RUNTIMES

#######################################
# Display usage
#######################################
usage() {
    cat << EOF
Usage: $(basename "$0") [TEST_TYPE] [OPTIONS]

Compare performance across PHP runtimes.

Test Types:
    quick     Quick wrk test (30s per runtime, default)
    load      k6 load test (5 min per runtime)
    soak      k6 soak test (30 min per runtime)
    stress    k6 stress test (10 min per runtime)

Options:
    -h, --help      Show this help message
    -j, --json      Output results as JSON
    -o, --output    Output file for report
    --skip-check    Skip runtime availability check

Runtimes tested:
    PHP-FPM     http://localhost:80
    RoadRunner  http://localhost:8080
    FrankenPHP  http://localhost:8081

Examples:
    # Quick comparison (30s each)
    ./perf-compare.sh

    # Load test comparison
    ./perf-compare.sh load

    # Save comparison to file
    ./perf-compare.sh load -o comparison-report.txt

EOF
    exit 0
}

#######################################
# Log message
#######################################
log() {
    local level=$1; shift
    local color=""
    case $level in
        INFO)  color=$BLUE ;;
        OK)    color=$GREEN ;;
        WARN)  color=$YELLOW ;;
        ERROR) color=$RED ;;
        STEP)  color=$CYAN ;;
    esac
    echo -e "${color}[$(date '+%H:%M:%S')] [$level]${NC} $*"
}

#######################################
# Check dependencies
#######################################
check_dependencies() {
    local test_type="${1:-quick}"
    local missing=()

    if ! command -v curl >/dev/null 2>&1; then
        missing+=("curl")
    fi

    if [[ "$test_type" == "quick" ]]; then
        if ! command -v wrk >/dev/null 2>&1; then
            missing+=("wrk")
        fi
    else
        if ! command -v k6 >/dev/null 2>&1; then
            missing+=("k6")
        fi
    fi

    if ! command -v jq >/dev/null 2>&1; then
        missing+=("jq")
    fi

    if ! command -v bc >/dev/null 2>&1; then
        missing+=("bc")
    fi

    if [[ ${#missing[@]} -gt 0 ]]; then
        log ERROR "Missing dependencies: ${missing[*]}"
        exit 1
    fi
}

#######################################
# Check which runtimes are available
#######################################
check_runtimes() {
    log INFO "Checking runtime availability..."

    local available=0
    for runtime in "${!RUNTIMES[@]}"; do
        local url="${RUNTIMES[$runtime]}"
        if curl -sf --max-time 3 "$url/health" >/dev/null 2>&1; then
            AVAILABLE_RUNTIMES[$runtime]=1
            log OK "${RUNTIME_NAMES[$runtime]} is available at $url"
            ((available++))
        else
            AVAILABLE_RUNTIMES[$runtime]=0
            log WARN "${RUNTIME_NAMES[$runtime]} is not available at $url"
        fi
    done

    echo ""

    if [[ $available -lt 2 ]]; then
        log ERROR "Need at least 2 runtimes for comparison (found: $available)"
        log INFO "Start runtimes with: docker compose up -d"
        exit 1
    fi

    log OK "Found $available available runtimes"
}

#######################################
# Run quick test (wrk)
#######################################
run_quick_test() {
    local runtime="$1"
    local url="${RUNTIMES[$runtime]}"

    log STEP "Running quick test for ${RUNTIME_NAMES[$runtime]}..."

    # Run wrk for health endpoint
    local health_output
    health_output=$(wrk -t4 -c100 -d30s "$url/health" 2>&1) || true

    # Run wrk for article endpoint
    local article_output
    article_output=$(wrk -t4 -c100 -d30s "$url/api/v1/article/" 2>&1) || true

    # Parse health results
    local health_latency health_req
    health_latency=$(echo "$health_output" | grep -E "^\s+Latency" | awk '{print $2}' | head -1)
    health_req=$(echo "$health_output" | grep "Requests/sec:" | awk '{print $2}')

    # Parse article results
    local article_latency article_req
    article_latency=$(echo "$article_output" | grep -E "^\s+Latency" | awk '{print $2}' | head -1)
    article_req=$(echo "$article_output" | grep "Requests/sec:" | awk '{print $2}')

    # Convert latency to ms and average
    local health_ms article_ms avg_latency
    health_ms=$(convert_to_ms "$health_latency")
    article_ms=$(convert_to_ms "$article_latency")
    avg_latency=$(echo "scale=2; ($health_ms + $article_ms) / 2" | bc)

    # Average requests/sec
    local avg_req
    avg_req=$(echo "scale=0; ($health_req + $article_req) / 2" | bc)

    # Store results
    LATENCY_P95[$runtime]="$avg_latency"
    REQ_PER_SEC[$runtime]="$avg_req"
    FAILURE_RATE[$runtime]="0.00"

    log OK "${RUNTIME_NAMES[$runtime]}: Latency=${avg_latency}ms, Req/Sec=${avg_req}"
}

#######################################
# Run k6 test
#######################################
run_k6_test() {
    local runtime="$1"
    local test_type="$2"
    local url="${RUNTIMES[$runtime]}"
    local script="${K6_SCRIPTS[$test_type]}"

    log STEP "Running $test_type test for ${RUNTIME_NAMES[$runtime]}..."

    local output_file="${RESULTS_DIR}/${runtime}-${test_type}-${TIMESTAMP}.json"

    # Run k6 with JSON output
    TARGET_URL="$url" k6 run --out json="$output_file" "$script" 2>&1 | tail -20

    # Parse JSON output for metrics
    local latency_p95 req_sec failure_rate

    # Extract p95 latency
    latency_p95=$(jq -s '[.[] | select(.type == "Point" and .data.name == "http_req_duration") | .data.value] | if length > 0 then (sort | .[length * 0.95 | floor]) else 0 end' "$output_file" 2>/dev/null || echo "0")

    # Extract requests per second (approximate from total requests / duration)
    local total_reqs duration_s
    total_reqs=$(jq -s '[.[] | select(.type == "Point" and .data.name == "http_reqs")] | length' "$output_file" 2>/dev/null || echo "0")

    case $test_type in
        load)   duration_s=300 ;;
        soak)   duration_s=1800 ;;
        stress) duration_s=600 ;;
    esac

    req_sec=$(echo "scale=0; $total_reqs / $duration_s" | bc 2>/dev/null || echo "0")

    # Extract failure rate
    failure_rate=$(jq -s '[.[] | select(.type == "Point" and .data.name == "http_req_failed") | .data.value] | if length > 0 then (add / length * 100) else 0 end' "$output_file" 2>/dev/null || echo "0")

    # Store results
    LATENCY_P95[$runtime]="${latency_p95:-0}"
    REQ_PER_SEC[$runtime]="${req_sec:-0}"
    FAILURE_RATE[$runtime]="${failure_rate:-0}"

    log OK "${RUNTIME_NAMES[$runtime]}: p95=${latency_p95}ms, Req/Sec=${req_sec}, Failures=${failure_rate}%"
}

#######################################
# Convert time string to milliseconds
#######################################
convert_to_ms() {
    local value="$1"
    [[ -z "$value" ]] && echo "0" && return

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
# Find best runtime for a metric
# Arguments:
#   $1 - metric type (latency_lower_better, throughput_higher_better)
#######################################
find_winner() {
    local metric_type="$1"
    local -n metric_array="$2"
    local winner=""
    local best_value=""

    for runtime in "${!metric_array[@]}"; do
        [[ "${AVAILABLE_RUNTIMES[$runtime]:-0}" != "1" ]] && continue

        local value="${metric_array[$runtime]}"
        [[ -z "$value" || "$value" == "0" ]] && continue

        if [[ -z "$best_value" ]]; then
            best_value="$value"
            winner="$runtime"
        else
            local is_better
            if [[ "$metric_type" == "lower" ]]; then
                is_better=$(echo "$value < $best_value" | bc -l)
            else
                is_better=$(echo "$value > $best_value" | bc -l)
            fi

            if [[ "$is_better" == "1" ]]; then
                best_value="$value"
                winner="$runtime"
            fi
        fi
    done

    echo "$winner"
}

#######################################
# Calculate percentage difference
#######################################
calc_diff() {
    local base="$1"
    local compare="$2"

    if [[ -z "$base" || "$base" == "0" ]]; then
        echo "N/A"
        return
    fi

    local diff
    diff=$(echo "scale=1; (($compare - $base) / $base) * 100" | bc 2>/dev/null || echo "N/A")

    if [[ "$diff" != "N/A" ]]; then
        local sign=""
        if [[ $(echo "$diff > 0" | bc) == "1" ]]; then
            sign="+"
        fi
        echo "${sign}${diff}%"
    else
        echo "N/A"
    fi
}

#######################################
# Generate comparison report
#######################################
generate_report() {
    local test_type="$1"
    local report_file="${RESULTS_DIR}/comparison-${test_type}-${TIMESTAMP}.txt"

    # Find winners
    local latency_winner throughput_winner
    latency_winner=$(find_winner "lower" LATENCY_P95)
    throughput_winner=$(find_winner "higher" REQ_PER_SEC)

    # Generate report
    {
        echo "=============================================="
        echo "  Performance Comparison Report"
        echo "=============================================="
        echo ""
        echo "Date:      $(date '+%Y-%m-%d %H:%M:%S')"
        echo "Test Type: $test_type"
        echo "Duration:  $(case $test_type in quick) echo '30s';; load) echo '5min';; soak) echo '30min';; stress) echo '10min';; esac)"
        echo ""
        echo "----------------------------------------------"
        printf "%-12s | %12s | %12s | %10s | %s\n" "Runtime" "Latency p95" "Req/Sec" "Failures" "Winner"
        echo "----------------------------------------------"

        for runtime in fpm rr frank; do
            [[ "${AVAILABLE_RUNTIMES[$runtime]:-0}" != "1" ]] && continue

            local name="${RUNTIME_NAMES[$runtime]}"
            local latency="${LATENCY_P95[$runtime]:-N/A}"
            local req="${REQ_PER_SEC[$runtime]:-N/A}"
            local fail="${FAILURE_RATE[$runtime]:-N/A}"

            local winner_mark=""
            if [[ "$runtime" == "$latency_winner" && "$runtime" == "$throughput_winner" ]]; then
                winner_mark="★★"
            elif [[ "$runtime" == "$latency_winner" || "$runtime" == "$throughput_winner" ]]; then
                winner_mark="★"
            fi

            printf "%-12s | %10sms | %12s | %9s%% | %s\n" "$name" "$latency" "$req" "$fail" "$winner_mark"
        done

        echo "----------------------------------------------"
        echo ""
        echo "Legend: ★★ = Best in both metrics, ★ = Best in one metric"
        echo ""

        # Determine overall winner
        if [[ -n "$latency_winner" && "$latency_winner" == "$throughput_winner" ]]; then
            echo -e "Best Overall: ${RUNTIME_NAMES[$latency_winner]} (lowest latency, highest throughput)"
        elif [[ -n "$latency_winner" && -n "$throughput_winner" ]]; then
            echo "Best Latency:    ${RUNTIME_NAMES[$latency_winner]}"
            echo "Best Throughput: ${RUNTIME_NAMES[$throughput_winner]}"
        fi
        echo ""

        # Percentage comparisons vs RoadRunner (baseline)
        if [[ "${AVAILABLE_RUNTIMES[rr]:-0}" == "1" ]]; then
            echo "----------------------------------------------"
            echo "Comparison vs RoadRunner (baseline)"
            echo "----------------------------------------------"

            local rr_latency="${LATENCY_P95[rr]}"
            local rr_req="${REQ_PER_SEC[rr]}"

            for runtime in fpm frank; do
                [[ "${AVAILABLE_RUNTIMES[$runtime]:-0}" != "1" ]] && continue

                local lat_diff req_diff
                lat_diff=$(calc_diff "$rr_latency" "${LATENCY_P95[$runtime]}")
                req_diff=$(calc_diff "$rr_req" "${REQ_PER_SEC[$runtime]}")

                echo "${RUNTIME_NAMES[$runtime]}:"
                echo "  Latency:    $lat_diff"
                echo "  Throughput: $req_diff"
            done
        fi

        echo ""
        echo "=============================================="
    } | tee "$report_file"

    log OK "Report saved: $report_file"
}

#######################################
# Generate JSON report
#######################################
generate_json_report() {
    local test_type="$1"
    local json_file="${RESULTS_DIR}/comparison-${test_type}-${TIMESTAMP}.json"

    local latency_winner throughput_winner
    latency_winner=$(find_winner "lower" LATENCY_P95)
    throughput_winner=$(find_winner "higher" REQ_PER_SEC)

    cat << EOF > "$json_file"
{
    "timestamp": "$(date -Iseconds)",
    "test_type": "$test_type",
    "results": {
EOF

    local first=true
    for runtime in fpm rr frank; do
        [[ "${AVAILABLE_RUNTIMES[$runtime]:-0}" != "1" ]] && continue

        if [[ "$first" != "true" ]]; then
            echo "," >> "$json_file"
        fi
        first=false

        cat << EOF >> "$json_file"
        "$runtime": {
            "name": "${RUNTIME_NAMES[$runtime]}",
            "latency_p95_ms": ${LATENCY_P95[$runtime]:-0},
            "requests_per_sec": ${REQ_PER_SEC[$runtime]:-0},
            "failure_rate_pct": ${FAILURE_RATE[$runtime]:-0}
        }
EOF
    done

    cat << EOF >> "$json_file"
    },
    "winners": {
        "latency": "${latency_winner:-none}",
        "throughput": "${throughput_winner:-none}"
    }
}
EOF

    log OK "JSON report saved: $json_file"

    if [[ "${JSON_OUTPUT:-false}" == "true" ]]; then
        cat "$json_file"
    fi
}

#######################################
# Main
#######################################
main() {
    local test_type="quick"
    local output_file=""

    # Parse arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)     usage ;;
            -j|--json)     JSON_OUTPUT=true ;;
            -o|--output)   output_file="$2"; shift ;;
            --skip-check)  SKIP_CHECK=true ;;
            quick|load|soak|stress) test_type="$1" ;;
            *) log ERROR "Unknown option: $1"; usage ;;
        esac
        shift
    done

    echo ""
    echo -e "${BOLD}========================================${NC}"
    echo -e "${BOLD}  Performance Comparison: $test_type${NC}"
    echo -e "${BOLD}========================================${NC}"
    echo ""

    # Pre-flight
    check_dependencies "$test_type"
    mkdir -p "$RESULTS_DIR"

    if [[ "${SKIP_CHECK:-false}" != "true" ]]; then
        check_runtimes
    fi

    echo ""

    # Run tests
    for runtime in fpm rr frank; do
        [[ "${AVAILABLE_RUNTIMES[$runtime]:-0}" != "1" ]] && continue

        if [[ "$test_type" == "quick" ]]; then
            run_quick_test "$runtime"
        else
            run_k6_test "$runtime" "$test_type"
        fi
        echo ""
    done

    # Generate reports
    echo ""
    generate_report "$test_type"
    generate_json_report "$test_type"

    echo ""
    log OK "Comparison complete!"
}

main "$@"
