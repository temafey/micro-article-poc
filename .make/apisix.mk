# APISIX API Gateway Management
# Part of Phase 8: API Gateway Modernization (TASK-023)
#
# Makefile targets for managing Apache APISIX in development.
# Usage: make apisix-[target]

# ============================================================================
# Configuration
# ============================================================================

APISIX_COMPOSE_FILES := -f docker-compose.yml -f docker-compose.apisix.yml
APISIX_ADMIN_URL := http://localhost:9181
APISIX_GATEWAY_URL := http://localhost:9080
APISIX_DASHBOARD_URL := http://localhost:9000
APISIX_ADMIN_KEY := edd1c9f034335f136f87ad84b625c8f1

# Runtime overlay files
APISIX_FPM_COMPOSE := $(APISIX_COMPOSE_FILES) -f docker-compose.fpm.yml -f docker-compose.workers.yml
APISIX_RR_COMPOSE := $(APISIX_COMPOSE_FILES) -f docker-compose.rr.yml -f docker-compose.workers.yml
APISIX_FRANK_COMPOSE := $(APISIX_COMPOSE_FILES) -f docker-compose.frank.yml -f docker-compose.workers.yml

# ============================================================================
# Lifecycle Commands
# ============================================================================

.PHONY: apisix-up
apisix-up: ## Start APISIX + etcd (without PHP runtime)
	@echo "Starting APISIX API Gateway..."
	docker compose $(APISIX_COMPOSE_FILES) up -d
	@echo "APISIX Dashboard: $(APISIX_DASHBOARD_URL)"
	@echo "APISIX Gateway:   $(APISIX_GATEWAY_URL)"
	@echo "APISIX Admin API: $(APISIX_ADMIN_URL)"

.PHONY: apisix-down
apisix-down: ## Stop APISIX + etcd
	@echo "Stopping APISIX API Gateway..."
	docker compose $(APISIX_COMPOSE_FILES) down

.PHONY: apisix-restart
apisix-restart: ## Restart APISIX + etcd
	@echo "Restarting APISIX API Gateway..."
	docker compose $(APISIX_COMPOSE_FILES) restart

.PHONY: apisix-logs
apisix-logs: ## View APISIX logs
	docker compose $(APISIX_COMPOSE_FILES) logs -f test-micro-article-system-apisix

.PHONY: apisix-status
apisix-status: ## Show APISIX container status
	docker compose $(APISIX_COMPOSE_FILES) ps

# ============================================================================
# Full Stack Commands (APISIX + PHP Runtime)
# ============================================================================

.PHONY: apisix-fpm-up
apisix-fpm-up: ## Start full stack: APISIX + PHP-FPM
	@echo "Starting APISIX + PHP-FPM stack..."
	docker compose $(APISIX_FPM_COMPOSE) up -d
	@echo ""
	@echo "Stack is ready!"
	@echo "  Gateway:   $(APISIX_GATEWAY_URL)"
	@echo "  Dashboard: $(APISIX_DASHBOARD_URL)"
	@echo "  API:       $(APISIX_GATEWAY_URL)/api/v1/article"

.PHONY: apisix-fpm-down
apisix-fpm-down: ## Stop full stack: APISIX + PHP-FPM
	docker compose $(APISIX_FPM_COMPOSE) down

.PHONY: apisix-rr-up
apisix-rr-up: ## Start full stack: APISIX + RoadRunner
	@echo "Starting APISIX + RoadRunner stack..."
	docker compose $(APISIX_RR_COMPOSE) up -d
	@echo ""
	@echo "Stack is ready!"
	@echo "  Gateway:   $(APISIX_GATEWAY_URL)"
	@echo "  Dashboard: $(APISIX_DASHBOARD_URL)"
	@echo "  API:       $(APISIX_GATEWAY_URL)/api/v1/article"

.PHONY: apisix-rr-down
apisix-rr-down: ## Stop full stack: APISIX + RoadRunner
	docker compose $(APISIX_RR_COMPOSE) down

