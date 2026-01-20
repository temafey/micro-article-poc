<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\ValueObject\PhpVersion;

/*
 * Rector configuration for PHP 8.4 + Symfony 8.0 + DDD project.
 *
 * @see https://getrector.com/documentation
 */
return RectorConfig::configure()
    // Paths to process
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])

    // Skip paths that should not be refactored
    ->withSkip([
        // Database migrations should not be modified
        __DIR__ . '/src/Common/Infrastructure/Migrations',

        // Cache and generated files
        __DIR__ . '/var',

        // Vendor dependencies
        __DIR__ . '/vendor',

        // Bootstrap files
        __DIR__ . '/tests/bootstrap.php',
    ])

    // PHP version targeting - use PHP 8.4 features
    ->withPhpSets(php84: true)

    // Prepared rule sets for code quality
    ->withPreparedSets(
        // Remove unused code
        deadCode: true,

        // Improve code quality
        codeQuality: true,

        // Consistent coding style
        codingStyle: true,

        // Add type declarations (return types, param types, property types)
        typeDeclarations: true,

        // Encapsulation improvements
        privatization: true,

        // Simplify conditionals with early returns
        earlyReturn: true,

        // PHPUnit test improvements
        phpunitCodeQuality: true,

        // Doctrine ORM improvements
        doctrineCodeQuality: true,

        // Symfony-specific improvements
        symfonyCodeQuality: true,
    )

    // Additional sets for PHP 8.4 level
    ->withSets([LevelSetList::UP_TO_PHP_84])

    // PHP version for analysis
    ->withPhpVersion(PhpVersion::PHP_84)

    // Parallel processing for faster execution
    ->withParallel(timeoutSeconds: 360, maxNumberOfProcess: 16, jobSize: 20)

    // Import short class names
    ->withImportNames(
        importNames: true,
        importDocBlockNames: true,
        importShortClasses: true,
        removeUnusedImports: true,
    );
