# Performance Testing Makefile targets
# Multi-runtime performance testing strategy (PHP-FPM, RoadRunner, FrankenPHP)
# See ADR-012-performance-testing-strategy.md for full documentation

# Docker compose commands for each runtime (use modular overlays from Makefile)
# These inherit from the main Makefile's DCC_FPM, DCC_RR, DCC_FRANK variables
# which combine: base infrastructure + runtime overlay + shared workers
DCC_PHPFPM = $(DCC_FPM)
DCC_ROADRUNNER = $(DCC_RR)
DCC_FRANKENPHP = $(DCC_FRANK)

# Performance results directory
PERF_RESULTS_DIR = $(CWD)tests/performance/results

# Ensure results directory exists
$(PERF_RESULTS_DIR):
	mkdir -p $(PERF_RESULTS_DIR)

# =============================================================================
# Quick Tests (wrk baseline - 30s each)
# =============================================================================

.PHONY: perf-quick-phpfpm
perf-quick-phpfpm: $(PERF_RESULTS_DIR) ## Quick performance test for nginx+PHP-FPM
	@echo "=== Quick Test: nginx+PHP-FPM ==="
	$(DCC_PHPFPM) up -d
	@echo "Waiting for warm-up (10s)..."
	@sleep 10
	@curl -sf http://localhost:80/health > /dev/null || echo "Health check failed"
	wrk -t4 -c100 -d30s --latency http://localhost:80/api/v1/article/ > $(PERF_RESULTS_DIR)/phpfpm-quick-$$(date +%Y%m%d-%H%M%S).txt 2>&1
	@cat $(PERF_RESULTS_DIR)/phpfpm-quick-*.txt | tail -20
	$(DCC_PHPFPM) down

.PHONY: perf-quick-roadrunner
perf-quick-roadrunner: $(PERF_RESULTS_DIR) ## Quick performance test for RoadRunner
	@echo "=== Quick Test: RoadRunner ==="
	$(DCC_ROADRUNNER) up -d
	@echo "Waiting for warm-up (10s)..."
	@sleep 10
	@curl -sf http://localhost:8080/health > /dev/null || echo "Health check failed"
	wrk -t4 -c100 -d30s --latency http://localhost:8080/api/v1/article/ > $(PERF_RESULTS_DIR)/roadrunner-quick-$$(date +%Y%m%d-%H%M%S).txt 2>&1
	@cat $(PERF_RESULTS_DIR)/roadrunner-quick-*.txt | tail -20
	$(DCC_ROADRUNNER) down

.PHONY: perf-quick-frankenphp
perf-quick-frankenphp: $(PERF_RESULTS_DIR) ## Quick performance test for FrankenPHP
	@echo "=== Quick Test: FrankenPHP ==="
	$(DCC_FRANKENPHP) up -d
	@echo "Waiting for warm-up (10s)..."
	@sleep 10
	@curl -sf http://localhost:8081/health > /dev/null || echo "Health check failed"
	wrk -t4 -c100 -d30s --latency http://localhost:8081/api/v1/article/ > $(PERF_RESULTS_DIR)/frankenphp-quick-$$(date +%Y%m%d-%H%M%S).txt 2>&1
	@cat $(PERF_RESULTS_DIR)/frankenphp-quick-*.txt | tail -20
	$(DCC_FRANKENPHP) down

.PHONY: perf-quick-all
perf-quick-all: perf-quick-phpfpm perf-quick-roadrunner perf-quick-frankenphp ## Run all quick tests sequentially
	@echo "=== All Quick Tests Complete ==="
	@./scripts/perf-compare.sh quick 2>/dev/null || echo "Comparison script not yet implemented"

# =============================================================================
# Load Tests (k6 - 5 minutes each)
# =============================================================================

