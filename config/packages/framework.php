<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * Framework configuration migrated from YAML to PHP for OPcache optimization.
 *
 * Benefits:
 * - Type safety with IDE autocomplete
 * - OPcache compatible (no YAML parsing at runtime)
 * - 10-20% faster config loading
 *
 * @see docs/updates/phase-5-advanced/TASK-015-php-array-config.md
 */
return static function (ContainerConfigurator $container): void {
    $container->extension('framework', [
        'secret' => '%app.secret%',

        // PHP error logging configuration
        'php_errors' => [
            'log' => true,
            // PHP errors are logged, deprecation warnings handled via error_reporting in php.ini
        ],

        // Trusted hosts (empty for API-only service)
        'trusted_hosts' => [],

        // Serializer configuration (required for MapRequestPayload)
        'serializer' => [
            'enabled' => true,
        ],

        // Property access configuration (required for serializer)
        'property_access' => [
            'enabled' => true,
        ],

        // Property info configuration (required for serializer type extraction)
        'property_info' => [
            'enabled' => true,
        ],
    ]);
};
