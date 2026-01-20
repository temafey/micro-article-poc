<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Service\Postman;

/**
 * Postman Generator Service Interface.
 *
 * Main orchestrator for generating Postman collections from OpenAPI specifications.
 * Follows Single Responsibility Principle - coordinates collection generation process.
 */
interface PostmanGeneratorServiceInterface
{
    /**
     * Generates Postman collections for all entities from API documentation.
     *
     * Creates separate collection files for commands and queries per entity.
     * Files are saved to tests/postman/entities/ directory.
     *
     * @param string $apiVersion      API version (e.g., 'v1', 'v2')
     * @param string $outputDirectory Output directory path
     *
     * @return array<string,string> Generated file paths keyed by entity name
     */
    public function generateCollections(string $apiVersion, string $outputDirectory): array;

    /**
     * Generates Postman collections and environment file.
     *
     * Creates separate collection files for commands and queries per entity,
     * plus an environment file with all variables used in collections.
     *
     * @param string $apiVersion            API version (e.g., 'v1', 'v2')
     * @param string $collectionsDirectory  Collections output directory path
     * @param string $environmentsDirectory Environments output directory path
     * @param string $environmentName       Environment name (e.g., 'local-dev', 'staging')
     * @param array  $customValues          Custom values to override defaults
     *
     * @return array{collections: array<string,string>, environment: string} Generated file paths
     */
    public function generateCollectionsWithEnvironment(
        string $apiVersion,
        string $collectionsDirectory,
        string $environmentsDirectory,
        string $environmentName = 'local-dev',
        array $customValues = [],
    ): array;
}
