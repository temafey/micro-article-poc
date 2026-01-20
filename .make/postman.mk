# ========================================================================
# POSTMAN COLLECTION GENERATION
# ========================================================================

.PHONY: postman-generate
postman-generate: ## generate postman collections for all entities (default: v2 API)
	@echo "Generating Postman collections..."
	$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console app:generate-postman-collection'

.PHONY: postman-generate-v1
postman-generate-v1: ## generate postman collections for v1 API
	@echo "Generating Postman collections for v1 API..."
	$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console app:generate-postman-collection --api-version=v1'

.PHONY: postman-generate-v2
postman-generate-v2: ## generate postman collections for v2 API
	@echo "Generating Postman collections for v2 API..."
	$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console app:generate-postman-collection --api-version=v2'

.PHONY: postman-generate-custom
postman-generate-custom: ## generate with custom output (usage: make postman-generate-custom API_VERSION=v2 OUTPUT=path/to/output)
	@echo "Generating Postman collections with custom settings..."
	$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console app:generate-postman-collection --api-version=$(API_VERSION) --output=$(OUTPUT)'

.PHONY: postman-generate-with-env
postman-generate-with-env: ## generate collections and environment file (v2 API, local-dev environment)
	@echo "Generating Postman collections with environment..."
	$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console app:generate-postman-collection --generate-env'

.PHONY: postman-generate-env
postman-generate-env: ## generate environment file from existing collections (usage: make postman-generate-env ENV_NAME=local-dev)
	@echo "Generating Postman environment file..."
	$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console app:generate-postman-collection --generate-env --env-name=$(ENV_NAME)'

# ========================================================================
# POSTMAN COLLECTION TESTING - ENTITY-SPECIFIC
# ========================================================================

.PHONY: postman-test-article
postman-test-article: ## test article operations
	@echo "Testing article operations..."
	$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console app:emulate-request --collection=/app/tests/postman/entities/article-commands.json --postman-env=/app/tests/postman/environments/local-dev.json'
	$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console app:emulate-request --collection=/app/tests/postman/entities/article-queries.json --postman-env=/app/tests/postman/environments/local-dev.json'

# ========================================================================
# POSTMAN COLLECTION TESTING - ENVIRONMENT-SPECIFIC
# ========================================================================

.PHONY: postman-test-performance
postman-test-performance: ## performance testing with load environment
	@echo "Running performance tests..."
	$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console app:emulate-request --collection=/app/tests/postman/entities/article-commands.json --postman-env=/app/tests/postman/environments/performance.json'
	$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console app:emulate-request --collection=/app/tests/postman/entities/article-queries.json --postman-env=/app/tests/postman/environments/performance.json'

# ========================================================================
# POSTMAN COLLECTION TESTING - ADVANCED USAGE
# ========================================================================

.PHONY: postman-test-single
postman-test-single: ## test single HTTP request (usage: make postman-test-single METHOD=GET URI=/api/v2/article/status)
	@echo "Testing single HTTP request..."
	$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console app:emulate-request $(METHOD) $(URI) $(HEADERS) $(CONTENT)'

.PHONY: postman-test-folder
postman-test-folder: ## test specific folder from collection (usage: make postman-test-folder COLLECTION=... FOLDER=...)
	@echo "Testing specific folder from collection..."
	$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console app:emulate-request --collection=$(COLLECTION) --postman-env=/app/tests/postman/environments/local-dev.json --folder=$(FOLDER)'

.PHONY: postman-test-request
postman-test-request: ## test specific request from collection (usage: make postman-test-request COLLECTION=... REQUEST=...)
	@echo "Testing specific request from collection..."
	$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console app:emulate-request --collection=$(COLLECTION) --postman-env=/app/tests/postman/environments/local-dev.json --request=$(REQUEST)'

.PHONY: postman-test-collection
postman-test-collection: ## run custom collection with environment (usage: make postman-test-collection COLLECTION=path ENV=path)
	@echo "Running custom Postman collection..."
	$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console app:emulate-request --collection=$(COLLECTION) --postman-env=$(ENV)'

# ========================================================================
# POSTMAN HELP
# ========================================================================

.PHONY: postman-help
postman-help: ## show available postman commands with usage examples
	@echo ""
	@echo "═══════════════════════════════════════════════════════════════════════════"
	@echo "  POSTMAN COLLECTION GENERATION"
	@echo "═══════════════════════════════════════════════════════════════════════════"
	@echo ""
	@echo "  make postman-generate              - Generate collections for all entities (v2 API)"
	@echo "  make postman-generate-v1           - Generate collections for v1 API"
	@echo "  make postman-generate-v2           - Generate collections for v2 API"
	@echo "  make postman-generate-custom       - Generate with custom settings"
	@echo "                                       API_VERSION=v2 OUTPUT=path/to/output"
	@echo "  make postman-generate-with-env     - Generate collections + environment (local-dev)"
	@echo "  make postman-generate-env          - Generate environment only"
	@echo "                                       ENV_NAME=local-dev"
	@echo ""
	@echo "═══════════════════════════════════════════════════════════════════════════"
	@echo "  POSTMAN COLLECTION TESTING - COMPREHENSIVE SUITES"
	@echo "═══════════════════════════════════════════════════════════════════════════"
	@echo ""
	@echo "  make postman-test-all              - Run all collections with local environment"
	@echo "  make postman-validate-api          - Validate API endpoints before deployment"
	@echo ""
	@echo "═══════════════════════════════════════════════════════════════════════════"
	@echo "  POSTMAN COLLECTION TESTING - ENTITY-SPECIFIC"
	@echo "═══════════════════════════════════════════════════════════════════════════"
	@echo ""
	@echo "  make postman-test-article             - Test article operations"
	@echo ""
	@echo "═══════════════════════════════════════════════════════════════════════════"
	@echo "  POSTMAN COLLECTION TESTING - ENVIRONMENT-SPECIFIC"
	@echo "═══════════════════════════════════════════════════════════════════════════"
	@echo ""
	@echo "  make postman-test-staging          - Test against staging environment"
	@echo "  make postman-test-performance      - Run performance tests"
	@echo ""
	@echo "═══════════════════════════════════════════════════════════════════════════"
	@echo "  POSTMAN COLLECTION TESTING - ADVANCED USAGE"
	@echo "═══════════════════════════════════════════════════════════════════════════"
	@echo ""
	@echo "  make postman-test-single           - Test single HTTP request"
	@echo "       METHOD=GET URI=/api/v2/article/status"
	@echo ""
	@echo "  make postman-test-folder           - Test specific folder from collection"
	@echo "       COLLECTION=path/to/collection.json FOLDER=\"Folder Name\""
	@echo ""
	@echo "  make postman-test-request          - Test specific request from collection"
	@echo "       COLLECTION=path/to/collection.json REQUEST=\"Request Name\""
	@echo ""
	@echo "  make postman-test-collection       - Run custom collection with environment"
	@echo "       COLLECTION=path/to/collection.json ENV=path/to/env.json"
	@echo ""
	@echo "═══════════════════════════════════════════════════════════════════════════"
	@echo ""
