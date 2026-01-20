<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Service\Postman;

final class PostmanEnvironmentGenerator implements PostmanEnvironmentGeneratorInterface
{
    private const array DEFAULT_VALUES = [
        'baseUrl' => 'localhost',
        'contentType' => 'application/json',
        'apiVersion' => 'v2',
    ];

    private const array POSTMAN_DYNAMIC_VARIABLES = [
        '$guid',
        '$timestamp',
        '$isoTimestamp',
        '$randomInt',
        '$randomUUID',
        '$randomEmail',
        '$randomPhoneNumber',
    ];

    public function generateEnvironment(
        string $apiVersion,
        array $collections,
        string $environmentName = 'local-dev',
        array $customValues = [],
    ): array {
        $variables = $this->extractVariables($collections);
        $environmentId = sprintf('%s-env', strtolower(str_replace(' ', '-', $environmentName)));

        $values = [];

        // Add base configuration
        $values[] = $this->createVariable('baseUrl', $customValues['baseUrl'] ?? self::DEFAULT_VALUES['baseUrl']);
        $values[] = $this->createVariable('apiVersion', $customValues['apiVersion'] ?? $apiVersion);
        $values[] = $this->createVariable(
            'contentType',
            $customValues['contentType'] ?? self::DEFAULT_VALUES['contentType']
        );

        // Add variables extracted from collections
        foreach ($variables as $variableName) {
            // Skip base variables already added
            if (in_array($variableName, ['baseUrl', 'apiVersion', 'contentType'], true)) {
                continue;
            }

            $value = $customValues[$variableName] ?? $this->generateDefaultValue($variableName);
            $values[] = $this->createVariable($variableName, $value);
        }

        // Add Postman dynamic variables with static fallbacks
        // Check if any variable uses the dynamic variable syntax
        foreach (self::POSTMAN_DYNAMIC_VARIABLES as $dynamicVar) {
            $usedInValues = array_any(
                $values,
                fn ($valueItem): bool => is_string(
                    $valueItem['value']
                ) && str_contains($valueItem['value'], $dynamicVar)
            );
            // Add fallback value if dynamic variable is used
            if ($usedInValues || in_array(ltrim($dynamicVar, '$'), $variables, true)) {
                $values[] = $this->createVariable($dynamicVar, $this->generateDynamicVariableFallback($dynamicVar));
            }
        }

        return [
            'id' => $environmentId,
            'name' => $environmentName,
            'values' => $values,
            '_postman_variable_scope' => 'environment',
        ];
    }

    public function extractVariables(array $collections): array
    {
        $variables = [];

        foreach ($collections as $collection) {
            $collectionJson = json_encode($collection, JSON_THROW_ON_ERROR);

            // Extract all {{variableName}} patterns
            if (preg_match_all('/\{\{([^}]+)\}\}/', $collectionJson, $matches)) {
                $variables = array_merge($variables, $matches[1]);
            }
        }

        // Return unique, sorted variables
        $variables = array_unique($variables);
        sort($variables);

        return array_values($variables);
    }

    /**
     * Create environment variable structure.
     */
    private function createVariable(string $key, mixed $value, bool $enabled = true): array
    {
        return [
            'key' => $key,
            'value' => $value,
            'enabled' => $enabled,
        ];
    }

    /**
     * Generate default value based on variable name patterns.
     */
    private function generateDefaultValue(string $variableName): string
    {
        // UUID/ID patterns
        if (str_contains($variableName, 'uuid') || str_contains($variableName, 'Uuid')) {
            return '{{$guid}}';
        }

        if (str_contains($variableName, '_id') || str_contains($variableName, 'Id') || $variableName === 'id') {
            return '{{$guid}}';
        }

        // Timestamp patterns
        if (str_contains($variableName, 'timestamp') || str_contains($variableName, 'Timestamp')) {
            return '{{$isoTimestamp}}';
        }

        if (str_contains($variableName, 'date') || str_contains($variableName, 'Date')) {
            return '2024-01-01';
        }

        // Email patterns
        if (str_contains($variableName, 'email') || str_contains($variableName, 'Email')) {
            return '{{$randomEmail}}';
        }

        // Phone patterns
        if (str_contains($variableName, 'phone') || str_contains($variableName, 'Phone')) {
            return '{{$randomPhoneNumber}}';
        }

        // Numeric patterns
        if (str_contains($variableName, 'amount') || str_contains($variableName, 'Amount')) {
            return '100000';
        }

        if (str_contains($variableName, 'age') || str_contains($variableName, 'Age')) {
            return '25';
        }

        if (str_contains($variableName, 'rate') || str_contains($variableName, 'Rate')) {
            return '0.08';
        }

        if (str_contains($variableName, 'score') || str_contains($variableName, 'Score')) {
            return '650';
        }

        if (str_contains($variableName, 'count') || str_contains($variableName, 'Count')) {
            return '10';
        }

        // Text patterns
        if (str_contains($variableName, 'name') || str_contains($variableName, 'Name')) {
            return sprintf('Test %s', ucfirst($variableName));
        }

        if (str_contains($variableName, 'title') || str_contains($variableName, 'Title')) {
            return sprintf('Sample %s', ucfirst($variableName));
        }

        if (str_contains($variableName, 'description') || str_contains($variableName, 'Description')) {
            return sprintf('Sample description for %s', $variableName);
        }

        if (str_contains($variableName, 'status') || str_contains($variableName, 'Status')) {
            return 'ACTIVE';
        }

        // Boolean patterns
        if (str_contains($variableName, 'enabled') || str_contains($variableName, 'Enabled')) {
            return 'true';
        }

        if (str_contains($variableName, 'active') || str_contains($variableName, 'Active')) {
            return 'true';
        }

        // Default fallback
        return sprintf('{{%s}}', $variableName);
    }

    /**
     * Generate static fallback for Postman dynamic variables.
     */
    private function generateDynamicVariableFallback(string $dynamicVar): string
    {
        return match ($dynamicVar) {
            '$guid' => '123e4567-e89b-12d3-a456-426614174000',
            '$timestamp' => (string) time(),
            '$isoTimestamp' => date('c'),
            '$randomInt' => '123456789',
            '$randomUUID' => '123e4567-e89b-12d3-a456-426614174000',
            '$randomEmail' => 'test.user@example.com',
            '$randomPhoneNumber' => '555-123-4567',
            default => '',
        };
    }
}
