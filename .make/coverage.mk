# Coverage Makefile targets
# PHPUnit code coverage generation for Unit, Functional, Integration tests
# Generates HTML and Clover XML reports

# =============================================================================
# Configuration
# =============================================================================

COVERAGE_DIR = var/coverage

# =============================================================================
# Unit Test Coverage
# =============================================================================

.PHONY: coverage-unit
coverage-unit: ## Generate code coverage for Unit tests (HTML + Clover)
	@echo "Generating Unit test coverage..."
	@mkdir -p $(COVERAGE_DIR)/unit
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc '\
		mkdir -p $(COVERAGE_DIR)/unit/html && \
		XDEBUG_MODE=coverage ./vendor/bin/phpunit tests/Unit \
			--coverage-html=$(COVERAGE_DIR)/unit/html \
			--coverage-clover=$(COVERAGE_DIR)/unit/clover.xml \
			--coverage-text'
	@echo ""
	@echo "Unit test coverage generated:"
	@echo "  - HTML Report: $(COVERAGE_DIR)/unit/html/index.html"
	@echo "  - Clover XML:  $(COVERAGE_DIR)/unit/clover.xml"

.PHONY: coverage-unit-text
coverage-unit-text: ## Quick Unit coverage (console output only, faster)
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc '\
		XDEBUG_MODE=coverage ./vendor/bin/phpunit tests/Unit --coverage-text'

# =============================================================================
# Functional Test Coverage
# =============================================================================

.PHONY: coverage-functional
coverage-functional: ## Generate code coverage for Functional tests (HTML + Clover)
	@echo "Generating Functional test coverage..."
	@mkdir -p $(COVERAGE_DIR)/functional
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc '\
		mkdir -p $(COVERAGE_DIR)/functional/html && \
		XDEBUG_MODE=coverage ./vendor/bin/phpunit tests/Functional \
			--coverage-html=$(COVERAGE_DIR)/functional/html \
			--coverage-clover=$(COVERAGE_DIR)/functional/clover.xml \
			--coverage-text'
	@echo ""
	@echo "Functional test coverage generated:"
	@echo "  - HTML Report: $(COVERAGE_DIR)/functional/html/index.html"
	@echo "  - Clover XML:  $(COVERAGE_DIR)/functional/clover.xml"

.PHONY: coverage-functional-text
coverage-functional-text: ## Quick Functional coverage (console output only, faster)
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc '\
		XDEBUG_MODE=coverage ./vendor/bin/phpunit tests/Functional --coverage-text'

# =============================================================================
# Integration Test Coverage
# =============================================================================

.PHONY: coverage-integration
coverage-integration: ## Generate code coverage for Integration tests (HTML + Clover)
	@echo "Generating Integration test coverage..."
	@mkdir -p $(COVERAGE_DIR)/integration
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc '\
		mkdir -p $(COVERAGE_DIR)/integration/html && \
		XDEBUG_MODE=coverage ./vendor/bin/phpunit tests/Integration \
			--coverage-html=$(COVERAGE_DIR)/integration/html \
			--coverage-clover=$(COVERAGE_DIR)/integration/clover.xml \
			--coverage-text'
	@echo ""
	@echo "Integration test coverage generated:"
	@echo "  - HTML Report: $(COVERAGE_DIR)/integration/html/index.html"
	@echo "  - Clover XML:  $(COVERAGE_DIR)/integration/clover.xml"

.PHONY: coverage-integration-text
coverage-integration-text: ## Quick Integration coverage (console output only, faster)
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc '\
		XDEBUG_MODE=coverage ./vendor/bin/phpunit tests/Integration --coverage-text'

# =============================================================================
# API Test Coverage
# =============================================================================

.PHONY: coverage-api
coverage-api: ## Generate code coverage for API tests (HTML + Clover)
	@echo "Generating API test coverage..."
	@mkdir -p $(COVERAGE_DIR)/api
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc '\
		mkdir -p $(COVERAGE_DIR)/api/html && \
		XDEBUG_MODE=coverage ./vendor/bin/phpunit tests/Api \
			--coverage-html=$(COVERAGE_DIR)/api/html \
			--coverage-clover=$(COVERAGE_DIR)/api/clover.xml \
			--coverage-text'
	@echo ""
	@echo "API test coverage generated:"
	@echo "  - HTML Report: $(COVERAGE_DIR)/api/html/index.html"
	@echo "  - Clover XML:  $(COVERAGE_DIR)/api/clover.xml"

# =============================================================================
# Feature Test Coverage
# =============================================================================

.PHONY: coverage-feature
coverage-feature: ## Generate code coverage for Feature tests (HTML + Clover)
	@echo "Generating Feature test coverage..."
	@mkdir -p $(COVERAGE_DIR)/feature
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc '\
		mkdir -p $(COVERAGE_DIR)/feature/html && \
		XDEBUG_MODE=coverage ./vendor/bin/phpunit tests/Feature \
			--coverage-html=$(COVERAGE_DIR)/feature/html \
			--coverage-clover=$(COVERAGE_DIR)/feature/clover.xml \
			--coverage-text'
	@echo ""
	@echo "Feature test coverage generated:"
	@echo "  - HTML Report: $(COVERAGE_DIR)/feature/html/index.html"
	@echo "  - Clover XML:  $(COVERAGE_DIR)/feature/clover.xml"

