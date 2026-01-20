#!/bin/bash
# FrankenPHP vs nginx+PHP-FPM Benchmark Script
# Compares performance between the two deployment options
#
# Requirements:
#   - Apache Bench (ab): apt-get install apache2-utils
#   - Both docker-compose configurations must be available
#   - Database and Redis should be running
#
# Usage:
#   ./scripts/benchmark-frankenphp.sh [requests] [concurrency]
#
# Example:
#   ./scripts/benchmark-frankenphp.sh 1000 50

set -euo pipefail

# Configuration
REQUESTS="${1:-1000}"
CONCURRENCY="${2:-50}"
WARMUP_REQUESTS=100
API_ENDPOINT="/api/v1/article"
RESULTS_DIR="./benchmark-results"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check dependencies
check_dependencies() {
    if ! command -v ab &> /dev/null; then
        echo -e "${RED}Error: Apache Bench (ab) is not installed${NC}"
        echo "Install with: apt-get install apache2-utils (Debian/Ubuntu)"
        echo "           or: brew install httpd (macOS)"
        exit 1
    fi

    if ! command -v docker &> /dev/null; then
        echo -e "${RED}Error: Docker is not installed${NC}"
        exit 1
    fi
}

# Create results directory
setup_results_dir() {
    mkdir -p "${RESULTS_DIR}"
    echo -e "${BLUE}Results will be saved to: ${RESULTS_DIR}/${NC}"
}

# Wait for service to be healthy
wait_for_service() {
    local url=$1
    local max_attempts=30
    local attempt=0

    echo -n "Waiting for service at ${url}..."
    while [ $attempt -lt $max_attempts ]; do
        if curl -sf "${url}/health" > /dev/null 2>&1; then
            echo -e " ${GREEN}Ready${NC}"
            return 0
        fi
        echo -n "."
        sleep 1
        ((attempt++))
    done
    echo -e " ${RED}Failed${NC}"
    return 1
}

# Run warmup requests
warmup() {
    local url=$1
    echo -e "${YELLOW}Warming up with ${WARMUP_REQUESTS} requests...${NC}"
    ab -n ${WARMUP_REQUESTS} -c 10 "${url}${API_ENDPOINT}" > /dev/null 2>&1 || true
}

# Run benchmark and save results
run_benchmark() {
    local name=$1
    local url=$2
    local output_file="${RESULTS_DIR}/${name}_${TIMESTAMP}.txt"

    echo -e "${BLUE}Running benchmark: ${name}${NC}"
    echo "  Requests: ${REQUESTS}, Concurrency: ${CONCURRENCY}"
    echo "  URL: ${url}${API_ENDPOINT}"

    # Run Apache Bench
    ab -n "${REQUESTS}" \
       -c "${CONCURRENCY}" \
       -H "Accept: application/json" \
       -H "Content-Type: application/json" \
       "${url}${API_ENDPOINT}" 2>&1 | tee "${output_file}"

    echo -e "${GREEN}Results saved to: ${output_file}${NC}"
    echo ""
}

# Extract key metrics from ab output
extract_metrics() {
    local file=$1

    local rps=$(grep "Requests per second" "${file}" | awk '{print $4}')
    local time_per_request=$(grep "Time per request" "${file}" | head -1 | awk '{print $4}')
    local transfer_rate=$(grep "Transfer rate" "${file}" | awk '{print $3}')
    local failed=$(grep "Failed requests" "${file}" | awk '{print $3}')
    local p50=$(grep "50%" "${file}" | awk '{print $2}')
    local p95=$(grep "95%" "${file}" | awk '{print $2}')
    local p99=$(grep "99%" "${file}" | awk '{print $2}')

    echo "${rps}|${time_per_request}|${transfer_rate}|${failed}|${p50}|${p95}|${p99}"
}

# Compare results
compare_results() {
    local nginx_file=$1
    local franken_file=$2

    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}         BENCHMARK COMPARISON           ${NC}"
    echo -e "${BLUE}========================================${NC}\n"

    # Extract metrics
    IFS='|' read -r nginx_rps nginx_tpr nginx_tr nginx_fail nginx_p50 nginx_p95 nginx_p99 <<< "$(extract_metrics "${nginx_file}")"
    IFS='|' read -r frank_rps frank_tpr frank_tr frank_fail frank_p50 frank_p95 frank_p99 <<< "$(extract_metrics "${franken_file}")"

    # Calculate improvement percentage
    if [ -n "${nginx_rps}" ] && [ -n "${frank_rps}" ]; then
        improvement=$(echo "scale=2; ((${frank_rps} - ${nginx_rps}) / ${nginx_rps}) * 100" | bc)
    else
        improvement="N/A"
    fi

    printf "%-30s %15s %15s %15s\n" "Metric" "nginx+PHP-FPM" "FrankenPHP" "Improvement"
    printf "%-30s %15s %15s %15s\n" "------------------------------" "---------------" "---------------" "---------------"
    printf "%-30s %15s %15s %15s%%\n" "Requests/sec" "${nginx_rps:-N/A}" "${frank_rps:-N/A}" "${improvement}"
    printf "%-30s %15s %15s\n" "Time per request (ms)" "${nginx_tpr:-N/A}" "${frank_tpr:-N/A}"
    printf "%-30s %15s %15s\n" "Transfer rate (KB/s)" "${nginx_tr:-N/A}" "${frank_tr:-N/A}"
    printf "%-30s %15s %15s\n" "Failed requests" "${nginx_fail:-N/A}" "${frank_fail:-N/A}"
    printf "%-30s %15s %15s\n" "Latency p50 (ms)" "${nginx_p50:-N/A}" "${frank_p50:-N/A}"
    printf "%-30s %15s %15s\n" "Latency p95 (ms)" "${nginx_p95:-N/A}" "${frank_p95:-N/A}"
    printf "%-30s %15s %15s\n" "Latency p99 (ms)" "${nginx_p99:-N/A}" "${frank_p99:-N/A}"

    echo ""

    # Summary
    if [ "${improvement}" != "N/A" ]; then
        if (( $(echo "${improvement} > 0" | bc -l) )); then
            echo -e "${GREEN}FrankenPHP is ${improvement}% faster than nginx+PHP-FPM${NC}"
        else
            echo -e "${YELLOW}nginx+PHP-FPM is $(echo "${improvement} * -1" | bc)% faster than FrankenPHP${NC}"
        fi
    fi
}