.PHONY: perf-load-phpfpm
perf-load-phpfpm: $(PERF_RESULTS_DIR) ## K6 load test for nginx+PHP-FPM
	@echo "=== Load Test: nginx+PHP-FPM (5 minutes) ==="
	$(DCC_PHPFPM) up -d
	@sleep 10
	docker run --rm --network=$(DOCKER_NETWORK_NAME) \
		-v $(CWD)scripts/perf:/scripts \
		-e TARGET_URL=http://test-micro-article-system-nginx \
		grafana/k6 run /scripts/k6-load-test.js \
		--out json=/scripts/../tests/performance/results/phpfpm-load.json 2>&1 || true
	$(DCC_PHPFPM) down

.PHONY: perf-load-roadrunner
perf-load-roadrunner: $(PERF_RESULTS_DIR) ## K6 load test for RoadRunner
	@echo "=== Load Test: RoadRunner (5 minutes) ==="
	$(DCC_ROADRUNNER) up -d
	@sleep 10
	docker run --rm --network=$(DOCKER_NETWORK_NAME) \
		-v $(CWD)scripts/perf:/scripts \
		-e TARGET_URL=http://test-micro-article-system-http:8080 \
		grafana/k6 run /scripts/k6-load-test.js \
		--out json=/scripts/../tests/performance/results/roadrunner-load.json 2>&1 || true
	$(DCC_ROADRUNNER) down

.PHONY: perf-load-frankenphp
perf-load-frankenphp: $(PERF_RESULTS_DIR) ## K6 load test for FrankenPHP
	@echo "=== Load Test: FrankenPHP (5 minutes) ==="
	$(DCC_FRANKENPHP) up -d
	@sleep 10
	docker run --rm --network=$(DOCKER_NETWORK_NAME) \
		-v $(CWD)scripts/perf:/scripts \
		-e TARGET_URL=http://test-micro-article-system-http:80 \
		grafana/k6 run /scripts/k6-load-test.js \
		--out json=/scripts/../tests/performance/results/frankenphp-load.json 2>&1 || true
	$(DCC_FRANKENPHP) down

.PHONY: perf-load-all
perf-load-all: perf-load-phpfpm perf-load-roadrunner perf-load-frankenphp ## Run all load tests sequentially
	@echo "=== All Load Tests Complete ==="
	@./scripts/perf-compare.sh load 2>/dev/null || echo "Comparison script not yet implemented"

# =============================================================================
# Soak Tests (k6 - 30 minutes each)
# =============================================================================

.PHONY: perf-soak-roadrunner
perf-soak-roadrunner: $(PERF_RESULTS_DIR) ## K6 soak test for RoadRunner (30 minutes)
	@echo "=== Soak Test: RoadRunner (30 minutes) ==="
	$(DCC_ROADRUNNER) up -d
	@sleep 10
	docker run --rm --network=$(DOCKER_NETWORK_NAME) \
		-v $(CWD)scripts/perf:/scripts \
		-e TARGET_URL=http://test-micro-article-system-http:8080 \
		grafana/k6 run /scripts/k6-soak-test.js \
		--out json=/scripts/../tests/performance/results/roadrunner-soak.json 2>&1 || true
	$(DCC_ROADRUNNER) down

.PHONY: perf-soak-frankenphp
perf-soak-frankenphp: $(PERF_RESULTS_DIR) ## K6 soak test for FrankenPHP (30 minutes)
	@echo "=== Soak Test: FrankenPHP (30 minutes) ==="
	$(DCC_FRANKENPHP) up -d
	@sleep 10
	docker run --rm --network=$(DOCKER_NETWORK_NAME) \
		-v $(CWD)scripts/perf:/scripts \
		-e TARGET_URL=http://test-micro-article-system-http:80 \
		grafana/k6 run /scripts/k6-soak-test.js \
		--out json=/scripts/../tests/performance/results/frankenphp-soak.json 2>&1 || true
	$(DCC_FRANKENPHP) down

.PHONY: perf-soak-all
perf-soak-all: perf-soak-roadrunner perf-soak-frankenphp ## Run soak tests for persistent runtimes
	@echo "=== All Soak Tests Complete ==="

# =============================================================================
# Stress Tests (k6 - 10 minutes each)
# =============================================================================