.PHONY: apisix-frank-up
apisix-frank-up: ## Start full stack: APISIX + FrankenPHP
	@echo "Starting APISIX + FrankenPHP stack..."
	docker compose $(APISIX_FRANK_COMPOSE) up -d
	@echo ""
	@echo "Stack is ready!"
	@echo "  Gateway:   $(APISIX_GATEWAY_URL)"
	@echo "  Dashboard: $(APISIX_DASHBOARD_URL)"
	@echo "  API:       $(APISIX_GATEWAY_URL)/api/v1/article"

.PHONY: apisix-frank-down
apisix-frank-down: ## Stop full stack: APISIX + FrankenPHP
	docker compose $(APISIX_FRANK_COMPOSE) down

# ============================================================================
# Health & Diagnostics
# ============================================================================

.PHONY: apisix-health
apisix-health: ## Check APISIX health status
	@echo "Checking APISIX health..."
	@curl -s $(APISIX_GATEWAY_URL)/apisix/health | jq . 2>/dev/null || \
		curl -s $(APISIX_GATEWAY_URL)/apisix/health
	@echo ""
	@echo "Checking etcd health..."
	@docker compose $(APISIX_COMPOSE_FILES) exec test-micro-article-system-etcd etcdctl endpoint health

.PHONY: apisix-routes
apisix-routes: ## List all configured routes
	@echo "Fetching routes from APISIX Admin API..."
	@curl -s -H "X-API-KEY: $(APISIX_ADMIN_KEY)" \
		$(APISIX_ADMIN_URL)/apisix/admin/routes | jq '.list[].value | {id, name, uri, methods}'

.PHONY: apisix-upstreams
apisix-upstreams: ## List all configured upstreams
	@echo "Fetching upstreams from APISIX Admin API..."
	@curl -s -H "X-API-KEY: $(APISIX_ADMIN_KEY)" \
		$(APISIX_ADMIN_URL)/apisix/admin/upstreams | jq '.list[].value | {id, name, nodes}'

.PHONY: apisix-services
apisix-services: ## List all configured services
	@echo "Fetching services from APISIX Admin API..."
	@curl -s -H "X-API-KEY: $(APISIX_ADMIN_KEY)" \
		$(APISIX_ADMIN_URL)/apisix/admin/services | jq '.list[].value | {id, name, upstream_id}'

.PHONY: apisix-plugins
apisix-plugins: ## List enabled plugins
	@echo "Fetching enabled plugins..."
	@curl -s -H "X-API-KEY: $(APISIX_ADMIN_KEY)" \
		$(APISIX_ADMIN_URL)/apisix/admin/plugins/list | jq .

# ============================================================================
# Configuration Management
# ============================================================================

.PHONY: apisix-reload
apisix-reload: ## Reload APISIX configuration
	@echo "Reloading APISIX configuration..."
	docker compose $(APISIX_COMPOSE_FILES) exec test-micro-article-system-apisix \
		apisix reload
	@echo "Configuration reloaded."

.PHONY: apisix-validate
apisix-validate: ## Validate APISIX configuration files
	@echo "Validating apisix/config.yaml..."
	@docker compose $(APISIX_COMPOSE_FILES) exec test-micro-article-system-apisix \
		apisix test 2>&1 || echo "Validation failed"

.PHONY: apisix-init-routes
apisix-init-routes: ## Initialize routes from apisix.yaml via Admin API
	@echo "Initializing routes from apisix.yaml..."
	@echo "Note: Routes are auto-loaded from apisix.yaml on startup."
	@echo "Use 'make apisix-routes' to verify."

# ============================================================================
# Metrics & Observability
# ============================================================================

.PHONY: apisix-metrics
apisix-metrics: ## Fetch Prometheus metrics from APISIX
	@echo "Fetching APISIX Prometheus metrics..."
	@curl -s http://localhost:9091/apisix/prometheus/metrics | head -50
	@echo ""
	@echo "... (truncated, full metrics at http://localhost:9091/apisix/prometheus/metrics)"

