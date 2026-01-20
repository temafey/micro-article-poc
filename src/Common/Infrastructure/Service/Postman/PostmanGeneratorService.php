<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Service\Postman;

use Micro\Component\Common\Infrastructure\Service\OpenApi\OpenApiParser;
use Symfony\Component\DependencyInjection\Attribute\Lazy;

/**
 * Postman Generator Service - Main Orchestrator.
 *
 * SOLID Principles Implementation:
 * - Single Responsibility: Coordinates collection generation workflow
 * - Dependency Inversion: Depends on abstractions (interfaces)
 * - Open/Closed: Extensible through constructor injection
 */
#[Lazy]
final readonly class PostmanGeneratorService implements PostmanGeneratorServiceInterface
{
    /**
     * @param OpenApiParser                        $openApiParser        OpenAPI parser
     * @param PostmanCollectionFactoryInterface    $collectionFactory    Collection factory
     * @param CollectionFileWriterInterface        $fileWriter           File writer
     * @param PostmanEnvironmentGeneratorInterface $environmentGenerator Environment generator
     */
    public function __construct(
        private OpenApiParser $openApiParser,
        private PostmanCollectionFactoryInterface $collectionFactory,
        private CollectionFileWriterInterface $fileWriter,
        private PostmanEnvironmentGeneratorInterface $environmentGenerator,
    ) {
    }

    public function generateCollections(string $apiVersion, string $outputDirectory): array
    {
        // Step 1: Fetch OpenAPI specification
        $docPath = sprintf('/api/%s/doc.json', $apiVersion);
        $openApiSpec = $this->openApiParser->fetchOpenApiSpec($docPath);

        // Step 2: Extract all operations
        $operations = $this->openApiParser->extractPaths($openApiSpec);

        // Step 3: Group by entity and type (command/query)
        $grouped = $this->groupOperationsByEntityAndType($operations);

        // Step 4: Generate collections for each entity
        $generatedFiles = [];

        foreach ($grouped as $entityName => $types) {
            // Generate commands collection if exists
            if (! empty($types['commands'])) {
                $commandsCollection = $this->collectionFactory->createCommandsCollection(
                    $types['commands'],
                    $openApiSpec,
                    $entityName,
                    sprintf('CQRS Commands for %s entity', ucfirst($entityName))
                );

                $fileName = sprintf('%s-commands.json', strtolower($entityName));
                $filePath = $outputDirectory . '/' . $fileName;

                $this->fileWriter->write($filePath, $commandsCollection);
                $generatedFiles[$entityName . '_commands'] = $filePath;
            }

            // Generate queries collection if exists
            if (! empty($types['queries'])) {
                $queriesCollection = $this->collectionFactory->createQueriesCollection(
                    $types['queries'],
                    $openApiSpec,
                    $entityName,
                    sprintf('CQRS Queries for %s entity - Read operations', ucfirst($entityName))
                );

                $fileName = sprintf('%s-queries.json', strtolower($entityName));
                $filePath = $outputDirectory . '/' . $fileName;

                $this->fileWriter->write($filePath, $queriesCollection);
                $generatedFiles[$entityName . '_queries'] = $filePath;
            }
        }

        return $generatedFiles;
    }

    public function generateCollectionsWithEnvironment(
        string $apiVersion,
        string $collectionsDirectory,
        string $environmentsDirectory,
        string $environmentName = 'local-dev',
        array $customValues = [],
    ): array {
        // Step 1: Generate all collections
        $generatedCollections = $this->generateCollections($apiVersion, $collectionsDirectory);

        // Step 2: Load generated collections for environment variable extraction
        $collections = [];
        foreach ($generatedCollections as $filePath) {
            $collectionData = json_decode(file_get_contents($filePath), true, 512, JSON_THROW_ON_ERROR);
            $collections[] = $collectionData;
        }

        // Step 3: Generate environment based on collections
        $environment = $this->environmentGenerator->generateEnvironment(
            $apiVersion,
            $collections,
            $environmentName,
            $customValues
        );

        // Step 4: Write environment file
        $environmentFileName = sprintf('%s.json', strtolower(str_replace(' ', '-', $environmentName)));
        $environmentFilePath = $environmentsDirectory . '/' . $environmentFileName;
        $this->fileWriter->write($environmentFilePath, $environment);

        return [
            'collections' => $generatedCollections,
            'environment' => $environmentFilePath,
        ];
    }

    /**
     * Groups operations by entity name and type (command vs query).
     *
     * Commands: POST, PUT, PATCH, DELETE
     * Queries: GET
     *
     * @param array<int,array<string,mixed>> $operations Operations from OpenAPI
     *
     * @return array<string,array<string,array<int,array<string,mixed>>>> Grouped operations
     */
    private function groupOperationsByEntityAndType(array $operations): array
    {
        $grouped = [];

        foreach ($operations as $operation) {
            $entityName = $this->extractEntityName($operation);
            $method = $operation['method'];

            // Determine type
            $type = $this->isCommandMethod($method) ? 'commands' : 'queries';

            $grouped[$entityName][$type][] = $operation;
        }

        return $grouped;
    }

    /**
     * Extracts entity name from operation.
     *
     * Uses tags or path to determine entity.
     *
     * @param array<string,mixed> $operation Operation definition
     *
     * @return string Entity name
     */
    private function extractEntityName(array $operation): string
    {
        // Try to get from tags
        $tags = $operation['tags'] ?? [];
        if (! empty($tags)) {
            $tag = $tags[0];
            // Remove -commands, -queries suffixes
            $tag = preg_replace('/(commands|queries)$/i', '', (string) $tag);
            $tag = trim($tag, '-');

            return strtolower($tag);
        }

        // Extract from path (e.g., /api/v2/article/ => article)
        $path = $operation['path'];
        if (preg_match('#/api/v\d+/([^/]+)#', (string) $path, $matches)) {
            return strtolower($matches[1]);
        }

        return 'other';
    }

    /**
     * Checks if HTTP method is a command.
     *
     * @param string $method HTTP method
     *
     * @return bool True if command method
     */
    private function isCommandMethod(string $method): bool
    {
        return in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }
}
