<?php

declare(strict_types=1);

namespace Micro\Component\Common\Presentation\Cli;

use Micro\Component\Common\Infrastructure\Service\Postman\PostmanGeneratorServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Console command to generate Postman collections from OpenAPI documentation.
 *
 * This command automatically analyzes your API structure using OpenAPI/Swagger
 * documentation and generates separate Postman collections per entity with CQRS separation.
 *
 * The command accesses API documentation internally (without HTTP requests) using the
 * Symfony kernel, parses the OpenAPI specification, and generates Postman Collection v2.1.0
 * compatible JSON files organized by entity (commands/queries split).
 *
 * Features:
 * - Generates separate collections per entity (article-commands.json, article-queries.json)
 * - CQRS separation (commands vs queries)
 * - Uses variables for request bodies ({{uuid}}, {{title}}, {{description}})
 * - Excludes process_uuid and metadata fields
 * - Supports versioned APIs (v1, v2, etc.)
 * - Compatible with EmulateHttpRequestCommand for testing
 *
 * Use Cases:
 * - Automated API testing preparation
 * - Quick Postman collection bootstrap
 * - CI/CD integration for API validation
 * - Development environment setup
 * - API documentation synchronization
 *
 * Generated Files:
 * - Entity-specific collections: tests/postman/entities/{entity}-commands.json
 * - Entity-specific queries: tests/postman/entities/{entity}-queries.json
 *
 * Usage Examples:
 * ```bash
 * # Generate collections for v2 API (default)
 * php bin/console app:generate-postman-collection
 *
 * # Generate collections for v1 API
 * php bin/console app:generate-postman-collection --api-version=v1
 *
 * # Custom output directory
 * php bin/console app:generate-postman-collection --output=tests/postman/my-collections
 * ```
 *
 * @since   1.0.0
 */
#[AsCommand(
    name: 'app:generate-postman-collection',
    description: 'Generates Postman collections per entity from OpenAPI documentation'
)]
final class GeneratePostmanCollectionCommand
{
    /**
     * @param PostmanGeneratorServiceInterface $postmanGeneratorService Postman generator service (SOLID)
     */
    public function __construct(
        private readonly PostmanGeneratorServiceInterface $postmanGeneratorService,
    ) {
    }

    /**
     * Executes the command - generates Postman collections from API documentation.
     *
     * Workflow:
     * 1. Fetches OpenAPI specification from internal endpoint
     * 2. Parses API paths and groups by entity and type (command/query)
     * 3. Generates separate collection files per entity
     * 4. Saves JSON files to output directory
     *
     * @param string|null  $apiVersion  API version to generate collections for (v1, v2, etc.)
     * @param string|null  $output      Output directory for generated collections
     * @param bool         $generateEnv Generate environment file based on collection variables
     * @param string|null  $envOutput   Output directory for environment file
     * @param string|null  $envName     Environment name (local-dev, staging, etc.)
     * @param SymfonyStyle $io          Symfony console style interface
     *
     * @return int Command exit code (Command::SUCCESS or Command::FAILURE)
     */
    public function __invoke(
        #[Option(name: 'api-version', description: 'API version to generate collections for (v1, v2, etc.)')]
        string $apiVersion = 'v2',

        #[Option(name: 'output', shortcut: 'o', description: 'Output directory for generated collections')]
        string $output = '/app/tests/postman/entities',

        #[Option(name: 'generate-env', description: 'Generate environment file based on collection variables')]
        bool $generateEnv = false,

        #[Option(name: 'env-output', description: 'Output directory for environment file')]
        string $envOutput = '/app/tests/postman/environments',

        #[Option(name: 'env-name', description: 'Environment name (local-dev, staging, etc.)')]
        string $envName = 'local-dev',

        ?SymfonyStyle $io = null,
    ): int {
        // Create SymfonyStyle if not injected (for backwards compatibility)
        if ($io === null) {
            throw new \RuntimeException('SymfonyStyle must be injected');
        }

        $io->title('Postman Collection Generator');
        $io->text('Analyzing API documentation and generating Postman collections...');

        try {
            $io->section('Entity-Based Collection Generation (SOLID Architecture)');
            $io->text(sprintf('API Version: <info>%s</info>', $apiVersion));
            $io->text(sprintf('Output Directory: <info>%s</info>', $output));

            if ($generateEnv) {
                $io->text('Environment Generation: <info>enabled</info>');
                $io->text(sprintf('Environment Output: <info>%s</info>', $envOutput));
                $io->text(sprintf('Environment Name: <info>%s</info>', $envName));
            }

            $io->newLine();
            $io->text('Generating separate collections for each entity...');

            // Generate collections (with or without environment)
            if ($generateEnv) {
                $result = $this->postmanGeneratorService->generateCollectionsWithEnvironment(
                    $apiVersion,
                    $output,
                    $envOutput,
                    $envName
                );
                $generatedFiles = $result['collections'];
                $environmentFile = $result['environment'];
            } else {
                $generatedFiles = $this->postmanGeneratorService->generateCollections($apiVersion, $output);
                $environmentFile = null;
            }

            $io->success('Collections generated successfully!');

            // Display generated collection files
            $io->section('Generated Collection Files');
            foreach ($generatedFiles as $entity => $filePath) {
                $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
                $io->text(sprintf(
                    '  - <info>%s</info>: %s (%s)',
                    $entity,
                    $filePath,
                    $this->formatBytes($fileSize)
                ));
            }

            // Display environment file if generated
            if ($environmentFile !== null) {
                $io->section('Generated Environment File');
                $fileSize = file_exists($environmentFile) ? filesize($environmentFile) : 0;
                $io->text(sprintf(
                    '  - <info>%s</info>: %s (%s)',
                    $envName,
                    $environmentFile,
                    $this->formatBytes($fileSize)
                ));

                // Usage instructions with environment
                $io->block(
                    'You can now use these collections with EmulateHttpRequestCommand:' . PHP_EOL .
                    sprintf(
                        'php bin/console app:emulate-request --collection=%s/article-commands.json --postman-env=%s',
                        $output,
                        $environmentFile
                    ),
                    'INFO',
                    'fg=white;bg=blue',
                    ' ',
                    true
                );
            } else {
                // Usage instructions without environment
                $io->block(
                    'You can now use these collections with EmulateHttpRequestCommand:' . PHP_EOL .
                    sprintf('php bin/console app:emulate-request --collection=%s/article-commands.json', $output),
                    'INFO',
                    'fg=white;bg=blue',
                    ' ',
                    true
                );
            }

            return Command::SUCCESS;
        } catch (\Throwable $throwable) {
            $io->error([
                'Failed to generate Postman collections',
                sprintf('Error: %s', $throwable->getMessage()),
                sprintf('File: %s:%d', $throwable->getFile(), $throwable->getLine()),
            ]);

            if ($io->isVerbose()) {
                $io->section('Stack Trace');
                $io->text($throwable->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    /**
     * Formats bytes into human-readable format.
     *
     * @param int $bytes Byte count
     *
     * @return string Formatted string (e.g., "1.5 MB")
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;

        while ($bytes >= 1024 && $index < count($units) - 1) {
            $bytes /= 1024;
            ++$index;
        }

        return sprintf('%.2f %s', $bytes, $units[$index]);
    }
}