.PHONY: apisix-traffic
apisix-traffic: ## Show real-time traffic stats
	@echo "Real-time APISIX traffic (Ctrl+C to stop)..."
	@docker compose $(APISIX_COMPOSE_FILES) logs -f test-micro-article-system-apisix 2>&1 | \
		grep -E "(GET|POST|PUT|PATCH|DELETE|status)"

# ============================================================================
# Testing & Verification
# ============================================================================

.PHONY: apisix-test
apisix-test: ## Test API endpoints through APISIX gateway
	@echo "Testing APISIX gateway routing..."
	@echo ""
	@echo "1. Gateway Health:"
	@curl -s -w "\n   Status: %{http_code}\n" $(APISIX_GATEWAY_URL)/apisix/health
	@echo ""
	@echo "2. Article API v1 (GET /api/v1/article):"
	@curl -s -w "\n   Status: %{http_code}\n" $(APISIX_GATEWAY_URL)/api/v1/article | head -c 200
	@echo "..."
	@echo ""
	@echo "3. Article API v2 (GET /api/v2/article):"
	@curl -s -w "\n   Status: %{http_code}\n" $(APISIX_GATEWAY_URL)/api/v2/article | head -c 200
	@echo "..."
	@echo ""
	@echo "4. Health Endpoint (GET /health):"
	@curl -s -w "\n   Status: %{http_code}\n" $(APISIX_GATEWAY_URL)/health

.PHONY: apisix-benchmark
apisix-benchmark: ## Run simple benchmark against APISIX
	@echo "Running benchmark against APISIX gateway..."
	@echo "Requires: ab (Apache Benchmark) or wrk"
	@if command -v wrk >/dev/null 2>&1; then \
		wrk -t4 -c100 -d10s $(APISIX_GATEWAY_URL)/api/v1/article; \
	elif command -v ab >/dev/null 2>&1; then \
		ab -n 1000 -c 50 $(APISIX_GATEWAY_URL)/api/v1/article; \
	else \
		echo "Install 'wrk' or 'ab' for benchmarking"; \
	fi

# ============================================================================
# Cleanup
# ============================================================================

.PHONY: apisix-clean
apisix-clean: ## Remove APISIX containers and volumes
	@echo "Cleaning up APISIX resources..."
	docker compose $(APISIX_COMPOSE_FILES) down -v
	@echo "APISIX resources cleaned."

.PHONY: apisix-reset
apisix-reset: apisix-clean apisix-up ## Reset APISIX (clean + start fresh)
	@echo "APISIX reset complete."

# ============================================================================
# Help
# ============================================================================

.PHONY: apisix-help
apisix-help: ## Show APISIX-related targets
	@echo "APISIX API Gateway Management"
	@echo "=============================="
	@echo ""
	@echo "Lifecycle:"
	@echo "  apisix-up           Start APISIX + etcd"
	@echo "  apisix-down         Stop APISIX + etcd"
	@echo "  apisix-restart      Restart APISIX"
	@echo "  apisix-logs         View APISIX logs"
	@echo "  apisix-status       Show container status"
	@echo ""
	@echo "Full Stack (APISIX + PHP Runtime):"
	@echo "  apisix-fpm-up       Start with PHP-FPM"
	@echo "  apisix-rr-up        Start with RoadRunner"
	@echo "  apisix-frank-up     Start with FrankenPHP"
	@echo ""
	@echo "Diagnostics:"
	@echo "  apisix-health       Check health status"
	@echo "  apisix-routes       List routes"
	@echo "  apisix-upstreams    List upstreams"
	@echo "  apisix-services     List services"
	@echo "  apisix-metrics      Prometheus metrics"
	@echo ""
	@echo "Testing:"
	@echo "  apisix-test         Test API endpoints"
	@echo "  apisix-benchmark    Run benchmark"
	@echo ""
	@echo "URLs:"
	@echo "  Gateway:   $(APISIX_GATEWAY_URL)"
	@echo "  Dashboard: $(APISIX_DASHBOARD_URL)"
	@echo "  Admin API: $(APISIX_ADMIN_URL)"
