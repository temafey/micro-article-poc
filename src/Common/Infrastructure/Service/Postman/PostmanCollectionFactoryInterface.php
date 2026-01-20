<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Service\Postman;

/**
 * Postman Collection Factory Interface.
 *
 * Creates Postman Collection v2.1.0 compliant structures.
 * Follows Open/Closed Principle - extensible for different collection types.
 */
interface PostmanCollectionFactoryInterface
{
    /**
     * Creates a commands collection for an entity.
     *
     * Commands include POST, PUT, PATCH, DELETE operations.
     *
     * @param array<int,array<string,mixed>> $operations  Array of command operations
     * @param array<string,mixed>            $openApiSpec Full OpenAPI specification
     * @param string                         $entityName  Entity name (e.g., 'article', 'user')
     * @param string                         $description Collection description
     *
     * @return array<string,mixed> Postman collection structure
     */
    public function createCommandsCollection(
        array $operations,
        array $openApiSpec,
        string $entityName,
        string $description = '',
    ): array;

    /**
     * Creates a queries collection for an entity.
     *
     * Queries include GET operations.
     *
     * @param array<int,array<string,mixed>> $operations  Array of query operations
     * @param array<string,mixed>            $openApiSpec Full OpenAPI specification
     * @param string                         $entityName  Entity name (e.g., 'article', 'user')
     * @param string                         $description Collection description
     *
     * @return array<string,mixed> Postman collection structure
     */
    public function createQueriesCollection(
        array $operations,
        array $openApiSpec,
        string $entityName,
        string $description = '',
    ): array;
}
