# =============================================================================
# Utility Makefile Targets
# =============================================================================
# Uses DCC_ACTIVE and PHP_CONTAINER for multi-runtime support
# Works with: PHP-FPM, RoadRunner, FrankenPHP (set RUNTIME variable)

sources = bin/console config src
version = $(shell git describe --tags --dirty --always)
build_name = application-$(version)
# use the rest as arguments for "run"
#RUN_ARGS := $(wordlist 2,$(words $(MAKECMDGOALS)),$(MAKECMDGOALS))
# ...and turn them into do-nothing targets
#$(eval $(RUN_ARGS):;@:)

# Default parallelism
JOBS=$(shell nproc)

.PHONY: setup-directories
setup-directories: ## setup required directory structure
	@echo "Setting up required directories..."
	@mkdir -p var/log/nginx
	@mkdir -p var/cache
	@mkdir -p var/storage/read
	@mkdir -p var/storage/write
	@mkdir -p var/fixture
	@echo "✓ Directory structure created successfully"

.PHONY: fix-permission
fix-permission: ## fix permission for docker env
	echo chown -R 777 ~/.composer/
	echo chown -R $(shell whoami):$(shell whoami) *
	echo chown -R $(shell whoami):$(shell whoami) .docker/*

.PHONY: add-docker-user
add-docker-user: ## Add docker user
	echo sudo groupadd docker
	echo sudo usermod -aG docker $USER
	echo newgrp docker

.PHONY: install-git-submodules
install-git-submodules: ## Install git submodules
	git clone --recurse-submodules https://github.com/microdevops-com/gitlab-ci-functions.git ./.gitlab-ci-functions
	cd .gitlab-ci-functions
	git submodule update --remote
	git add .
	cd .. && git commit -m "git submodule updated"

wait:
ifeq ($(OS),Windows_NT)
	timeout 15
else
	sleep 15
endif

.PHONY: help
help: ## Display this help message
	@cat $(MAKEFILE_LIST) | grep -e "^[a-zA-Z_\-]*: *.*## *" | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

.PHONY: configs
configs: ## Copy configs if not exists
	@if [ ! -f .env ]; then cp .env.docker .env; fi

.PHONY: configs-remove
configs-remove: ## Remove configs if exists
	@if [ -f .env.backup ]; then rm .env.backup; fi
	@if [ -f .env ]; then mv .env .env.backup; fi

.PHONY: logs
logs: ## look for service logs
	$(DCC_ACTIVE) logs -f ${CI_SERVICE_NAME}-$(RUN_ARGS)

.PHONY: shell
shell: ## enter to service shell
	$(DCC_ACTIVE) run --rm --no-deps ${CI_SERVICE_NAME}-$(RUN_ARGS) sh -l

.PHONY: restart
restart: ## restart service
	$(DCC_ACTIVE) restart ${CI_SERVICE_NAME}-$(RUN_ARGS)

.PHONY: docker-stop
docker-stop: ## stop all containers
	$(eval CONTAINERS=$(shell (docker ps -q)))
	@$(if $(strip $(CONTAINERS)), echo Going to stop all containers: $(shell docker stop $(CONTAINERS)), echo No run containers)

.PHONY: docker-remove
docker-remove: ## remove all containers
	$(eval CONTAINERS=$(shell (docker ps -aq)))
	@$(if $(strip $(CONTAINERS)), echo Going to remove all containers: $(shell docker rm $(CONTAINERS)), echo No containers)

.PHONY: docker-remove-volumes
docker-remove-volumes: ## remove project docker vo
	$(eval VOLUMES = $(shell (docker volume ls --filter name=$(CUR_DIR) -q)))
	$(if $(strip $(VOLUMES)), echo Going to remove volumes $(shell docker volume rm $(VOLUMES)), echo No active volumes)

.PHONY: docker-remove-images
docker-remove-images: ## remove all images
	$(eval IMAGES=$(shell (docker images -q)))
	@$(if $(strip $(IMAGES)), echo Going to remove all images: $(shell docker rmi $(IMAGES)), echo No images)

.PHONY: docker-update
docker-update: docker-stop docker-remove docker-remove-images build ## update all project containers

.PHONY: docker-network-create
docker-network-create:
	$(eval NETWORK=$(shell (docker network ls | grep $(DOCKER_NETWORK_NAME))))
	$(if $(strip $(NETWORK)), echo $(DOCKER_NETWORK_NAME) network exists, echo "Create $(DOCKER_NETWORK_NAME) network ...": $(shell docker network create $(DOCKER_NETWORK_NAME)))

.PHONY: docker-network-remove
docker-network-remove:
	$(eval NETWORK=$(shell (docker network ls | grep $(DOCKER_NETWORK_NAME))))
	$(if $(strip $(NETWORK)), echo "Remove network ": $(shell docker network rm $(DOCKER_NETWORK_NAME)), echo $(DOCKER_NETWORK_NAME) network doesn\'t exists)

.PHONY: generate-ssl
generate-ssl:
	$(DCC_ACTIVE) run --rm $(PHP_CONTAINER) sh -lc 'mkdir -p ./var/jwt'
	$(DCC_ACTIVE) run --rm $(PHP_CONTAINER) sh -lc 'openssl genrsa -out var/jwt/private.pem -aes256 4096'
	$(DCC_ACTIVE) run --rm $(PHP_CONTAINER) sh -lc 'openssl rsa -pubout -in var/jwt/private.pem -out var/jwt/public.pem'

.PHONY: docker-log
docker-log: ## look for service logs
	$(DCC_ACTIVE) logs -f ${CI_SERVICE_NAME}-$(RUN_ARGS)

.PHONY: docker-logs
docker-logs:
	$(DCC_ACTIVE) logs -f $(RUN_ARGS)

.PHONY: docker-shell
docker-shell:
	$(DCC_ACTIVE) run --rm ${CI_SERVICE_NAME}-$(RUN_ARGS) bash

.PHONY: docker-list
docker-list:
	$(DCC_ACTIVE) ps -a




