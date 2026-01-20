# =============================================================================
# Shared Services Makefile Targets (TASK-038: Legacy Cleanup)
# =============================================================================
# The legacy shared monitoring stack has been replaced by grafana/otel-lgtm.
# Use the new observability commands from observability.mk instead.
#
# Migration Guide:
#   shared-service-start   → otel-start
#   shared-service-stop    → otel-stop
#   shared-service-logs    → otel-logs
#   shared-service-ps      → otel-status
#   shared-service-erase   → otel-clean
#
# Related:
#   - ADR-014: Observability Stack Modernization
#   - TASK-034: Observability Foundation
#   - TASK-037: Alerting & Profiling
#   - TASK-038: Legacy Stack Cleanup
# =============================================================================

# DCCS defaults to DCC_ACTIVE (for database/infra commands with current runtime)
DCCS ?= $(DCC_ACTIVE)

# =============================================================================
# DEPRECATED: Legacy Shared Monitoring Commands
# =============================================================================
# These targets provide deprecation notices pointing to new commands.
# They will be removed in a future release.

.PHONY: shared-service-start
shared-service-start: ## [DEPRECATED] Use 'make otel-start' instead
	@echo ""
	@echo "╔══════════════════════════════════════════════════════════════════╗"
	@echo "║  DEPRECATED: shared-service-start                                ║"
	@echo "║                                                                  ║"
	@echo "║  The legacy monitoring stack has been replaced.                  ║"
	@echo "║  Use the new OpenTelemetry-based observability stack:            ║"
	@echo "║                                                                  ║"
	@echo "║    make otel-start                                               ║"
	@echo "║                                                                  ║"
	@echo "║  See: docs/adr/ADR-014-observability-stack-modernization.md      ║"
	@echo "╚══════════════════════════════════════════════════════════════════╝"
	@echo ""

.PHONY: shared-service-stop
shared-service-stop: ## [DEPRECATED] Use 'make otel-stop' instead
	@echo ""
	@echo "╔══════════════════════════════════════════════════════════════════╗"
	@echo "║  DEPRECATED: shared-service-stop                                 ║"
	@echo "║  Use: make otel-stop                                             ║"
	@echo "╚══════════════════════════════════════════════════════════════════╝"
	@echo ""

.PHONY: shared-service-erase
shared-service-erase: ## [DEPRECATED] Use 'make otel-clean' instead
	@echo ""
	@echo "╔══════════════════════════════════════════════════════════════════╗"
	@echo "║  DEPRECATED: shared-service-erase                                ║"
	@echo "║  Use: make otel-clean                                            ║"
	@echo "╚══════════════════════════════════════════════════════════════════╝"
	@echo ""

.PHONY: shared-service-ps
shared-service-ps: ## [DEPRECATED] Use 'make otel-status' instead
	@echo ""
	@echo "╔══════════════════════════════════════════════════════════════════╗"
	@echo "║  DEPRECATED: shared-service-ps                                   ║"
	@echo "║  Use: make otel-status                                           ║"
	@echo "╚══════════════════════════════════════════════════════════════════╝"
	@echo ""

.PHONY: shared-service-logs
shared-service-logs: ## [DEPRECATED] Use 'make otel-logs' instead
	@echo ""
	@echo "╔══════════════════════════════════════════════════════════════════╗"
	@echo "║  DEPRECATED: shared-service-logs                                 ║"
	@echo "║  Use: make otel-logs                                             ║"
	@echo "╚══════════════════════════════════════════════════════════════════╝"
	@echo ""

.PHONY: shared-service-restart
shared-service-restart: ## [DEPRECATED] Legacy command removed
	@echo ""
	@echo "╔══════════════════════════════════════════════════════════════════╗"
	@echo "║  DEPRECATED: shared-service-restart                              ║"
	@echo "║  Use: make otel-stop && make otel-start                          ║"
	@echo "╚══════════════════════════════════════════════════════════════════╝"
	@echo ""

.PHONY: shared-service-log
shared-service-log: ## [DEPRECATED] Use 'make otel-logs' instead
	@echo ""
	@echo "╔══════════════════════════════════════════════════════════════════╗"
	@echo "║  DEPRECATED: shared-service-log                                  ║"
	@echo "║  Use: make otel-logs                                             ║"
	@echo "╚══════════════════════════════════════════════════════════════════╝"
	@echo ""

.PHONY: shared-service-stop-service
shared-service-stop-service: ## [DEPRECATED] Legacy command removed
	@echo ""
	@echo "╔══════════════════════════════════════════════════════════════════╗"
	@echo "║  DEPRECATED: shared-service-stop-service                         ║"
	@echo "║  The new otel-lgtm stack is a single container.                  ║"
	@echo "║  Use: make otel-stop                                             ║"
	@echo "╚══════════════════════════════════════════════════════════════════╝"
	@echo ""

.PHONY: shared-service-shell
shared-service-shell: ## [DEPRECATED] Legacy command removed
	@echo ""
	@echo "╔══════════════════════════════════════════════════════════════════╗"
	@echo "║  DEPRECATED: shared-service-shell                                ║"
	@echo "║  Use: docker compose exec otel-lgtm sh                           ║"
	@echo "╚══════════════════════════════════════════════════════════════════╝"
	@echo ""

.PHONY: shared-service-setup-db
shared-service-setup-db: ## [DEPRECATED] Use 'make setup-db' instead
	@echo ""
	@echo "╔══════════════════════════════════════════════════════════════════╗"
	@echo "║  DEPRECATED: shared-service-setup-db                             ║"
	@echo "║  Use: make setup-db                                              ║"
	@echo "╚══════════════════════════════════════════════════════════════════╝"
	@echo ""

# =============================================================================
# Active Database Commands (Not Deprecated)
# =============================================================================
# These commands use DCCS (active runtime compose) and remain functional.

.PHONY: postgres-shell
postgres-shell: ## PostgreSQL interactive shell
	$(DCCS) exec ${APP_DATABASE_HOST} psql -U ${APP_DATABASE_LOGIN} ${APP_DATABASE_NAME}

.PHONY: postgres-list
postgres-list: ## List PostgreSQL databases
	$(DCCS) exec ${APP_DATABASE_HOST} psql -U ${APP_DATABASE_LOGIN} ${APP_DATABASE_NAME} -XtAc "\l"

