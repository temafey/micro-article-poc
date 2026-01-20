# =============================================================================
# OpenTelemetry Observability Stack Makefile Module (TASK-034)
# =============================================================================
# Provides Makefile targets for managing the grafana/otel-lgtm observability
# stack for development environments.
#
# Related: ADR-014-observability-stack-modernization.md
#          docker-compose.observability-dev.yml
# =============================================================================

# Docker Compose command for observability stack
DCC_OTEL = docker-compose -f $(CWD)docker-compose.observability-dev.yml --project-directory $(CWD)

# =============================================================================
# Observability Stack Management
# =============================================================================

.PHONY: otel-start
otel-start: ## Start OpenTelemetry observability stack (grafana/otel-lgtm)
	@echo "Starting OpenTelemetry observability stack..."
	$(DCC_OTEL) up -d
	@echo ""
	@echo "Observability stack started successfully!"
	@echo "  Grafana UI:    http://localhost:$${APP_GRAFANA_EXT_PORT:-3000}"
	@echo "  OTLP gRPC:     localhost:$${APP_OTLP_GRPC_EXT_PORT:-4317}"
	@echo "  OTLP HTTP:     localhost:$${APP_OTLP_HTTP_EXT_PORT:-4318}"
	@echo ""

.PHONY: otel-stop
otel-stop: ## Stop OpenTelemetry observability stack
	@echo "Stopping OpenTelemetry observability stack..."
	$(DCC_OTEL) stop

.PHONY: otel-down
otel-down: ## Stop and remove OpenTelemetry observability containers
	@echo "Stopping and removing OpenTelemetry observability stack..."
	$(DCC_OTEL) down

.PHONY: otel-restart
otel-restart: otel-stop otel-start ## Restart OpenTelemetry observability stack

.PHONY: otel-logs
otel-logs: ## View OpenTelemetry observability stack logs
	$(DCC_OTEL) logs -f

.PHONY: otel-status
otel-status: ## Show OpenTelemetry observability stack status
	@echo "OpenTelemetry Observability Stack Status:"
	@echo "=========================================="
	$(DCC_OTEL) ps
	@echo ""
	@echo "Health Check Endpoints:"
	@echo "  Grafana:   curl -s http://localhost:$${APP_GRAFANA_EXT_PORT:-3000}/api/health"
	@echo "  OTLP HTTP: curl -s http://localhost:$${APP_OTLP_HTTP_EXT_PORT:-4318}/v1/traces"

.PHONY: otel-health
otel-health: ## Check health of OpenTelemetry observability stack
	@echo "Checking OpenTelemetry observability stack health..."
	@echo ""
	@echo "Grafana Health:"
	@curl -s http://localhost:$${APP_GRAFANA_EXT_PORT:-3000}/api/health 2>/dev/null || echo "  [UNREACHABLE] Grafana is not responding"
	@echo ""
	@echo ""
	@echo "OTLP HTTP Endpoint:"
	@curl -s -o /dev/null -w "  HTTP Status: %{http_code}\n" http://localhost:$${APP_OTLP_HTTP_EXT_PORT:-4318}/v1/traces 2>/dev/null || echo "  [UNREACHABLE] OTLP HTTP endpoint is not responding"

.PHONY: otel-clean
otel-clean: ## Remove OpenTelemetry observability stack volumes (DESTRUCTIVE)
	@echo "WARNING: This will remove all observability data (logs, traces, metrics)!"
	@echo "Press Ctrl+C to cancel, or wait 5 seconds to continue..."
	@sleep 5
	$(DCC_OTEL) down -v
	@echo "Observability stack volumes removed."

# =============================================================================
# Convenience Aliases
# =============================================================================

.PHONY: observability-start
observability-start: otel-start ## Alias for otel-start

.PHONY: observability-stop
observability-stop: otel-stop ## Alias for otel-stop

.PHONY: observability-logs
observability-logs: otel-logs ## Alias for otel-logs
