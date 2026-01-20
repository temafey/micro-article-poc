.PHONY: setup-db
setup-db: ## recreate database and grant service user privileges
	$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console d:d:d --force --if-exists --connection=migration'
	$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console d:d:c --connection=migration'
	$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console d:m:m -n'
	@$(MAKE) grant-service-privileges

.PHONY: grant-service-privileges
grant-service-privileges: ## Grant DML privileges to service user on all tables
	@echo "Granting privileges to service user..."
	$(DCC) exec ${APP_DATABASE_HOST} sh -c '/scripts/grant-service-user-privileges.sh'
	@echo "Service user privileges granted successfully!"

.PHONY: schema-validate
schema-validate: ## validate database schema
	$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc "./bin/console d:s:v"

.PHONY: migration-generate
migration-generate: ## generate new database migration
	$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console d:m:g'

.PHONY: migration-migrate
migration-migrate: ## execute a migration to a specified version or the latest available version
	$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console d:m:m -n'

.PHONY: migration-diff
migration-diff: ## generate a migration by comparing your current database to your mapping information.
	$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console d:m:diff -n'

.PHONY: setup-enqueue
setup-enqueue: ## setup enqueue (without deprecation warnings)
	@echo "Setting up Enqueue brokers..."
	@$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console enqueue:setup-broker -c task' 2>/dev/null && echo "✓ Task broker setup completed"
	@$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console enqueue:setup-broker -c event' 2>/dev/null && echo "✓ Event broker setup completed"
	@$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console enqueue:setup-broker -c queueevent' 2>/dev/null && echo "✓ Queue Event broker setup completed"
	@$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console enqueue:setup-broker -c taskevent' 2>/dev/null && echo "✓ Task Event broker setup completed"
	@$(DCC) run --rm $(DOCKER_PHP_CONTAINER_NAME) sh -lc './bin/console enqueue:setup-broker -c global.article' 2>/dev/null && echo "✓ Global Article broker setup completed"
	@echo "All Enqueue brokers have been set up successfully!"

database-setup-db:
	$(DCC) exec ${APP_DATABASE_HOST} sh -c "if PGPASSWORD=$(POSTGRES_PASSWORD); psql -U $(POSTGRES_USER) postgres -XtAc '\l' | grep $(POSTGRES_DB); then echo DB $(POSTGRES_DB) already exists; else PGPASSWORD=$(POSTGRES_PASSWORD) createdb -U $(POSTGRES_USER) postgres --echo $(POSTGRES_DB); fi"

database-shell: ## POSTGRES console
	$(DCC) exec ${APP_DATABASE_HOST} psql -U ${APP_DATABASE_LOGIN} ${APP_DATABASE_NAME}

database-console: ## POSTGRES shell
	$(DCC) exec ${APP_DATABASE_HOST} psql -U ${APP_DATABASE_LOGIN} ${APP_DATABASE_NAME} -XtAc "\l"
