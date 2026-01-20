<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration for transactional outbox table.
 *
 * Creates the outbox table for the transactional outbox pattern,
 * ensuring reliable message delivery for domain events and task commands.
 *
 * @see docs/tasks/phase-14-transactional-outbox/TASK-14.1-database-core-components.md
 */
final class Version20260115000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create transactional outbox table for reliable message delivery';
    }

    public function up(Schema $schema): void
    {
        // Create outbox table
        $this->addSql('
            CREATE TABLE IF NOT EXISTS outbox (
                id UUID PRIMARY KEY,
                message_type VARCHAR(10) NOT NULL,
                aggregate_type VARCHAR(255) NOT NULL,
                aggregate_id UUID NOT NULL,
                event_type VARCHAR(255) NOT NULL,
                event_payload JSONB NOT NULL,
                topic VARCHAR(255) NOT NULL,
                routing_key VARCHAR(255) NOT NULL,
                created_at TIMESTAMP(6) WITHOUT TIME ZONE NOT NULL,
                published_at TIMESTAMP(6) WITHOUT TIME ZONE NULL,
                retry_count INTEGER NOT NULL DEFAULT 0,
                last_error TEXT NULL,
                next_retry_at TIMESTAMP(6) WITHOUT TIME ZONE NULL,
                sequence_number BIGINT NOT NULL DEFAULT 0,
                CONSTRAINT chk_message_type CHECK (message_type IN (\'EVENT\', \'TASK\'))
            )
        ');

        // Primary index for polling unpublished messages
        // Used by the publisher to find messages ready for delivery
        // Covers: published_at IS NULL AND (next_retry_at IS NULL OR next_retry_at <= NOW())
        // Ordered by sequence_number for FIFO processing
        $this->addSql('
            CREATE INDEX IF NOT EXISTS idx_outbox_unpublished
            ON outbox (published_at, next_retry_at, sequence_number)
            WHERE published_at IS NULL
        ');

        // Index for cleanup of old published messages
        // Used by cleanup job to delete messages older than retention period
        $this->addSql('
            CREATE INDEX IF NOT EXISTS idx_outbox_cleanup
            ON outbox (published_at)
            WHERE published_at IS NOT NULL
        ');

        // Index for querying by aggregate
        // Useful for debugging and monitoring specific entities
        $this->addSql('
            CREATE INDEX IF NOT EXISTS idx_outbox_aggregate
            ON outbox (aggregate_type, aggregate_id)
        ');

        // Index for querying by message type
        // Allows efficient filtering of events vs tasks
        $this->addSql('
            CREATE INDEX IF NOT EXISTS idx_outbox_message_type
            ON outbox (message_type)
            WHERE published_at IS NULL
        ');

        // Index for querying by event type
        // Useful for monitoring specific event types
        $this->addSql('
            CREATE INDEX IF NOT EXISTS idx_outbox_event_type
            ON outbox (event_type)
        ');

        // Index for retry monitoring
        // Used to identify stuck/failing messages
        $this->addSql('
            CREATE INDEX IF NOT EXISTS idx_outbox_failed
            ON outbox (retry_count, last_error)
            WHERE retry_count > 0 AND published_at IS NULL
        ');

        // Sequence for ordered message processing (auto-increment simulation)
        $this->addSql('CREATE SEQUENCE IF NOT EXISTS outbox_sequence_seq START WITH 1 INCREMENT BY 1');

        // Add comment to table for documentation
        $this->addSql("COMMENT ON TABLE outbox IS 'Transactional outbox for reliable message delivery (events and tasks)'");
        $this->addSql("COMMENT ON COLUMN outbox.message_type IS 'Discriminator: EVENT for domain events, TASK for commands'");
        $this->addSql("COMMENT ON COLUMN outbox.sequence_number IS 'Monotonically increasing sequence for ordered processing'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP SEQUENCE IF EXISTS outbox_sequence_seq');
        $this->addSql('DROP TABLE IF EXISTS outbox');
    }
}
