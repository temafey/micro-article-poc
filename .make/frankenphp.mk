# FrankenPHP Makefile targets
# Alternative deployment using FrankenPHP worker mode instead of nginx+PHP-FPM
# See ADR-005-php-runtime-selection.md for architecture details
# Updated for Phase 9: Modular Docker Compose Architecture

# Docker compose command for FrankenPHP configuration (uses modular overlay)
DCC_FRANKENPHP = $(DCC_FRANK)

.PHONY: build-frankenphp
build-frankenphp: ## Build FrankenPHP Docker image
	docker build . \
		--tag $(DOCKER_SERVER_HOST)/$(DOCKER_PROJECT_PATH)/frankenphp:$(DOCKER_IMAGE_VERSION) \
		-f .docker/frankenphp/Dockerfile ${DOCKER_BUILD_ARGS} \
		--build-arg DOCKER_IMAGE_VERSION=$(DOCKER_IMAGE_VERSION) \
		--build-arg DOCKER_SERVER_HOST=$(DOCKER_SERVER_HOST) \
		--build-arg DOCKER_PROJECT_PATH=$(DOCKER_PROJECT_PATH)

.PHONY: start-frankenphp
start-frankenphp: docker-network-create ## Start FrankenPHP stack (replaces nginx+PHP-FPM)
	$(DCC_FRANKENPHP) up -d

.PHONY: stop-frankenphp
stop-frankenphp: ## Stop FrankenPHP stack
	$(DCC_FRANKENPHP) stop

.PHONY: restart-frankenphp
restart-frankenphp: stop-frankenphp start-frankenphp ## Restart FrankenPHP stack

.PHONY: logs-frankenphp
logs-frankenphp: ## View FrankenPHP container logs
	$(DCC_FRANKENPHP) logs -f test-micro-article-system-frankenphp

.PHONY: logs-frankenphp-all
logs-frankenphp-all: ## View all FrankenPHP stack logs
	$(DCC_FRANKENPHP) logs -f

.PHONY: shell-frankenphp
shell-frankenphp: ## Open shell in FrankenPHP container
	$(DCC_FRANKENPHP) exec test-micro-article-system-frankenphp sh

.PHONY: ps-frankenphp
ps-frankenphp: ## Show FrankenPHP stack container status
	$(DCC_FRANKENPHP) ps

.PHONY: down-frankenphp
down-frankenphp: ## Stop and remove FrankenPHP stack containers
	$(DCC_FRANKENPHP) down

.PHONY: benchmark-frankenphp
benchmark-frankenphp: ## Run FrankenPHP vs nginx+PHP-FPM benchmark
	./scripts/benchmark-frankenphp.sh $(RUN_ARGS)

.PHONY: benchmark-frankenphp-quick
benchmark-frankenphp-quick: ## Quick benchmark of currently running stack
	./scripts/benchmark-frankenphp.sh quick 1000 50
