# Static Analysis Makefile targets
# PHP 8.4 + Symfony 8.0 static analysis tools

# =============================================================================
# PHPStan - Static Analysis
# =============================================================================

.PHONY: phpstan
phpstan: ## Run PHPStan static analysis
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc './vendor/bin/phpstan analyse --memory-limit=1G'

.PHONY: phpstan-baseline
phpstan-baseline: ## Regenerate PHPStan baseline
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc './vendor/bin/phpstan analyse --generate-baseline=phpstan-baseline.neon --memory-limit=1G'

.PHONY: phpstan-clear
phpstan-clear: ## Clear PHPStan cache
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc './vendor/bin/phpstan clear-result-cache'

# =============================================================================
# Psalm - Security-Focused Static Analysis
# =============================================================================

.PHONY: psalm
psalm: ## Run Psalm static analysis with baseline
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc './vendor/bin/psalm'

.PHONY: psalm-taint
psalm-taint: ## Run Psalm taint analysis for security vulnerabilities
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc './vendor/bin/psalm --taint-analysis'

.PHONY: psalm-baseline
psalm-baseline: ## Regenerate Psalm baseline
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc './vendor/bin/psalm --set-baseline=psalm-baseline.xml --ignore-baseline'

.PHONY: psalm-clear
psalm-clear: ## Clear Psalm cache
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc './vendor/bin/psalm --clear-cache'

.PHONY: psalm-info
psalm-info: ## Show Psalm configuration and issue types
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc './vendor/bin/psalm --show-info=true'

# =============================================================================
# ECS - Easy Coding Standard
# =============================================================================

.PHONY: ecs-check
ecs-check: ## Run ECS code style check
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc './vendor/bin/ecs check'

.PHONY: ecs-fix
ecs-fix: ## Fix code style issues with ECS
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc './vendor/bin/ecs check --fix'

# =============================================================================
# Rector - Code Modernization
# =============================================================================

.PHONY: rector
rector: ## Run Rector for code modernization (dry-run)
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc './vendor/bin/rector process --dry-run'

.PHONY: rector-fix
rector-fix: ## Apply Rector code modernization
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc './vendor/bin/rector process'

.PHONY: rector-clear
rector-clear: ## Clear Rector cache
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc './vendor/bin/rector clear-cache'

# =============================================================================
# Deptrac - Layer Dependency Analysis
# =============================================================================

.PHONY: deptrac
deptrac: ## Run Deptrac layer dependency analysis
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc './vendor/bin/deptrac analyse --config-file=depfile.yaml'

.PHONY: deptrac-baseline
deptrac-baseline: ## Generate Deptrac baseline for existing violations
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc './vendor/bin/deptrac analyse --config-file=depfile.yaml --formatter=baseline --output=deptrac-baseline.yaml'

.PHONY: deptrac-graph
deptrac-graph: ## Generate Deptrac dependency graph (requires graphviz)
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc './vendor/bin/deptrac analyse --config-file=depfile.yaml --formatter=graphviz-image --output=deptrac-graph.png'

# =============================================================================
# Combined Targets
# =============================================================================

.PHONY: static-analysis
static-analysis: phpstan psalm ecs-check ## Run all static analysis tools (PHPStan + Psalm + ECS)

.PHONY: static-analysis-full
static-analysis-full: phpstan psalm ecs-check rector deptrac ## Run all analysis tools including Rector and Deptrac

.PHONY: security-analysis
security-analysis: psalm-taint ## Run security-focused analysis (Psalm taint analysis)

.PHONY: lint-php
lint-php: ## PHP syntax check for src/ and tests/
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc 'find src tests -name "*.php" -print0 | xargs -0 -n1 php -l'

.PHONY: lint-all
lint-all: lint-php ecs-check phpstan ## Run all linting and static analysis

.PHONY: fix-all
fix-all: ecs-fix rector-fix ## Apply all automatic fixes (ECS + Rector)

.PHONY: clear-cache
clear-cache: phpstan-clear psalm-clear rector-clear infection-clear ## Clear all static analysis caches

# =============================================================================
# Infection - Mutation Testing
# =============================================================================
# Two-step approach: Pre-generate coverage for Unit tests only, then run Infection
# with cached coverage. This avoids Integration test issues (KERNEL_CLASS errors).
# Baseline MSI: 74.04% | Threshold: 70% | See: docs/adr/ADR-011-mutation-testing-strategy.md

