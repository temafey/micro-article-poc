<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Service\Postman;

/**
 * Postman Collection Factory - Creates collection structures.
 */
final readonly class PostmanCollectionFactory implements PostmanCollectionFactoryInterface
{
    /**
     * @param PostmanRequestBuilderInterface $requestBuilder Request builder
     */
    public function __construct(
        private PostmanRequestBuilderInterface $requestBuilder,
    ) {
    }

    public function createCommandsCollection(
        array $operations,
        array $openApiSpec,
        string $entityName,
        string $description = '',
    ): array {
        $entityTitle = ucfirst($entityName);

        return [
            'info' => [
                'name' => sprintf('%s Commands - %s System', $entityTitle, $entityTitle),
                'description' => $description ?: sprintf('CQRS Commands for %s entity', $entityTitle),
                'schema' => 'https://schema.postman.com/json/collection/v2.1.0/collection.json',
                '_postman_id' => sprintf('%s-commands-collection', strtolower($entityName)),
            ],
            'item' => $this->buildRequestItems($operations, $openApiSpec),
        ];
    }

    public function createQueriesCollection(
        array $operations,
        array $openApiSpec,
        string $entityName,
        string $description = '',
    ): array {
        $entityTitle = ucfirst($entityName);

        return [
            'info' => [
                'name' => sprintf('%s Queries - %s System', $entityTitle, $entityTitle),
                'description' => $description ?: sprintf('CQRS Queries for %s entity - Read operations', $entityTitle),
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
                '_postman_id' => sprintf('%s-queries-collection', strtolower($entityName)),
            ],
            'item' => $this->buildRequestItems($operations, $openApiSpec),
        ];
    }

    /**
     * Builds request items from operations.
     *
     * @param array<int,array<string,mixed>> $operations  Operations
     * @param array<string,mixed>            $openApiSpec Full OpenAPI spec
     *
     * @return array<int,array<string,mixed>> Request items
     */
    private function buildRequestItems(array $operations, array $openApiSpec): array
    {
        $items = [];

        foreach ($operations as $operation) {
            $items[] = $this->requestBuilder->buildRequest($operation, $openApiSpec);
        }

        return $items;
    }
}