.PHONY: perf-stress-all
perf-stress-all: $(PERF_RESULTS_DIR) ## Run stress tests for all runtimes
	@echo "=== Stress Tests (All Runtimes) ==="
	@echo "Stress test script not yet implemented"
	@echo "See ADR-012 for stress test k6 script"

# =============================================================================
# Comparison and Reporting
# =============================================================================

.PHONY: perf-compare
perf-compare: ## Generate comparison report from existing results
	@./scripts/perf-compare.sh all 2>/dev/null || echo "Comparison script not yet implemented - see ADR-012"

.PHONY: perf-baseline-update
perf-baseline-update: ## Update baseline metrics from current results
	@./scripts/perf-baseline.sh update 2>/dev/null || echo "Baseline script not yet implemented - see ADR-012"

.PHONY: perf-report
perf-report: ## Show latest performance results
	@echo "=== Latest Performance Results ==="
	@ls -la $(PERF_RESULTS_DIR)/*.txt 2>/dev/null || echo "No results found. Run perf-quick-all first."
	@echo ""
	@for f in $(PERF_RESULTS_DIR)/*-quick-*.txt; do \
		if [ -f "$$f" ]; then \
			echo "=== $$(basename $$f) ==="; \
			tail -15 "$$f"; \
			echo ""; \
		fi \
	done 2>/dev/null || true

# =============================================================================
# Resource Monitoring
# =============================================================================

.PHONY: perf-resources-phpfpm
perf-resources-phpfpm: ## Show PHP-FPM container resource usage
	@echo "=== PHP-FPM Container Resources ==="
	docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}" \
		$$(docker ps --filter "name=test-micro-article-system" --format "{{.Names}}") 2>/dev/null || \
		echo "No containers running. Start with 'make start'"

.PHONY: perf-resources-roadrunner
perf-resources-roadrunner: ## Show RoadRunner container resource usage
	@echo "=== RoadRunner Container Resources ==="
	docker stats --no-stream --format "table {{.Name}}\t{{.CPUPerc}}\t{{.MemUsage}}" \
		$$(docker ps --filter "name=test-micro-article-system-roadrunner" --format "{{.Names}}") 2>/dev/null || \
		echo "No containers running. Start with 'make start-roadrunner'"

# =============================================================================
# Help
# =============================================================================

.PHONY: perf-help
perf-help: ## Show performance testing help
	@echo "=== Multi-Runtime Performance Testing ==="
	@echo ""
	@echo "Quick Tests (30s each, wrk):"
	@echo "  make perf-quick-phpfpm      - Test nginx+PHP-FPM"
	@echo "  make perf-quick-roadrunner  - Test RoadRunner"
	@echo "  make perf-quick-frankenphp  - Test FrankenPHP"
	@echo "  make perf-quick-all         - Test all runtimes"
	@echo ""
	@echo "Load Tests (5min each, k6):"
	@echo "  make perf-load-phpfpm       - Load test PHP-FPM"
	@echo "  make perf-load-roadrunner   - Load test RoadRunner"
	@echo "  make perf-load-frankenphp   - Load test FrankenPHP"
	@echo "  make perf-load-all          - Load test all runtimes"
	@echo ""
	@echo "Soak Tests (30min each, k6 - memory leak detection):"
	@echo "  make perf-soak-roadrunner   - Soak test RoadRunner"
	@echo "  make perf-soak-frankenphp   - Soak test FrankenPHP"
	@echo "  make perf-soak-all          - Soak test all persistent runtimes"
	@echo ""
	@echo "Reports:"
	@echo "  make perf-report            - Show latest results"
	@echo "  make perf-compare           - Generate comparison report"
	@echo "  make perf-baseline-update   - Update baseline metrics"
	@echo ""
	@echo "Resources:"
	@echo "  make perf-resources-phpfpm     - Show PHP-FPM container stats"
	@echo "  make perf-resources-roadrunner - Show RoadRunner container stats"
	@echo ""
	@echo "See docs/adr/ADR-012-performance-testing-strategy.md for details"
