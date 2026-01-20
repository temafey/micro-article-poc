<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Service\Postman;

use Micro\Component\Common\Infrastructure\Service\OpenApi\OpenApiParser;

/**
 * Postman Request Builder - Builds individual requests from OpenAPI operations.
 */
final readonly class PostmanRequestBuilder implements PostmanRequestBuilderInterface
{
    /**
     * Fields to exclude from requests.
     */
    private const array EXCLUDED_FIELDS = [
        'process_uuid',
        'processUuid',
        'createdAt',
        'updatedAt',
        'deletedAt',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Fields to exclude from requests.
     */
    private const array EXCLUDED_POST_FIELDS = ['uuid'];

    /**
     * @param OpenApiParser $openApiParser OpenAPI parser for schema resolution
     */
    public function __construct(
        private OpenApiParser $openApiParser,
    ) {
    }

    public function buildRequest(array $operation, array $openApiSpec): array
    {
        $method = $operation['method'];
        $path = $operation['path'];
        $summary = $operation['summary'] ?? '';

        $request = [
            'name' => $summary ?: ucfirst(strtolower((string) $method)) . ' PostmanRequestBuilder.php' . $path,
            'request' => [
                'method' => $method,
                'header' => $this->buildHeaders($method),
                'url' => $this->buildUrl($path),
                'description' => $operation['description'] ?? '',
            ],
        ];

        // Add body for commands
        if (in_array($method, ['POST', 'PUT', 'PATCH'], true) && isset($operation['requestBody'])) {
            $request['request']['body'] = $this->buildBody($operation['requestBody'], $openApiSpec, $method);
        }

        return $request;
    }

    /**
     * Builds request headers.
     *
     * @param string $method HTTP method
     *
     * @return array<int,array<string,string>> Headers array
     */
    private function buildHeaders(string $method): array
    {
        if ($method === 'GET') {
            return [
                [
                    'key' => 'Accept',
                    'value' => '{{contentType}}',
                ],
            ];
        }

        return [
            [
                'key' => 'Content-Type',
                'value' => '{{contentType}}',
            ],
        ];
    }

    /**
     * Builds URL structure.
     *
     * @param string $path API path
     *
     * @return array<string,mixed> URL structure
     */
    private function buildUrl(string $path): array
    {
        // Replace path parameters with variables
        $processedPath = preg_replace_callback(
            '/{([^}]+)}/',
            fn ($matches): string => '{{' . $matches[1] . '}}',
            $path
        );

        $pathSegments = array_values(array_filter(explode('/', (string) $processedPath)));
        $pathSegments[] = '';

        return [
            'raw' => '{{baseUrl}}' . $processedPath,
            'host' => ['{{baseUrl}}'],
            'path' => $pathSegments,
        ];
    }

    /**
     * Builds request body.
     *
     * @param array<string,mixed> $requestBody Request body schema
     * @param array<string,mixed> $openApiSpec Full OpenAPI spec
     * @param string              $method      HTTP method
     *
     * @return array<string,mixed> Body structure
     */
    private function buildBody(array $requestBody, array $openApiSpec, string $method): array
    {
        $schema = $requestBody['schema'] ?? [];
        $resolvedSchema = $this->openApiParser->resolveSchemaRef($schema, $openApiSpec);
        $bodyData = $this->buildBodyData($resolvedSchema, $method);

        return [
            'mode' => 'raw',
            'raw' => json_encode($bodyData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Builds body data with variables.
     *
     * @param array<string,mixed> $schema Schema definition
     *
     * @return array<string,mixed> Body data
     */
    private function buildBodyData(array $schema, string $method): array
    {
        if (! isset($schema['properties'])) {
            return [];
        }

        $body = [];

        foreach ($schema['properties'] as $propertyName => $propertySchema) {
            // Skip excluded fields
            if ($this->isExcludedField($propertyName, $method)) {
                continue;
            }

            // Include required fields + common fields
            // if (in_array($propertyName, $required, true) || $this->isCommonField($propertyName)) {
            $body[$propertyName] = $this->generateValue($propertyName, $propertySchema);
            // }
        }

        return $body;
    }

    /**
     * Generates value for field.
     *
     * @param string              $fieldName Field name
     * @param array<string,mixed> $schema    Field schema
     *
     * @return mixed Generated value or variable reference
     */
    private function generateValue(string $fieldName, array $schema): mixed
    {
        $type = $schema['type'] ?? 'string';

        // Use variables for IDs
        if (str_contains($fieldName, 'uuid') || str_contains($fieldName, '_id')) {
            return '{{' . $fieldName . '}}';
        }

        // Use variables for common fields
        if (str_contains($fieldName, 'title')) {
            return '{{title}}';
        }

        if (str_contains($fieldName, 'description')) {
            return '{{description}}';
        }

        // Simple values for other types
        return match ($type) {
            'integer', 'number' => 100,
            'boolean' => true,
            'array' => [],
            'object' => [],
            default => 'Sample ' . ucfirst($fieldName),
        };
    }

    /**
     * Checks if field should be excluded.
     *
     * @param string $fieldName Field name
     *
     * @return bool True if excluded
     */
    private function isExcludedField(string $fieldName, string $method): bool
    {
        $excludedFields = self::EXCLUDED_FIELDS;

        if ($method === 'POST') {
            $excludedFields = array_merge($excludedFields, self::EXCLUDED_POST_FIELDS);
        }

        return array_any($excludedFields, fn ($excluded): bool => stripos($fieldName, (string) $excluded) !== false);
    }
}
