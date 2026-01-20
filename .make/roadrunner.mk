# RoadRunner Makefile targets
# High-performance PHP application server
# See ADR-005-php-runtime-selection.md for architecture details
# Part of Phase 9: Infrastructure Modernization

# Docker compose command for RoadRunner configuration (uses modular overlay)
DCC_ROADRUNNER = $(DCC_RR)

.PHONY: build-roadrunner
build-roadrunner: ## Build RoadRunner Docker image
	docker build .docker/roadrunner \
		--tag $(DOCKER_SERVER_HOST)/$(DOCKER_PROJECT_PATH)/roadrunner:$(DOCKER_IMAGE_VERSION) \
		${DOCKER_BUILD_ARGS} \
		--build-arg RR_VERSION=2025.1.6

.PHONY: start-roadrunner
start-roadrunner: docker-network-create ## Start RoadRunner stack (port 8080)
	$(DCC_ROADRUNNER) up -d

.PHONY: stop-roadrunner
stop-roadrunner: ## Stop RoadRunner stack
	$(DCC_ROADRUNNER) stop

.PHONY: restart-roadrunner
restart-roadrunner: stop-roadrunner start-roadrunner ## Restart RoadRunner stack

.PHONY: logs-roadrunner
logs-roadrunner: ## View RoadRunner container logs
	$(DCC_ROADRUNNER) logs -f test-micro-article-system-roadrunner

.PHONY: logs-roadrunner-all
logs-roadrunner-all: ## View all RoadRunner stack logs
	$(DCC_ROADRUNNER) logs -f

.PHONY: shell-roadrunner
shell-roadrunner: ## Open shell in RoadRunner container
	$(DCC_ROADRUNNER) exec test-micro-article-system-roadrunner sh

.PHONY: ps-roadrunner
ps-roadrunner: ## Show RoadRunner stack container status
	$(DCC_ROADRUNNER) ps

.PHONY: down-roadrunner
down-roadrunner: ## Stop and remove RoadRunner stack containers
	$(DCC_ROADRUNNER) down

.PHONY: health-roadrunner
health-roadrunner: ## Check RoadRunner health endpoint
	curl -sf http://localhost:2114/health?plugin=http && echo " ✓ RoadRunner healthy"

.PHONY: metrics-roadrunner
metrics-roadrunner: ## View RoadRunner Prometheus metrics
	curl -s http://localhost:9180/metrics | head -50
