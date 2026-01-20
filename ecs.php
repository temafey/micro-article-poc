<?php

declare(strict_types=1);

use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Option;

return ECSConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withRootFiles()

    // Base coding standards
    ->withPreparedSets(psr12: true, common: true, cleanCode: true, symplify: true, strict: true)

    // PHP 8.4 migration and framework-specific rules
    ->withPhpCsFixerSets(php84Migration: true, symfony: true, doctrineAnnotation: true)

    // Configure cache paths - useful for CI caching
    ->withCache(directory: sys_get_temp_dir() . '/_changed_files_detector_tests', namespace: getcwd())

    // Print contents with specific indent rules
    ->withSpacing(indentation: Option::INDENTATION_SPACES, lineEnding: PHP_EOL)

    // Parallel processing configuration
    ->withParallel(timeoutSeconds: 120, maxNumberOfProcess: 32, jobSize: 20);
