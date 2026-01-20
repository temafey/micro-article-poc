<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Create article read model table with optimized schema.
 *
 * This table serves as a denormalized read model (projection) for the Article aggregate
 * in the Event Sourcing architecture. Data is projected from the events table.
 *
 * @see \Micro\Article\Domain\Entity\ArticleEntity
 */
final class Version20251218222631 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create article read model table with indexes and constraints';
    }

    public function up(Schema $schema): void
    {
        // Create article table with proper data types and constraints
        $this->addSql('
            CREATE TABLE article (
                uuid UUID NOT NULL,
                title VARCHAR(255) NOT NULL,
                short_description VARCHAR(500) DEFAULT NULL,
                description TEXT DEFAULT NULL,
                slug VARCHAR(255) NOT NULL,
                event_id INTEGER DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT \'draft\',
                published_at TIMESTAMPTZ DEFAULT NULL,
                archived_at TIMESTAMPTZ DEFAULT NULL,
                created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),

                CONSTRAINT article_pkey PRIMARY KEY (uuid),
                CONSTRAINT article_slug_unique UNIQUE (slug),
                CONSTRAINT article_status_check CHECK (status IN (\'draft\', \'published\', \'archived\'))
            )
        ');

        // NOTE: No standalone index on status column - low cardinality (3 values)
        // means ~33% selectivity, PostgreSQL will prefer sequential scan.
        // Use partial indexes instead (see idx_article_published_listing below).

        // Index for published_at ordering (listing pages, RSS feeds)
        $this->addSql('CREATE INDEX idx_article_published_at ON article (published_at DESC NULLS LAST)');

        // Index for created_at ordering (admin panels, recent content)
        $this->addSql('CREATE INDEX idx_article_created_at ON article (created_at DESC)');

        // Partial index for published article listing (most common query pattern)
        $this->addSql('
            CREATE INDEX idx_article_published_listing
            ON article (published_at DESC)
            WHERE status = \'published\'
        ');

        // Covering index for slug lookups (includes commonly selected fields)
        $this->addSql('
            CREATE INDEX idx_article_slug_covering
            ON article (slug)
            INCLUDE (title, status, published_at)
        ');

        // BRIN index for time-series queries (efficient for large datasets)
        $this->addSql('CREATE INDEX idx_article_created_at_brin ON article USING BRIN (created_at)');

        // Partial index for event_id lookups (excludes NULL values for smaller index)
        // Use case: "Get all article for event X", JOIN with events table
        $this->addSql('CREATE INDEX idx_article_event_id ON article (event_id) WHERE event_id IS NOT NULL');

        // Table and column comments for documentation
        $this->addSql(
            'COMMENT ON TABLE article IS \'Read model projection for article articles (Event Sourcing architecture)\''
        );
        $this->addSql('COMMENT ON COLUMN article.uuid IS \'Aggregate root identifier matching event store\'');
        $this->addSql('COMMENT ON COLUMN article.slug IS \'SEO-friendly URL identifier, auto-generated from title\'');
        $this->addSql('COMMENT ON COLUMN article.event_id IS \'Optional reference to external event system\'');
        $this->addSql('COMMENT ON COLUMN article.status IS \'Lifecycle state: draft -> published -> archived\'');

        // Configure autovacuum for read model (high update frequency from projections)
        $this->addSql('
            ALTER TABLE article SET (
                autovacuum_vacuum_scale_factor = 0.05,
                autovacuum_analyze_scale_factor = 0.02
            )
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS article CASCADE');
    }
}
