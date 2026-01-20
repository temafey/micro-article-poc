<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

class Version202401300103819 extends AbstractMigration
{
    /**
     * Migration description.
     */
    public function getDescription(): string
    {
        return 'Create table `sagas`';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE sagas
            (
                id          uuid        not null constraint sagas_pkey primary key,
                saga_id     varchar(100) not null,
                status      integer     not null,
                values      jsonb       not null,
                recorded_on timestamp   without time zone default current_timestamp
            );
        ');

        $this->addSql('CREATE INDEX IF NOT EXISTS sagas_id ON sagas (saga_id);');
        $this->addSql('CREATE INDEX IF NOT EXISTS sagas_values_index ON sagas USING GIN (values);');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE sagas');
    }
}
