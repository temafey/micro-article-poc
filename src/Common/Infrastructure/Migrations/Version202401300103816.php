<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version202401300103816 extends AbstractMigration
{
    /**
     * Migration description.
     */
    public function getDescription(): string
    {
        return 'Create tables `enqueue_job_unique` and `enqueue_job`';
    }

    public function up(Schema $schema): void
    {
        $schema->createSequence('enqueue_job_id_seq');
        $table = $schema->createTable('enqueue_job_unique');
        $table->addColumn('name', 'string', [
            'length' => 255,
        ]);
        $table->setPrimaryKey(['name']);

        $this->addSql('
            CREATE TABLE enqueue_job
            (
              id          int           not null,
              root_job_id int           default null,
              owner_id    varchar(255)  default null,
              name        varchar(255)  not null,
              status      varchar(255)  not null,
              interrupted boolean       not null,
              "unique"    boolean       not null,
              started_at  timestamp(0)  without time zone default null,
              created_at  timestamp(0)  without time zone not null,
              stopped_at  timestamp(0)  without time zone default null,
              data        json          default null,
              PRIMARY KEY (id)
            );
        ');

        $this->addSql('CREATE INDEX IDX_C206D624295AA268 ON enqueue_job (root_job_id);');
        $this->addSql('COMMENT ON COLUMN enqueue_job.data IS \'(DC2Type:json)\';');
        $this->addSql('ALTER TABLE enqueue_job
              ADD CONSTRAINT FK_C206D624295AA268 FOREIGN KEY (root_job_id) REFERENCES enqueue_job (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
        ');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('enqueue_job_unique');
        $schema->dropTable('enqueue_job');
        $schema->dropSequence('enqueue_job_id_seq');
    }
}
