#!/bin/bash
set -e

# =============================================================================
# PostgreSQL Service User Initialization Script
# =============================================================================
# This script creates a restricted service user for application runtime.
# The service user can only perform DML operations (SELECT, INSERT, UPDATE, DELETE)
# and CANNOT modify database schema (no CREATE, ALTER, DROP, TRUNCATE).
#
# User Privilege Matrix:
# +------------------+------------+--------------+
# | Operation        | Root User  | Service User |
# +------------------+------------+--------------+
# | SELECT           | YES        | YES          |
# | INSERT           | YES        | YES          |
# | UPDATE           | YES        | YES          |
# | DELETE           | YES        | YES          |
# | CREATE TABLE     | YES        | NO           |
# | ALTER TABLE      | YES        | NO           |
# | DROP TABLE       | YES        | NO           |
# | TRUNCATE         | YES        | NO           |
# | CREATE INDEX     | YES        | NO           |
# | RUN MIGRATIONS   | YES        | NO           |
# +------------------+------------+--------------+
# =============================================================================

echo "==> Creating service user with restricted privileges..."

# Validate required environment variables
if [ -z "$APP_DATABASE_SERVICE_LOGIN" ]; then
    echo "WARNING: APP_DATABASE_SERVICE_LOGIN not set, skipping service user creation"
    exit 0
fi

if [ -z "$APP_DATABASE_SERVICE_PASSWORD" ]; then
    echo "ERROR: APP_DATABASE_SERVICE_PASSWORD is required"
    exit 1
fi

if [ -z "$POSTGRES_DB" ]; then
    echo "ERROR: POSTGRES_DB is required"
    exit 1
fi

# Connect as superuser and create service user
psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    -- ==========================================================================
    -- Step 1: Create service user (if not exists)
    -- ==========================================================================
    DO \$\$
    BEGIN
        IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = '${APP_DATABASE_SERVICE_LOGIN}') THEN
            CREATE ROLE ${APP_DATABASE_SERVICE_LOGIN} WITH
                LOGIN
                PASSWORD '${APP_DATABASE_SERVICE_PASSWORD}'
                NOSUPERUSER
                NOCREATEDB
                NOCREATEROLE
                NOINHERIT
                NOREPLICATION
                CONNECTION LIMIT 50;
            RAISE NOTICE 'Service user "${APP_DATABASE_SERVICE_LOGIN}" created successfully';
        ELSE
            -- Update password if user already exists
            ALTER ROLE ${APP_DATABASE_SERVICE_LOGIN} WITH PASSWORD '${APP_DATABASE_SERVICE_PASSWORD}';
            RAISE NOTICE 'Service user "${APP_DATABASE_SERVICE_LOGIN}" already exists, password updated';
        END IF;
    END
    \$\$;

    -- ==========================================================================
    -- Step 2: Grant connection privilege to database
    -- ==========================================================================
    GRANT CONNECT ON DATABASE "${POSTGRES_DB}" TO ${APP_DATABASE_SERVICE_LOGIN};

    -- ==========================================================================
    -- Step 3: Grant USAGE on public schema (required to see objects)
    -- ==========================================================================
    GRANT USAGE ON SCHEMA public TO ${APP_DATABASE_SERVICE_LOGIN};

    -- ==========================================================================
    -- Step 4: Grant DML privileges on ALL existing tables
    -- ==========================================================================
    GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO ${APP_DATABASE_SERVICE_LOGIN};

    -- ==========================================================================
    -- Step 5: Grant USAGE on ALL existing sequences (for SERIAL/IDENTITY columns)
    -- ==========================================================================
    GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO ${APP_DATABASE_SERVICE_LOGIN};

    -- ==========================================================================
    -- Step 6: Set DEFAULT privileges for FUTURE tables (created by migrations)
    -- ==========================================================================
    ALTER DEFAULT PRIVILEGES IN SCHEMA public
        GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO ${APP_DATABASE_SERVICE_LOGIN};

    ALTER DEFAULT PRIVILEGES IN SCHEMA public
        GRANT USAGE, SELECT ON SEQUENCES TO ${APP_DATABASE_SERVICE_LOGIN};

    -- ==========================================================================
    -- Step 7: EXPLICITLY REVOKE dangerous privileges (defense in depth)
    -- ==========================================================================
    -- Revoke CREATE on schema (cannot create tables)
    REVOKE CREATE ON SCHEMA public FROM ${APP_DATABASE_SERVICE_LOGIN};

    -- Revoke TRUNCATE (data deletion without WHERE clause)
    REVOKE TRUNCATE ON ALL TABLES IN SCHEMA public FROM ${APP_DATABASE_SERVICE_LOGIN};

    ALTER DEFAULT PRIVILEGES IN SCHEMA public
        REVOKE TRUNCATE ON TABLES FROM ${APP_DATABASE_SERVICE_LOGIN};

    -- ==========================================================================
    -- Step 8: Verification query
    -- ==========================================================================
    SELECT
        'Service user privileges configured' as status,
        r.rolname as username,
        r.rolsuper as is_superuser,
        r.rolcreatedb as can_create_db,
        r.rolcreaterole as can_create_role,
        r.rolconnlimit as connection_limit
    FROM pg_roles r
    WHERE r.rolname = '${APP_DATABASE_SERVICE_LOGIN}';

EOSQL

echo "==> Service user '${APP_DATABASE_SERVICE_LOGIN}' configured successfully!"
echo "==> Privileges: SELECT, INSERT, UPDATE, DELETE (DML only)"
echo "==> Restrictions: No CREATE, ALTER, DROP, TRUNCATE (no DDL)"
