# =============================================================================
# Composer Makefile Targets
# =============================================================================
# Uses DCC_ACTIVE and PHP_CONTAINER for multi-runtime support
# Works with: PHP-FPM, RoadRunner, FrankenPHP (set RUNTIME variable)

.PHONY: composer-autoscript
composer-autoscript: ## Symfony cache clear and install assets
	$(DCC_ACTIVE) run --rm --no-deps $(PHP_CONTAINER) sh -lc '/app/bin/console -vvv c:c'
	$(DCC_ACTIVE) run --rm --no-deps $(PHP_CONTAINER) sh -lc '/app/bin/console assets:install'

.PHONY: composer-install
composer-install: ## Install project dependencies
	$(DCC_ACTIVE) run --rm --no-deps $(PHP_CONTAINER) sh -lc 'composer install --optimize-autoloader'
	$(DCC_ACTIVE) run --rm --no-deps $(PHP_CONTAINER) sh -lc '/app/bin/console -vvv c:c'
	$(DCC_ACTIVE) run --rm --no-deps $(PHP_CONTAINER) sh -lc '/app/bin/console assets:install'


.PHONY: composer-install-no-dev
composer-install-no-dev: ## Install project dependencies without dev
	$(DCC_ACTIVE) run --rm --no-deps $(PHP_CONTAINER) sh -lc 'composer install --no-dev --optimize-autoloader'
	$(DCC_ACTIVE) run --rm --no-deps $(PHP_CONTAINER) sh -lc '/app/bin/console -vvv c:c'
	$(DCC_ACTIVE) run --rm --no-deps $(PHP_CONTAINER) sh -lc '/app/bin/console assets:install'

.PHONY: composer-update
composer-update: ## Update project dependencies
	$(DCC_ACTIVE) run --rm --no-deps $(PHP_CONTAINER) sh -lc 'composer update --optimize-autoloader --with-all-dependencies'
	$(DCC_ACTIVE) run --rm --no-deps $(PHP_CONTAINER) sh -lc '/app/bin/console -vvv c:c'
	$(DCC_ACTIVE) run --rm --no-deps $(PHP_CONTAINER) sh -lc '/app/bin/console assets:install'

.PHONY: composer-outdated
composer-outdated: ## Show outdated project dependencies
	$(DCC_ACTIVE) run --rm --no-deps $(PHP_CONTAINER) sh -lc 'composer outdated'

.PHONY: composer-validate
composer-validate: ## Validate composer config
	$(DCC_ACTIVE) run --rm --no-deps $(PHP_CONTAINER) sh -lc 'composer validate --no-check-publish'

.PHONY: composer
composer: ## Execute composer command
	$(DCC_ACTIVE) run --rm --no-deps $(PHP_CONTAINER) sh -lc "composer $(RUN_ARGS) --ignore-platform-reqs"

.PHONY: composer-test
composer-test: ## Run unit and code style tests.
	$(DCC_ACTIVE) run --rm --no-deps $(PHP_CONTAINER) sh -lc "composer test"

.PHONY: composer-fix-style
composer-fix-style: ## Automated attempt to fix code style.
	$(DCC_ACTIVE) run --rm --no-deps $(PHP_CONTAINER) sh -lc "composer fix-style"

.PHONY: composer-preload
composer-preload: ## Generate preload config file
	$(DCC_ACTIVE) run --rm --no-deps $(PHP_CONTAINER) sh -lc 'composer preload'

