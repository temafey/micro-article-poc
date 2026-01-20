# =============================================================================
# Build Makefile Targets
# =============================================================================
# Uses DCC_ACTIVE for multi-runtime support (PHP-FPM, RoadRunner, FrankenPHP)
# Individual runtime builds are in roadrunner.mk and frankenphp.mk

.PHONY: build-all
build-all: docker-network-create build build-nginx build-rabbitmq ## Build PHP-FPM stack (default runtime)

.PHONY: build-all-runtimes
build-all-runtimes: docker-network-create build build-nginx build-rabbitmq build-roadrunner build-frankenphp ## Build all three PHP runtimes (FPM + RoadRunner + FrankenPHP)
	@echo "✓ All runtimes built successfully"
	@echo "  - PHP-FPM:     $(DOCKER_SERVER_HOST)/$(DOCKER_PROJECT_PATH)/php$(DOCKER_PHP_VERSION)-fpm:$(DOCKER_IMAGE_VERSION)"
	@echo "  - RoadRunner:  $(DOCKER_SERVER_HOST)/$(DOCKER_PROJECT_PATH)/roadrunner:$(DOCKER_IMAGE_VERSION)"
	@echo "  - FrankenPHP:  $(DOCKER_SERVER_HOST)/$(DOCKER_PROJECT_PATH)/frankenphp:$(DOCKER_IMAGE_VERSION)"

.PHONY: build
build: ## build php
	docker build .docker/php$(DOCKER_PHP_VERSION)-fpm/ \
	--tag $(DOCKER_SERVER_HOST)/$(DOCKER_PROJECT_PATH)/php$(DOCKER_PHP_VERSION)-fpm:$(DOCKER_IMAGE_VERSION) \
		--build-arg DOCKER_IMAGE_VERSION=$(DOCKER_IMAGE_VERSION) \
		--build-arg DOCKER_SERVER_HOST=$(DOCKER_SERVER_HOST) \
		--build-arg DOCKER_PROJECT_PATH=$(DOCKER_PROJECT_PATH) \
		--build-arg DOCKER_PHP_VERSION=$(DOCKER_PHP_VERSION)

	docker build .docker/php$(DOCKER_PHP_VERSION)-fpm-composer/ \
	--tag $(DOCKER_SERVER_HOST)/$(DOCKER_PROJECT_PATH)/php$(DOCKER_PHP_VERSION)-fpm-composer:$(DOCKER_IMAGE_VERSION) \
		--build-arg DOCKER_IMAGE_VERSION=$(DOCKER_IMAGE_VERSION) \
		--build-arg DOCKER_SERVER_HOST=$(DOCKER_SERVER_HOST) \
		--build-arg DOCKER_PROJECT_PATH=$(DOCKER_PROJECT_PATH) \
		--build-arg DOCKER_PHP_VERSION=$(DOCKER_PHP_VERSION)

	docker build .docker/php$(DOCKER_PHP_VERSION)-fpm-dev/ \
	--tag $(DOCKER_SERVER_HOST)/$(DOCKER_PROJECT_PATH)/php$(DOCKER_PHP_VERSION)-fpm-dev:$(DOCKER_IMAGE_VERSION) \
		--build-arg DOCKER_IMAGE_VERSION=$(DOCKER_IMAGE_VERSION) \
		--build-arg DOCKER_SERVER_HOST=$(DOCKER_SERVER_HOST) \
		--build-arg DOCKER_PROJECT_PATH=$(DOCKER_PROJECT_PATH) \
		--build-arg DOCKER_PHP_VERSION=$(DOCKER_PHP_VERSION)

.PHONY: build-nginx
build-nginx: ## build nginx
	docker build .docker/nginx \
		--tag $(DOCKER_SERVER_HOST)/$(DOCKER_PROJECT_PATH)/nginx:$(DOCKER_IMAGE_VERSION) \
		-f .docker/nginx/Dockerfile ${DOCKER_BUILD_ARGS} \
		--build-arg DOCKER_IMAGE_VERSION=$(DOCKER_IMAGE_VERSION) \
		--build-arg DOCKER_SERVER_HOST=$(DOCKER_SERVER_HOST) \
		--build-arg DOCKER_PROJECT_PATH=$(DOCKER_PROJECT_PATH)

.PHONY: build-postgres
build-postgres: ## build postgres
	docker build .docker/postgres/ \
		--tag $(DOCKER_SERVER_HOST)/$(DOCKER_PROJECT_PATH)/postgres:$(DOCKER_IMAGE_VERSION) \
		-f .docker/migrations/Dockerfile ${DOCKER_BUILD_ARGS} \
		--build-arg DOCKER_IMAGE_VERSION=$(DOCKER_IMAGE_VERSION) \
		--build-arg DOCKER_SERVER_HOST=$(DOCKER_SERVER_HOST) \
		--build-arg DOCKER_PROJECT_PATH=$(DOCKER_PROJECT_PATH)

.PHONY: build-rabbitmq
build-rabbitmq: ## build rabbitmq
	docker build .docker/rabbitmq/ \
		--tag $(DOCKER_SERVER_HOST)/$(DOCKER_PROJECT_PATH)/rabbitmq:$(DOCKER_IMAGE_VERSION) \
		-f .docker/rabbitmq/Dockerfile ${DOCKER_BUILD_ARGS} \
		--build-arg DOCKER_IMAGE_VERSION=$(DOCKER_IMAGE_VERSION) \
		--build-arg DOCKER_SERVER_HOST=$(DOCKER_SERVER_HOST) \
		--build-arg DOCKER_PROJECT_PATH=$(DOCKER_PROJECT_PATH)
