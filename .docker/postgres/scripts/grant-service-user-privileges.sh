#!/bin/bash
# =============================================================================
# Grant Service User Privileges
# =============================================================================
# This script grants DML privileges (SELECT, INSERT, UPDATE, DELETE) to the
# service user on all existing tables and sequences in the public schema.
#
# WHY THIS IS NEEDED:
# - The init script (01-create-service-user.sh) sets DEFAULT PRIVILEGES
# - However, `make setup-db` drops and recreates the database
# - This loses all DEFAULT PRIVILEGES that were set
# - Migrations create tables but service user has zero grants
# - This script must run AFTER migrations to restore privileges
#
# USAGE:
#   ./grant-service-user-privileges.sh
#   make grant-service-privileges
#
# Part of Phase 9: Infrastructure Modernization
# =============================================================================

set -euo pipefail

# Configuration from environment or defaults
POSTGRES_USER="${POSTGRES_USER:-postgres}"
POSTGRES_DB="${POSTGRES_DB:-news}"
SERVICE_USER="${APP_DATABASE_SERVICE_LOGIN:-news_service}"

echo "=== Granting privileges to service user: ${SERVICE_USER} ==="
echo "Database: ${POSTGRES_DB}"
echo "Admin user: ${POSTGRES_USER}"

# Execute privilege grants
psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<-EOSQL
    -- Grant DML privileges on all existing tables
    GRANT SELECT, INSERT, UPDATE, DELETE ON ALL TABLES IN SCHEMA public TO ${SERVICE_USER};

    -- Grant sequence usage for auto-increment columns
    GRANT USAGE, SELECT ON ALL SEQUENCES IN SCHEMA public TO ${SERVICE_USER};

    -- Set DEFAULT privileges for future tables created by postgres user
    ALTER DEFAULT PRIVILEGES FOR ROLE ${POSTGRES_USER} IN SCHEMA public
        GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO ${SERVICE_USER};

    -- Set DEFAULT privileges for future sequences
    ALTER DEFAULT PRIVILEGES FOR ROLE ${POSTGRES_USER} IN SCHEMA public
        GRANT USAGE, SELECT ON SEQUENCES TO ${SERVICE_USER};

    -- Verify grants were applied
    SELECT
        schemaname,
        tablename,
        tableowner,
        has_table_privilege('${SERVICE_USER}', schemaname || '.' || tablename, 'SELECT') as can_select,
        has_table_privilege('${SERVICE_USER}', schemaname || '.' || tablename, 'INSERT') as can_insert,
        has_table_privilege('${SERVICE_USER}', schemaname || '.' || tablename, 'UPDATE') as can_update,
        has_table_privilege('${SERVICE_USER}', schemaname || '.' || tablename, 'DELETE') as can_delete
    FROM pg_tables
    WHERE schemaname = 'public'
    ORDER BY tablename;
EOSQL

echo "=== Privileges granted successfully ==="
