<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Service\Postman;

interface PostmanEnvironmentGeneratorInterface
{
    /**
     * Generate Postman environment file based on API schema and collections.
     *
     * @param string $apiVersion      API version (v1, v2, etc.)
     * @param array  $collections     Generated collection data
     * @param string $environmentName Environment name (local-dev, staging, etc.)
     * @param array  $customValues    Custom values to override defaults
     *
     * @return array Environment data structure
     */
    public function generateEnvironment(
        string $apiVersion,
        array $collections,
        string $environmentName = 'local-dev',
        array $customValues = [],
    ): array;

    /**
     * Extract all variables used in collections.
     *
     * @param array $collections Array of collection data
     *
     * @return array<string> List of unique variable names
     */
    public function extractVariables(array $collections): array;
}
