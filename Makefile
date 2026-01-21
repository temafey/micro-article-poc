$(shell (if [ ! -e .env ]; then .make/convert-env.sh; fi))
include .env
export

ifeq ($(OS),Windows_NT)
    CWD := $(lastword $(dir $(realpath $(MAKEFILE_LIST))))/
else
    CWD := $(abspath $(patsubst %/,%,$(dir $(abspath $(lastword $(MAKEFILE_LIST))))))/
endif

RUN_ARGS = $(filter-out $@,$(MAKECMDGOALS))

# Docker Compose Commands - Modular Architecture (Phase 9)
# Base infrastructure only
DCC_BASE = docker-compose -f $(CWD)docker-compose.yml --project-directory $(CWD)
# Runtime-specific full stacks
DCC_FPM = docker-compose -f $(CWD)docker-compose.yml -f $(CWD)docker-compose.fpm.yml -f $(CWD)docker-compose.workers.yml --project-directory $(CWD)
DCC_RR = docker-compose -f $(CWD)docker-compose.yml -f $(CWD)docker-compose.rr.yml -f $(CWD)docker-compose.workers.yml --project-directory $(CWD)
DCC_FRANK = docker-compose -f $(CWD)docker-compose.yml -f $(CWD)docker-compose.frank.yml -f $(CWD)docker-compose.workers.yml --project-directory $(CWD)
# CLI development container (PHPStorm integration)
DCC_CLI = docker-compose -f $(CWD)docker-compose.yml -f $(CWD)docker-compose.yml --project-directory $(CWD)
# Default: PHP-FPM stack (backward compatibility)
DCC = $(DCC_FPM)

# =============================================================================
# Runtime Selection System (Phase 9: Multi-Runtime Support)
# =============================================================================
# Usage: make start RUNTIME=rr  OR  RUNTIME=frank make start
# Valid values: fpm (default), rr (RoadRunner), frank (FrankenPHP)
RUNTIME ?= fpm

# Active runtime configuration (resolved based on RUNTIME variable)
ifeq ($(RUNTIME),rr)
    DCC_ACTIVE = $(DCC_RR)
    PHP_CONTAINER = test-micro-article-system-http
    RUNTIME_PORT = 8080
    RUNTIME_NAME = RoadRunner
else ifeq ($(RUNTIME),frank)
    DCC_ACTIVE = $(DCC_FRANK)
    PHP_CONTAINER = test-micro-article-system-http
    RUNTIME_PORT = 8081
    RUNTIME_NAME = FrankenPHP
else
    # Default: PHP-FPM (backward compatible)
    DCC_ACTIVE = $(DCC_FPM)
    PHP_CONTAINER = $(DOCKER_PHP_CONTAINER_NAME)
    RUNTIME_PORT = 80
    RUNTIME_NAME = PHP-FPM
endif

include .make/utils.mk
include .make/build.mk
include .make/composer.mk
include .make/migrations.mk
include .make/postman.mk
include .make/static-analysis.mk
include .make/coverage.mk
include .make/frankenphp.mk
include .make/roadrunner.mk
include .make/performance.mk
include .make/shared-services.mk
include .make/apisix.mk
include .make/observability.mk

.PHONY: install
install: fix-permission erase build-all setup-directories composer-install start setup## clean current environment, recreate dependencies and spin up again

.PHONY: install-lite
install-lite: build setup-directories setup start

.PHONY: start
start: setup-directories ## spin up environment (use RUNTIME=rr|frank to switch)
	@echo "Starting $(RUNTIME_NAME) stack on port $(RUNTIME_PORT)..."
	$(DCC_ACTIVE) up -d

.PHONY: stop
stop: ## stop environment
	@echo "Stopping $(RUNTIME_NAME) stack..."
	$(DCC_ACTIVE) stop

.PHONY: remove
remove: ## remove project docker containers
	$(DCC_ACTIVE) rm -v -f

.PHONY: erase
erase: stop remove docker-remove-volumes ## stop and delete containers, clean volumes

.PHONY: setup
setup: setup-db setup-enqueue ## build environment and initialize composer and project dependencies

.PHONY: clear-events
clear-events: ## setup enqueue
	$(DCC_ACTIVE) run --rm $(PHP_CONTAINER) sh -lc './bin/console cleaner:clear db'

.PHONY: console
console: ## execute symfony console command (use RUNTIME=rr|frank to switch)
	$(DCC_ACTIVE) run --rm --no-deps $(PHP_CONTAINER) sh -lc "./bin/console -vvv $(RUN_ARGS)"

