<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version202401300103818 extends AbstractMigration
{
    /**
     * Migration description.
     */
    public function getDescription(): string
    {
        return 'Create table `snapshots`';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            '
            CREATE TABLE snapshots
            (
                id          serial       not null constraint snapshots_pkey primary key,
                uuid        uuid         not null,
                playhead    integer      not null,
                payload     text         not null,
                metadata    text         not null,
                recorded_on timestamp    without time zone default current_timestamp,
                type        varchar(255) not null
            );
        '
        );

        $this->addSql(
            'CREATE INDEX IF NOT EXISTS snapshots_uniq_uuid_playhead_index
                on snapshots (uuid, playhead);'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE snapshots');
    }
}
