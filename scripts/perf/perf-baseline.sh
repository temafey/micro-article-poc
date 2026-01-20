#!/bin/bash
#
# perf-baseline.sh - Baseline management for performance metrics
#
# Purpose: Save, load, and compare baseline performance metrics for regression detection.
# Implements ADR-012 baseline management specification.
#
# Usage:
#   ./perf-baseline.sh save [RUNTIME]           # Save current metrics as baseline
#   ./perf-baseline.sh load [RUNTIME]           # Display saved baseline
#   ./perf-baseline.sh compare [RUNTIME]        # Compare current vs baseline
#   ./perf-baseline.sh list                     # List all baselines
#   ./perf-baseline.sh -h                       # Show help
#
# Exit Codes:
#   0 - Success / All metrics OK
#   1 - Regression detected
#   2 - Error (missing files, invalid data)
#
# @see ADR-012-performance-testing-strategy.md

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/../.." && pwd)"
BASELINES_DIR="$PROJECT_ROOT/tests/performance/baselines"
RESULTS_DIR="$PROJECT_ROOT/tests/performance/results"

# Regression thresholds (percentage)
LATENCY_REGRESSION_THRESHOLD=10    # Flag if latency increases > 10%
THROUGHPUT_REGRESSION_THRESHOLD=10 # Flag if throughput decreases > 10%
FAILURE_REGRESSION_THRESHOLD=50    # Flag if failure rate increases > 50%

# Runtime configuration
declare -A RUNTIMES=(
    ["fpm"]="http://localhost:80"
    ["rr"]="http://localhost:8080"
    ["frank"]="http://localhost:8081"
)

declare -A RUNTIME_NAMES=(
    ["fpm"]="PHP-FPM"
    ["rr"]="RoadRunner"
    ["frank"]="FrankenPHP"
)

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m'

#######################################
# Display usage
#######################################
usage() {
    cat << EOF
Usage: $(basename "$0") COMMAND [RUNTIME] [OPTIONS]

Baseline management for performance metrics.

Commands:
    save [RUNTIME]      Save current metrics as baseline
    load [RUNTIME]      Display saved baseline
    compare [RUNTIME]   Compare current results vs baseline
    list                List all saved baselines
    delete [RUNTIME]    Delete a baseline

Options:
    -h, --help          Show this help message
    -f, --force         Force overwrite existing baseline
    --json              Output as JSON
    --ci                CI mode: exit with error on regression

Runtimes:
    fpm                 PHP-FPM baseline
    rr                  RoadRunner baseline (default)
    frank               FrankenPHP baseline
    all                 All runtimes

Regression Thresholds:
    Latency increase:   > ${LATENCY_REGRESSION_THRESHOLD}%
    Throughput decrease: > ${THROUGHPUT_REGRESSION_THRESHOLD}%
    Failure rate increase: > ${FAILURE_REGRESSION_THRESHOLD}%

Examples:
    # Save RoadRunner baseline
    ./perf-baseline.sh save rr

    # Compare current results against baseline
    ./perf-baseline.sh compare rr

    # Save all baselines
    ./perf-baseline.sh save all

    # CI mode - fail on regression
    ./perf-baseline.sh compare rr --ci

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
        INFO)   color=$BLUE ;;
        OK)     color=$GREEN ;;
        WARN)   color=$YELLOW ;;
        ERROR)  color=$RED ;;
        REGR)   color=$RED ;;
    esac
    echo -e "${color}[$level]${NC} $*"
}

#######################################
# Get latest results file for a runtime
#######################################
get_latest_results() {
    local runtime="$1"
    local pattern="${RESULTS_DIR}/${runtime}-summary-*.json"

    # Find most recent file
    local latest
    latest=$(ls -t $pattern 2>/dev/null | head -1)

    if [[ -z "$latest" || ! -f "$latest" ]]; then
        log ERROR "No results found for $runtime"
        log INFO "Run tests first: TARGET_URL=${RUNTIMES[$runtime]} ./wrk-baseline.sh"
        return 1
    fi

    echo "$latest"
}

