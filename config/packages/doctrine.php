<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * Doctrine configuration migrated from YAML to PHP for OPcache optimization.
 *
 * Two-connection architecture for security:
 * - default: Service user with RESTRICTED privileges (DML only)
 * - migration: Root user with FULL privileges (DDL + DML)
 *
 * @see docs/updates/phase-5-advanced/TASK-015-php-array-config.md
 * @see docs/adr/ADR-XXX-database-user-separation.md
 */
return static function (ContainerConfigurator $container): void {
    $container->extension('doctrine', [
        'dbal' => [
            'default_connection' => 'default',
            'connections' => [
                // Default connection: Service user with RESTRICTED privileges (DML only)
                // Used by: Application runtime, queries, repositories
                // Privileges: SELECT, INSERT, UPDATE, DELETE
                // Restrictions: No CREATE, ALTER, DROP, TRUNCATE
                'default' => [
                    'driver' => 'pdo_pgsql',
                    'server_version' => '18',
                    'charset' => 'UTF8',
                    'host' => '%env(APP_DATABASE_HOST)%',
                    'port' => '%env(APP_DATABASE_PORT)%',
                    'dbname' => '%env(APP_DATABASE_NAME)%',
                    'user' => '%env(APP_DATABASE_SERVICE_LOGIN)%',
                    'password' => '%env(APP_DATABASE_SERVICE_PASSWORD)%',
                    'mapping_types' => [
                        'enum' => 'string',
                    ],
                ],

                // Migration connection: Root user with FULL privileges (DDL + DML)
                // Used by: doctrine:migrations:migrate, doctrine:schema:update
                // Privileges: All DDL operations (CREATE, ALTER, DROP, TRUNCATE)
                'migration' => [
                    'driver' => 'pdo_pgsql',
                    'server_version' => '18',
                    'charset' => 'UTF8',
                    'host' => '%env(APP_DATABASE_HOST)%',
                    'port' => '%env(APP_DATABASE_PORT)%',
                    'dbname' => '%env(APP_DATABASE_NAME)%',
                    'user' => '%env(APP_DATABASE_ROOT_LOGIN)%',
                    'password' => '%env(APP_DATABASE_ROOT_PASSWORD)%',
                    'mapping_types' => [
                        'enum' => 'string',
                    ],
                ],
            ],

            // UUID type registrations from Ramsey\Uuid\Doctrine
            'types' => [
                'uuid' => 'Ramsey\Uuid\Doctrine\UuidType',
                'uuid_binary' => 'Ramsey\Uuid\Doctrine\UuidBinaryType',
                'uuid_binary_ordered_time' => 'Ramsey\Uuid\Doctrine\UuidBinaryOrderedTimeType',
            ],
        ],

        'orm' => [
            'naming_strategy' => 'doctrine.orm.naming_strategy.underscore',
            'auto_mapping' => true,
            'controller_resolver' => [
                'auto_mapping' => false,
            ],

            // Entity mappings for DDD Read Models
            'mappings' => [
                'MicroArticleDomainArticleReadModel' => [
                    'type' => 'attribute',
                    'dir' => '%kernel.project_dir%/src/Article/Domain/ReadModel',
                    'is_bundle' => false,
                    'prefix' => 'Micro\Article\Domain\ReadModel',
                ],
            ],
        ],
    ]);
};