# =============================================================================
# Combined Coverage
# =============================================================================

.PHONY: coverage-all
coverage-all: ## Generate combined code coverage for ALL tests (HTML + Clover)
	@echo "Generating combined coverage for all tests..."
	@mkdir -p $(COVERAGE_DIR)/all
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc '\
		mkdir -p $(COVERAGE_DIR)/all/html && \
		XDEBUG_MODE=coverage ./vendor/bin/phpunit \
			--coverage-html=$(COVERAGE_DIR)/all/html \
			--coverage-clover=$(COVERAGE_DIR)/all/clover.xml \
			--coverage-text'
	@echo ""
	@echo "Combined coverage generated:"
	@echo "  - HTML Report: $(COVERAGE_DIR)/all/html/index.html"
	@echo "  - Clover XML:  $(COVERAGE_DIR)/all/clover.xml"

.PHONY: coverage-all-text
coverage-all-text: ## Quick combined coverage (console output only, faster)
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc '\
		XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-text'

# =============================================================================
# PHPUnit Testsuite-based Coverage
# =============================================================================

.PHONY: coverage-testsuite
coverage-testsuite: ## Generate coverage for specific testsuite (usage: make coverage-testsuite SUITE=Unit)
	@if [ -z "$(SUITE)" ]; then \
		echo "ERROR: SUITE parameter required. Usage: make coverage-testsuite SUITE=Unit"; \
		echo "Available testsuites: Unit, Integration, Functional, Api, Feature"; \
		exit 1; \
	fi
	@echo "Generating coverage for testsuite: $(SUITE)"
	@mkdir -p $(COVERAGE_DIR)/$(SUITE)
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc '\
		mkdir -p $(COVERAGE_DIR)/$(SUITE)/html && \
		XDEBUG_MODE=coverage ./vendor/bin/phpunit --testsuite=$(SUITE) \
			--coverage-html=$(COVERAGE_DIR)/$(SUITE)/html \
			--coverage-clover=$(COVERAGE_DIR)/$(SUITE)/clover.xml \
			--coverage-text'
	@echo ""
	@echo "Coverage for $(SUITE) generated:"
	@echo "  - HTML Report: $(COVERAGE_DIR)/$(SUITE)/html/index.html"
	@echo "  - Clover XML:  $(COVERAGE_DIR)/$(SUITE)/clover.xml"

# =============================================================================
# CI/CD Coverage (Cobertura format for GitLab/Jenkins)
# =============================================================================

.PHONY: coverage-ci
coverage-ci: ## Generate CI-friendly coverage (Cobertura + Clover for GitLab/Jenkins)
	@echo "Generating CI coverage reports..."
	@mkdir -p $(COVERAGE_DIR)/ci
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc '\
		mkdir -p $(COVERAGE_DIR)/ci && \
		XDEBUG_MODE=coverage ./vendor/bin/phpunit tests/Unit \
			--coverage-clover=$(COVERAGE_DIR)/ci/clover.xml \
			--coverage-cobertura=$(COVERAGE_DIR)/ci/cobertura.xml \
			--log-junit=$(COVERAGE_DIR)/ci/junit.xml'
	@echo ""
	@echo "CI coverage reports generated:"
	@echo "  - Clover XML:   $(COVERAGE_DIR)/ci/clover.xml"
	@echo "  - Cobertura:    $(COVERAGE_DIR)/ci/cobertura.xml"
	@echo "  - JUnit XML:    $(COVERAGE_DIR)/ci/junit.xml"

# =============================================================================
# Cleanup
# =============================================================================

.PHONY: coverage-clean
coverage-clean: ## Remove all coverage reports
	@echo "Cleaning coverage reports..."
	rm -rf $(COVERAGE_DIR)
	@echo "Coverage reports removed."

# =============================================================================
# Help
# =============================================================================

.PHONY: coverage-help
coverage-help: ## Show coverage command help
	@echo "Code Coverage Commands:"
	@echo ""
	@echo "  Individual Test Types:"
	@echo "    make coverage-unit          - Unit test coverage (HTML + Clover)"
	@echo "    make coverage-functional    - Functional test coverage"
	@echo "    make coverage-integration   - Integration test coverage"
	@echo "    make coverage-api           - API test coverage"
	@echo "    make coverage-feature       - Feature test coverage"
	@echo ""
	@echo "  Quick Coverage (console only, faster):"
	@echo "    make coverage-unit-text"
	@echo "    make coverage-functional-text"
	@echo "    make coverage-integration-text"
	@echo "    make coverage-all-text"
	@echo ""
	@echo "  Combined:"
	@echo "    make coverage-all           - All tests combined"
	@echo "    make coverage-testsuite SUITE=Unit - Specific testsuite"
	@echo ""
	@echo "  CI/CD:"
	@echo "    make coverage-ci            - Cobertura + Clover for CI systems"
	@echo ""
	@echo "  Utilities:"
	@echo "    make coverage-clean         - Remove all coverage reports"
	@echo ""
	@echo "  Output location: var/coverage/"