#######################################
# Extract metrics from results file
#######################################
extract_metrics() {
    local results_file="$1"

    if ! jq empty "$results_file" 2>/dev/null; then
        log ERROR "Invalid JSON in $results_file"
        return 1
    fi

    # Extract and normalize metrics
    jq '{
        health_latency_avg: (.endpoints.health.latency_avg_ms // 0),
        health_latency_max: (.endpoints.health.latency_max_ms // 0),
        health_req_sec: (.endpoints.health.requests_per_sec // 0),
        article_latency_avg: (.endpoints.article.latency_avg_ms // 0),
        article_latency_max: (.endpoints.article.latency_max_ms // 0),
        article_req_sec: (.endpoints.article.requests_per_sec // 0)
    }' "$results_file"
}

#######################################
# Save baseline
#######################################
cmd_save() {
    local runtime="$1"
    local force="${FORCE:-false}"

    log INFO "Saving baseline for ${RUNTIME_NAMES[$runtime]}..."

    # Get latest results
    local results_file
    results_file=$(get_latest_results "$runtime") || exit 2

    log INFO "Using results from: $(basename "$results_file")"

    # Check if baseline exists
    local baseline_file="${BASELINES_DIR}/${runtime}-baseline.json"
    if [[ -f "$baseline_file" && "$force" != "true" ]]; then
        log WARN "Baseline already exists: $baseline_file"
        log INFO "Use --force to overwrite"
        return 1
    fi

    mkdir -p "$BASELINES_DIR"

    # Extract metrics and create baseline
    local metrics
    metrics=$(extract_metrics "$results_file") || exit 2

    # Create baseline with metadata
    jq -n \
        --arg runtime "$runtime" \
        --arg name "${RUNTIME_NAMES[$runtime]}" \
        --arg created "$(date -Iseconds)" \
        --arg source "$(basename "$results_file")" \
        --argjson metrics "$metrics" \
        '{
            runtime: $runtime,
            name: $name,
            created: $created,
            source: $source,
            version: "1.0",
            metrics: $metrics
        }' > "$baseline_file"

    log OK "Baseline saved: $baseline_file"

    # Display saved values
    echo ""
    echo "Saved metrics:"
    echo "$metrics" | jq -r 'to_entries[] | "  \(.key): \(.value)"'
}

#######################################
# Load baseline
#######################################
cmd_load() {
    local runtime="$1"
    local baseline_file="${BASELINES_DIR}/${runtime}-baseline.json"

    if [[ ! -f "$baseline_file" ]]; then
        log ERROR "No baseline found for $runtime"
        log INFO "Create with: ./perf-baseline.sh save $runtime"
        exit 2
    fi

    if [[ "${JSON_OUTPUT:-false}" == "true" ]]; then
        cat "$baseline_file"
    else
        echo ""
        echo -e "${BOLD}Baseline: ${RUNTIME_NAMES[$runtime]}${NC}"
        echo "========================================"

        local created source
        created=$(jq -r '.created' "$baseline_file")
        source=$(jq -r '.source' "$baseline_file")

        echo "Created: $created"
        echo "Source:  $source"
        echo ""
        echo "Metrics:"
        jq -r '.metrics | to_entries[] | "  \(.key): \(.value)"' "$baseline_file"
        echo ""
    fi
}

#######################################
# Compare current vs baseline
#######################################
cmd_compare() {
    local runtime="$1"
    local ci_mode="${CI_MODE:-false}"
    local regression_found=false

    log INFO "Comparing ${RUNTIME_NAMES[$runtime]} against baseline..."

    # Load baseline
    local baseline_file="${BASELINES_DIR}/${runtime}-baseline.json"
    if [[ ! -f "$baseline_file" ]]; then
        log ERROR "No baseline found for $runtime"
        log INFO "Create with: ./perf-baseline.sh save $runtime"
        exit 2
    fi

    # Get current results
    local results_file
    results_file=$(get_latest_results "$runtime") || exit 2

    # Extract metrics
    local baseline_metrics current_metrics
    baseline_metrics=$(jq '.metrics' "$baseline_file")
    current_metrics=$(extract_metrics "$results_file")

    echo ""
    echo -e "${BOLD}========================================${NC}"
    echo -e "${BOLD}  Baseline Comparison: ${RUNTIME_NAMES[$runtime]}${NC}"
    echo -e "${BOLD}========================================${NC}"
    echo ""
    echo "Baseline: $(jq -r '.created' "$baseline_file")"
    echo "Current:  $(basename "$results_file")"
    echo ""
    echo "----------------------------------------------"
    printf "%-22s | %10s | %10s | %8s | %s\n" "Metric" "Baseline" "Current" "Diff" "Status"
    echo "----------------------------------------------"

    # Compare each metric
    local metrics=("health_latency_avg" "health_req_sec" "article_latency_avg" "article_req_sec")
    local metric_types=("latency" "throughput" "latency" "throughput")

    for i in "${!metrics[@]}"; do
        local metric="${metrics[$i]}"
        local metric_type="${metric_types[$i]}"

        local baseline_val current_val
        baseline_val=$(echo "$baseline_metrics" | jq -r ".${metric} // 0")
        current_val=$(echo "$current_metrics" | jq -r ".${metric} // 0")

        # Calculate difference
        local diff_pct status status_color

        if [[ "$baseline_val" == "0" ]]; then
            diff_pct="N/A"
            status="OK"
            status_color=$GREEN
        else
            diff_pct=$(echo "scale=1; (($current_val - $baseline_val) / $baseline_val) * 100" | bc)

            # Determine status based on metric type
            if [[ "$metric_type" == "latency" ]]; then
                # Higher latency is bad
                if (( $(echo "$diff_pct > $LATENCY_REGRESSION_THRESHOLD" | bc -l) )); then
                    status="REGRESSION"
                    status_color=$RED
                    regression_found=true
                elif (( $(echo "$diff_pct > 5" | bc -l) )); then
                    status="WARNING"
                    status_color=$YELLOW
                elif (( $(echo "$diff_pct < -5" | bc -l) )); then
                    status="IMPROVED"
                    status_color=$GREEN
                else
                    status="OK"
                    status_color=$GREEN
                fi
            else
                # Lower throughput is bad
                if (( $(echo "$diff_pct < -$THROUGHPUT_REGRESSION_THRESHOLD" | bc -l) )); then
                    status="REGRESSION"
                    status_color=$RED
                    regression_found=true
                elif (( $(echo "$diff_pct < -5" | bc -l) )); then
                    status="WARNING"
                    status_color=$YELLOW
                elif (( $(echo "$diff_pct > 5" | bc -l) )); then
                    status="IMPROVED"
                    status_color=$GREEN
                else
                    status="OK"
                    status_color=$GREEN
                fi
            fi

            # Format diff with sign
            if (( $(echo "$diff_pct > 0" | bc -l) )); then
                diff_pct="+${diff_pct}%"
            else
                diff_pct="${diff_pct}%"
            fi
        fi

        printf "%-22s | %10s | %10s | %8s | ${status_color}%s${NC}\n" \
            "$metric" "$baseline_val" "$current_val" "$diff_pct" "$status"
    done

    echo "----------------------------------------------"
    echo ""

    # Summary
    if [[ "$regression_found" == "true" ]]; then
        echo -e "${RED}${BOLD}⚠ REGRESSION DETECTED${NC}"
        echo ""
        echo "Thresholds exceeded:"
        echo "  - Latency increase > ${LATENCY_REGRESSION_THRESHOLD}%"
        echo "  - Throughput decrease > ${THROUGHPUT_REGRESSION_THRESHOLD}%"

        if [[ "$ci_mode" == "true" ]]; then
            log ERROR "CI mode: Exiting with error due to regression"
            exit 1
        fi
    else
        echo -e "${GREEN}${BOLD}✓ All metrics within acceptable range${NC}"
    fi

    echo ""
}

#######################################
# List baselines
#######################################
cmd_list() {
    echo ""
    echo -e "${BOLD}Saved Baselines${NC}"
    echo "========================================"

    local found=false
    for runtime in fpm rr frank; do
        local baseline_file="${BASELINES_DIR}/${runtime}-baseline.json"
        if [[ -f "$baseline_file" ]]; then
            found=true
            local created name
            created=$(jq -r '.created' "$baseline_file")
            name=$(jq -r '.name' "$baseline_file")
            echo "  [$runtime] $name - $created"
        fi
    done

    if [[ "$found" != "true" ]]; then
        echo "  No baselines found."
        echo ""
        echo "  Create with: ./perf-baseline.sh save [RUNTIME]"
    fi

    echo ""
}

#######################################
# Delete baseline
#######################################
cmd_delete() {
    local runtime="$1"
    local baseline_file="${BASELINES_DIR}/${runtime}-baseline.json"

    if [[ ! -f "$baseline_file" ]]; then
        log WARN "No baseline found for $runtime"
        return 0
    fi

    rm -f "$baseline_file"
    log OK "Deleted baseline: $baseline_file"
}

#######################################
# Main
#######################################
main() {
    local command=""
    local runtime="rr"

    # Parse arguments
    while [[ $# -gt 0 ]]; do
        case $1 in
            -h|--help)   usage ;;
            -f|--force)  FORCE=true ;;
            --json)      JSON_OUTPUT=true ;;
            --ci)        CI_MODE=true ;;
            save|load|compare|list|delete)
                command="$1"
                ;;
            fpm|rr|frank|all)
                runtime="$1"
                ;;
            *)
                log ERROR "Unknown argument: $1"
                usage
                ;;
        esac
        shift
    done

    if [[ -z "$command" ]]; then
        log ERROR "No command specified"
        usage
    fi

    # Validate runtime
    if [[ "$runtime" != "all" && ! -v "RUNTIMES[$runtime]" ]]; then
        log ERROR "Unknown runtime: $runtime"
        log INFO "Valid runtimes: fpm, rr, frank, all"
        exit 2
    fi

    # Execute command
    case $command in
        save)
            if [[ "$runtime" == "all" ]]; then
                for rt in fpm rr frank; do
                    cmd_save "$rt" || true
                    echo ""
                done
            else
                cmd_save "$runtime"
            fi
            ;;
        load)
            if [[ "$runtime" == "all" ]]; then
                for rt in fpm rr frank; do
                    cmd_load "$rt" 2>/dev/null || true
                done
            else
                cmd_load "$runtime"
            fi
            ;;
        compare)
            if [[ "$runtime" == "all" ]]; then
                local any_regression=false
                for rt in fpm rr frank; do
                    if [[ -f "${BASELINES_DIR}/${rt}-baseline.json" ]]; then
                        cmd_compare "$rt" || any_regression=true
                    fi
                done
                if [[ "$any_regression" == "true" && "${CI_MODE:-false}" == "true" ]]; then
                    exit 1
                fi
            else
                cmd_compare "$runtime"
            fi
            ;;
        list)
            cmd_list
            ;;
        delete)
            if [[ "$runtime" == "all" ]]; then
                for rt in fpm rr frank; do
                    cmd_delete "$rt"
                done
            else
                cmd_delete "$runtime"
            fi
            ;;
    esac
}

main "$@"