# Benchmark nginx+PHP-FPM
benchmark_nginx() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}    BENCHMARKING: nginx + PHP-FPM       ${NC}"
    echo -e "${BLUE}========================================${NC}\n"

    # Check if nginx compose is running
    if ! docker compose ps 2>/dev/null | grep -q "nginx.*running"; then
        echo -e "${YELLOW}Starting nginx+PHP-FPM stack...${NC}"
        docker compose up -d
        sleep 5
    fi

    local url="http://localhost"
    wait_for_service "${url}"
    warmup "${url}"
    run_benchmark "nginx_phpfpm" "${url}"
}

# Benchmark FrankenPHP
benchmark_frankenphp() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}       BENCHMARKING: FrankenPHP         ${NC}"
    echo -e "${BLUE}========================================${NC}\n"

    # Stop nginx stack if running
    docker compose down 2>/dev/null || true

    # Start FrankenPHP stack
    echo -e "${YELLOW}Starting FrankenPHP stack...${NC}"
    docker compose -f docker-compose.frankenphp.yml up -d
    sleep 5

    local url="http://localhost"
    wait_for_service "${url}"
    warmup "${url}"
    run_benchmark "frankenphp" "${url}"

    # Stop FrankenPHP and restore nginx
    docker compose -f docker-compose.frankenphp.yml down
}

# Quick benchmark (single stack)
quick_benchmark() {
    local stack=$1

    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}       QUICK BENCHMARK: ${stack}         ${NC}"
    echo -e "${BLUE}========================================${NC}\n"

    local url="http://localhost"
    wait_for_service "${url}"
    warmup "${url}"
    run_benchmark "${stack}" "${url}"
}

# Print usage
print_usage() {
    echo "Usage: $0 [command] [requests] [concurrency]"
    echo ""
    echo "Commands:"
    echo "  full      Run full comparison (nginx+PHP-FPM vs FrankenPHP)"
    echo "  nginx     Benchmark only nginx+PHP-FPM (must be running)"
    echo "  franken   Benchmark only FrankenPHP (must be running)"
    echo "  quick     Benchmark current running stack"
    echo ""
    echo "Options:"
    echo "  requests     Number of requests to perform (default: 1000)"
    echo "  concurrency  Number of concurrent requests (default: 50)"
    echo ""
    echo "Examples:"
    echo "  $0 full 5000 100      # Full comparison with 5000 requests, 100 concurrent"
    echo "  $0 quick 1000 50      # Quick benchmark of current stack"
    echo "  $0 nginx              # Benchmark nginx with default settings"
}

# Main
main() {
    local command="${1:-full}"

    # Shift if command is provided
    if [[ "${command}" =~ ^(full|nginx|franken|quick|help)$ ]]; then
        shift || true
    else
        command="full"
    fi

    # Update requests and concurrency if provided
    REQUESTS="${1:-${REQUESTS}}"
    CONCURRENCY="${2:-${CONCURRENCY}}"

    check_dependencies
    setup_results_dir

    case "${command}" in
        full)
            benchmark_nginx
            local nginx_result="${RESULTS_DIR}/nginx_phpfpm_${TIMESTAMP}.txt"
            benchmark_frankenphp
            local franken_result="${RESULTS_DIR}/frankenphp_${TIMESTAMP}.txt"
            compare_results "${nginx_result}" "${franken_result}"

            # Restore nginx stack
            echo -e "\n${YELLOW}Restoring nginx+PHP-FPM stack...${NC}"
            docker compose up -d
            ;;
        nginx)
            quick_benchmark "nginx_phpfpm"
            ;;
        franken)
            quick_benchmark "frankenphp"
            ;;
        quick)
            quick_benchmark "current"
            ;;
        help|--help|-h)
            print_usage
            exit 0
            ;;
        *)
            echo -e "${RED}Unknown command: ${command}${NC}"
            print_usage
            exit 1
            ;;
    esac

    echo -e "\n${GREEN}Benchmark complete!${NC}"
    echo "Results saved in: ${RESULTS_DIR}/"
}

main "$@"
