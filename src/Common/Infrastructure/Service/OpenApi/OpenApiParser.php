<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Service\OpenApi;

use Symfony\Component\DependencyInjection\Attribute\Lazy;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * OpenAPI Documentation Parser Service.
 *
 * Parses OpenAPI/Swagger documentation by executing internal Symfony kernel requests
 * to the API documentation endpoint without making external HTTP calls.
 *
 * Features:
 * - Fetches OpenAPI spec from internal endpoint (e.g., /api/doc.json)
 * - Parses paths, operations, parameters, and request bodies
 * - Extracts schema definitions for generating fake data
 * - Supports versioned APIs (v1, v2, etc.)
 */
#[Lazy]
final readonly class OpenApiParser
{
    /**
     * @param KernelInterface $kernel Symfony HTTP Kernel for internal requests
     */
    public function __construct(
        private KernelInterface $kernel,
    ) {
    }

    /**
     * Fetches and parses OpenAPI documentation from the specified endpoint.
     *
     * Makes an internal Symfony kernel request to fetch the OpenAPI JSON specification
     * without network overhead. This bypasses the web server layer entirely.
     *
     * @param string $docPath API documentation path (e.g., '/api/doc.json', '/api/v2/doc.json')
     *
     * @return array<string,mixed> Parsed OpenAPI specification as associative array
     */
    public function fetchOpenApiSpec(string $docPath = '/api/doc.json'): array
    {
        // Create internal request to API documentation endpoint
        $request = Request::create($docPath, Request::METHOD_GET);
        $request->headers->set('Accept', 'application/json');

        // Execute request through kernel (bypasses HTTP layer)
        $response = $this->kernel->handle($request);

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            throw new \RuntimeException(sprintf(
                'Failed to fetch OpenAPI documentation from %s. Status: %d',
                $docPath,
                $response->getStatusCode()
            ));
        }

        $content = $response->getContent();
        if ($content === false || $content === '') {
            throw new \RuntimeException('Empty response from API documentation endpoint');
        }

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Extracts all API paths and operations from OpenAPI specification.
     *
     * Processes the 'paths' section of OpenAPI spec and returns structured data
     * about available endpoints, methods, parameters, and request bodies.
     *
     * @param array<string,mixed> $openApiSpec Parsed OpenAPI specification
     *
     * @return array<int,array<string,mixed>> Array of endpoint operations with metadata
     */
    public function extractPaths(array $openApiSpec): array
    {
        if (! isset($openApiSpec['paths']) || ! is_array($openApiSpec['paths'])) {
            return [];
        }

        $operations = [];

        foreach ($openApiSpec['paths'] as $path => $pathData) {
            foreach ($pathData as $method => $operationData) {
                // Skip non-HTTP methods (like 'parameters', 'servers', etc.)
                if (! in_array(
                    strtoupper((string) $method),
                    ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'],
                    true
                )) {
                    continue;
                }

                $operations[] = [
                    'path' => $path,
                    'method' => strtoupper((string) $method),
                    'operationId' => $operationData['operationId'] ?? null,
                    'summary' => $operationData['summary'] ?? '',
                    'description' => $operationData['description'] ?? '',
                    'tags' => $operationData['tags'] ?? [],
                    'parameters' => $this->extractParameters($operationData),
                    'requestBody' => $this->extractRequestBody($operationData),
                    'responses' => $operationData['responses'] ?? [],
                ];
            }
        }

        return $operations;
    }

    /**
     * Resolves schema references ($ref) in OpenAPI specification.
     *
     * OpenAPI uses JSON references like "#/components/schemas/ArticleDto".
     * This method resolves such references to their actual schema definitions.
     *
     * @param array<string,mixed> $schema      Schema definition (may contain $ref)
     * @param array<string,mixed> $openApiSpec Full OpenAPI specification for reference resolution
     *
     * @return array<string,mixed> Resolved schema definition
     */
    public function resolveSchemaRef(array $schema, array $openApiSpec): array
    {
        if (! isset($schema['$ref'])) {
            return $schema;
        }

        $ref = $schema['$ref'];

        // Parse reference path (e.g., "#/components/schemas/ArticleDto")
        if (! str_starts_with($ref, '#/')) {
            return $schema; // External refs not supported
        }

        $path = explode('/', substr($ref, 2)); // Remove '#/' prefix
        $resolved = $openApiSpec;

        foreach ($path as $segment) {
            if (! isset($resolved[$segment])) {
                return $schema; // Reference not found
            }

            $resolved = $resolved[$segment];
        }

        return is_array($resolved) ? $resolved : $schema;
    }

    /**
     * Extracts base URL from OpenAPI specification.
     *
     * @param array<string,mixed> $openApiSpec Parsed OpenAPI specification
     *
     * @return string Base URL (defaults to 'http://localhost' if not found)
     */
    public function extractBaseUrl(array $openApiSpec): string
    {
        return $openApiSpec['servers'][0]['url'] ?? 'http://localhost';
    }

    /**
     * Extracts parameters from an operation definition.
     *
     * Processes query parameters, path parameters, and header parameters
     * with their schemas and metadata.
     *
     * @param array<string,mixed> $operationData Operation definition from OpenAPI spec
     *
     * @return array<int,array<string,mixed>> Array of parameter definitions
     */
    private function extractParameters(array $operationData): array
    {
        if (! isset($operationData['parameters']) || ! is_array($operationData['parameters'])) {
            return [];
        }

        $parameters = [];

        foreach ($operationData['parameters'] as $param) {
            $parameters[] = [
                'name' => $param['name'] ?? '',
                'in' => $param['in'] ?? 'query',
                'required' => $param['required'] ?? false,
                'schema' => $param['schema'] ?? [],
                'description' => $param['description'] ?? '',
            ];
        }

        return $parameters;
    }

    /**
     * Extracts request body schema from an operation definition.
     *
     * Processes the request body content, typically JSON, and extracts
     * the schema definition for generating request data.
     *
     * @param array<string,mixed> $operationData Operation definition from OpenAPI spec
     *
     * @return array<string,mixed>|null Request body schema or null if not present
     */
    private function extractRequestBody(array $operationData): ?array
    {
        if (! isset($operationData['requestBody']['content'])) {
            return null;
        }

        $content = $operationData['requestBody']['content'];

        // Prefer application/json, but support other content types
        $jsonContent = $content['application/json'] ?? $content[array_key_first($content)] ?? null;

        if ($jsonContent === null) {
            return null;
        }

        return [
            'required' => $operationData['requestBody']['required'] ?? false,
            'schema' => $jsonContent['schema'] ?? [],
        ];
    }
}