.PHONY: lint-container
lint-container: ## checks that the arguments injected into services match their type declarations
	$(DCC_ACTIVE) run --rm --no-deps $(PHP_CONTAINER) sh -lc "./bin/console lint:container"

.PHONY: php-test
php-test: ## PHP shell without deps
	$(DCC_ACTIVE) run --rm --no-deps $(PHP_CONTAINER) sh -l

.PHONY: php-shell
php-shell: ## PHP shell (use RUNTIME=rr|frank to switch)
	$(DCC_ACTIVE) run --rm $(PHP_CONTAINER) sh -l

.PHONY: php-shell-no-deps
php-shell-no-deps: ## PHP shell without deps
	$(DCC_ACTIVE) run --rm --no-deps $(PHP_CONTAINER) sh -l

.PHONY: nginx-shell
nginx-shell: ## nginx shell
	$(DCC) run --rm  --no-deps test-nginx sh -l

.PHONY: clean
clean: ## Clear build vendor report folders
	rm -rf build/ vendor/ var/

# =============================================================================
# Runtime Information and Validation
# =============================================================================

.PHONY: runtime-info
runtime-info: ## Show current runtime configuration
	@echo "=== Runtime Configuration ==="
	@echo "RUNTIME:       $(RUNTIME)"
	@echo "RUNTIME_NAME:  $(RUNTIME_NAME)"
	@echo "RUNTIME_PORT:  $(RUNTIME_PORT)"
	@echo "PHP_CONTAINER: $(PHP_CONTAINER)"
	@echo ""
	@echo "Usage examples:"
	@echo "  make start              # Start PHP-FPM (default)"
	@echo "  make start RUNTIME=rr   # Start RoadRunner"
	@echo "  make start RUNTIME=frank # Start FrankenPHP"

.PHONY: validate-runtime
validate-runtime: ## Validate RUNTIME variable value
	@if [ "$(RUNTIME)" != "fpm" ] && [ "$(RUNTIME)" != "rr" ] && [ "$(RUNTIME)" != "frank" ]; then \
		echo "ERROR: Invalid RUNTIME '$(RUNTIME)'. Valid options: fpm, rr, frank"; \
		exit 1; \
	fi
	@echo "Runtime '$(RUNTIME)' is valid."

# =============================================================================
# CLI Development Container (PHPStorm Integration)
# =============================================================================

.PHONY: start-cli
start-cli: setup-directories ## Start CLI development container for PHPStorm debugging
	@echo "Starting CLI container for PHPStorm integration..."
	$(DCC_CLI) up -d test-micro-article-system-cli
	@echo "CLI container ready. Configure PHPStorm:"
	@echo "  1. Settings → PHP → CLI Interpreter → Add → Docker"
	@echo "  2. Container: $(CI_SERVICE_NAME)-cli"
	@echo "  3. Lifecycle: Connect to existing container"

.PHONY: stop-cli
stop-cli: ## Stop CLI development container
	@echo "Stopping CLI container..."
	$(DCC_CLI) stop test-micro-article-system-cli

.PHONY: cli-shell
cli-shell: ## Open shell in CLI container
	$(DCC_CLI) exec test-micro-article-system-cli sh -l

.PHONY: cli-exec
cli-exec: ## Execute command in CLI container (use: make cli-exec cmd="php bin/console")
	$(DCC_CLI) exec test-micro-article-system-cli sh -lc "$(cmd)"

.PHONY: cli-php
cli-php: ## Run PHP script with debugging (use: make cli-php script="scripts/test.php")
	$(DCC_CLI) exec test-micro-article-system-cli php $(script)

.PHONY: cli-console
cli-console: ## Run Symfony console in CLI container with debugging
	$(DCC_CLI) exec test-micro-article-system-cli sh -lc "./bin/console $(RUN_ARGS)"

.PHONY: test-unit
test-unit: ## Run PHPUnit tests with debugging in CLI container
	$(DCC_CLI) exec test-micro-article-system-cli ./vendor/bin/phpunit $(RUN_ARGS)

.PHONY: cli-logs
cli-logs: ## View CLI container logs
	$(DCC_CLI) logs -f test-micro-article-system-cli

.PHONY: cli-status
cli-status: ## Show CLI container status
	@echo "=== CLI Container Status ==="
	@$(DCC_CLI) ps test-micro-article-system-cli 2>/dev/null || echo "CLI container not running"
	@echo ""
	@echo "Xdebug configuration:"
	@$(DCC_CLI) exec test-micro-article-system-cli php -i 2>/dev/null | grep -E "xdebug\.(mode|client_host|client_port|start_with_request)" || echo "  Container not running"

