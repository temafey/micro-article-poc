<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration for Identity module read model tables.
 */
final class Version20260106151823 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create Identity module read model tables (user_read_model, jwt_token_read_model)';
    }

    public function up(Schema $schema): void
    {
        // Create user_read_model table
        $this->addSql('
            CREATE TABLE IF NOT EXISTS user_read_model (
                uuid UUID PRIMARY KEY,
                username VARCHAR(100) NOT NULL,
                email VARCHAR(255) NOT NULL,
                normalized_email VARCHAR(255) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                status VARCHAR(50) NOT NULL DEFAULT \'pending\',
                first_name VARCHAR(100) NULL,
                last_name VARCHAR(100) NULL,
                roles JSONB NOT NULL DEFAULT \'[]\',
                email_verified_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
                last_login_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
                password_changed_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
                deactivated_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
                event_id INTEGER NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // Create indexes for user_read_model
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS idx_user_username ON user_read_model (username)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS idx_user_email ON user_read_model (email)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_user_normalized_email ON user_read_model (normalized_email)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_user_status ON user_read_model (status)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_user_created_at ON user_read_model (created_at)');

        // Create jwt_token_read_model table
        $this->addSql('
            CREATE TABLE IF NOT EXISTS jwt_token_read_model (
                uuid UUID PRIMARY KEY,
                subject VARCHAR(255) NOT NULL,
                token_type VARCHAR(50) NOT NULL,
                token_hash VARCHAR(128) NOT NULL,
                issuer VARCHAR(255) NULL,
                audience VARCHAR(255) NULL,
                issued_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                revoked_at TIMESTAMP(0) WITHOUT TIME ZONE NULL,
                event_id INTEGER NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ');

        // Create indexes for jwt_token_read_model
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_jwt_subject ON jwt_token_read_model (subject)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_jwt_token_type ON jwt_token_read_model (token_type)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS idx_jwt_token_hash ON jwt_token_read_model (token_hash)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_jwt_expires_at ON jwt_token_read_model (expires_at)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_jwt_revoked_at ON jwt_token_read_model (revoked_at)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_jwt_subject_type ON jwt_token_read_model (subject, token_type)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS jwt_token_read_model');
        $this->addSql('DROP TABLE IF EXISTS user_read_model');
    }
}