.PHONY: infection-coverage
infection-coverage: ## Step 1: Generate PHPUnit coverage for Unit tests (required before mutation testing)
	@echo "Generating PHPUnit coverage for Unit tests..."
	@mkdir -p var/infection/coverage
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc '\
		mkdir -p var/infection/coverage && \
		XDEBUG_MODE=coverage ./vendor/bin/phpunit tests/Unit \
			--coverage-xml=var/infection/coverage/coverage-xml \
			--log-junit=var/infection/coverage/junit.xml'
	@echo "Coverage generated in var/infection/coverage/"

.PHONY: infection
infection: ## Step 2: Run mutation testing with cached coverage (run infection-coverage first)
	@echo "Running mutation testing with Infection..."
	@if [ ! -f "var/infection/coverage/junit.xml" ]; then \
		echo "ERROR: Coverage not found. Run 'make infection-coverage' first."; \
		exit 1; \
	fi
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc '\
		./vendor/bin/infection --threads=4 \
			--coverage=var/infection/coverage \
			--skip-initial-tests'

.PHONY: infection-full
infection-full: infection-coverage infection ## Full mutation testing: generate coverage + run Infection

.PHONY: infection-check
infection-check: ## Verify Unit tests pass before mutation testing
	@echo "Checking Unit test prerequisites for mutation testing..."
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc './vendor/bin/phpunit tests/Unit --stop-on-failure' && \
	echo "Unit tests pass - ready for mutation testing" || \
	(echo "Unit tests failing - fix tests before running Infection" && exit 1)

.PHONY: infection-with-check
infection-with-check: infection-check infection-full ## Run mutation testing with prerequisite check

.PHONY: infection-report
infection-report: infection-coverage ## Generate mutation testing with detailed HTML report
	@echo "Running mutation testing with detailed reporting..."
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc '\
		./vendor/bin/infection --threads=4 \
			--coverage=var/infection/coverage \
			--skip-initial-tests'
	@echo ""
	@echo "Reports generated in var/infection/"
	@echo "  - HTML: var/infection/infection.html"
	@echo "  - JSON: var/infection/infection.json"
	@echo "  - Text: var/infection/infection.log"
	@echo "  - Per-mutator: var/infection/per-mutator.md"

.PHONY: infection-filter
infection-filter: ## Run mutation testing on specific path (usage: make infection-filter FILTER=src/Article/Domain)
	@echo "Running mutation testing on: $(FILTER)"
	@if [ ! -f "var/infection/coverage/junit.xml" ]; then \
		echo "ERROR: Coverage not found. Run 'make infection-coverage' first."; \
		exit 1; \
	fi
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc '\
		./vendor/bin/infection --threads=4 \
			--coverage=var/infection/coverage \
			--skip-initial-tests \
			--filter=$(FILTER)'

.PHONY: infection-min
infection-min: ## Run mutation testing with MSI threshold (usage: make infection-min MSI=70)
	@echo "Running mutation testing with MSI threshold: $(MSI)%"
	@if [ ! -f "var/infection/coverage/junit.xml" ]; then \
		echo "ERROR: Coverage not found. Run 'make infection-coverage' first."; \
		exit 1; \
	fi
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc '\
		./vendor/bin/infection --threads=4 \
			--coverage=var/infection/coverage \
			--skip-initial-tests \
			--min-msi=$(MSI) \
			--min-covered-msi=$(MSI)'

.PHONY: infection-dry-run
infection-dry-run: ## Show what would be mutated without running tests
	@echo "Dry run - showing what would be mutated..."
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc './vendor/bin/infection --threads=4 --dry-run'

.PHONY: infection-clear
infection-clear: ## Clear Infection cache, coverage, and reports
	@echo "Clearing Infection cache and coverage..."
	$(DCC) exec -T $(DOCKER_PHP_CONTAINER_NAME) sh -lc '\
		rm -rf var/infection/tmp/* \
		var/infection/coverage/* \
		var/infection/*.json \
		var/infection/*.html \
		var/infection/*.log \
		var/infection/*.md'
	@echo "Infection cache cleared."