.PHONY: cli-help
cli-help: ## Show CLI container help
	@echo "==============================================================================="
	@echo " PHP CLI Development Container (PHPStorm Integration)"
	@echo "==============================================================================="
	@echo ""
	@echo "QUICK START:"
	@echo "  make start-cli              # Start CLI container"
	@echo "  make cli-shell              # Open shell in container"
	@echo "  make cli-console app:test   # Run Symfony console command"
	@echo ""
	@echo "PHPSTORM SETUP:"
	@echo "  1. Settings → PHP → CLI Interpreter → Add → Docker"
	@echo "  2. Server: Docker (local)"
	@echo "  3. Container: $(CI_SERVICE_NAME)-cli"
	@echo "  4. Lifecycle: Connect to existing container"
	@echo "  5. Path mapping: /app → project root"
	@echo ""
	@echo "DEBUGGING:"
	@echo "  - Xdebug auto-starts for all CLI scripts"
	@echo "  - IDE key: PHPSTORM"
	@echo "  - Port: 9003"
	@echo "  - No browser extension needed (auto-start)"
	@echo ""
	@echo "AVAILABLE COMMANDS:"
	@echo "  make start-cli              # Start CLI container"
	@echo "  make stop-cli               # Stop CLI container"
	@echo "  make cli-shell              # Shell access"
	@echo "  make cli-exec cmd=\"...\"     # Run arbitrary command"
	@echo "  make cli-php script=\"...\"   # Run PHP script with debugging"
	@echo "  make cli-console <args>     # Run Symfony console"
	@echo "  make test-unit <args>        # Run PHPUnit with debugging"
	@echo "  make cli-logs               # View container logs"
	@echo "  make cli-status             # Show container & Xdebug status"
	@echo ""
	@echo "ENVIRONMENT VARIABLES:"
	@echo "  XDEBUG_CLI_MODE             # Override Xdebug mode (default: debug)"
	@echo ""

.PHONY: runtime-help
runtime-help: ## Show multi-runtime help and available commands
	@echo "==============================================================================="
	@echo " Multi-Runtime PHP Server Support (Phase 9)"
	@echo "==============================================================================="
	@echo ""
	@echo "AVAILABLE RUNTIMES:"
	@echo "  fpm    (default) - nginx + PHP-FPM       Port: 80"
	@echo "  rr               - RoadRunner            Port: 8080"
	@echo "  frank            - FrankenPHP            Port: 8081"
	@echo ""
	@echo "USAGE: make <target> RUNTIME=<fpm|rr|frank>"
	@echo ""
	@echo "RUNTIME-AWARE COMMANDS (work with any runtime):"
	@echo "  make start                  # Start default (PHP-FPM)"
	@echo "  make start RUNTIME=rr       # Start RoadRunner"
	@echo "  make start RUNTIME=frank    # Start FrankenPHP"
	@echo "  make stop RUNTIME=<runtime>"
	@echo "  make composer-install RUNTIME=<runtime>"
	@echo "  make composer-update RUNTIME=<runtime>"
	@echo "  make console RUNTIME=<runtime> <args>"
	@echo "  make postgres-shell RUNTIME=<runtime>"
	@echo "  make generate-ssl RUNTIME=<runtime>"
	@echo ""
	@echo "RUNTIME-SPECIFIC COMMANDS:"
	@echo "  PHP-FPM:     make php-shell, make nginx-shell"
	@echo "  RoadRunner:  make start-roadrunner, make shell-roadrunner, make logs-roadrunner"
	@echo "  FrankenPHP:  make start-frankenphp, make shell-frankenphp, make logs-frankenphp"
	@echo ""
	@echo "BUILD COMMANDS:"
	@echo "  make build-all              # Build PHP-FPM + nginx + RabbitMQ"
	@echo "  make build-all-runtimes     # Build ALL runtimes (FPM + RR + Frank)"
	@echo "  make build-roadrunner       # Build RoadRunner only"
	@echo "  make build-frankenphp       # Build FrankenPHP only"
	@echo ""
	@echo "PERFORMANCE TESTING:"
	@echo "  make perf-quick-all         # Quick benchmark all runtimes"
	@echo "  make perf-load-all          # Load test all runtimes"
	@echo "  make perf-help              # Show all performance commands"
	@echo ""
	@echo "CURRENT CONFIGURATION:"
	@echo "  RUNTIME=$(RUNTIME) -> $(RUNTIME_NAME) on port $(RUNTIME_PORT)"
	@echo "  Container: $(PHP_CONTAINER)"
	@echo ""
	@echo "For more details: docs/adr/ADR-005-php-runtime-selection.md"
