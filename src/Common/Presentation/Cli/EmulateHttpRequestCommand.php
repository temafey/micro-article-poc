<?php

declare(strict_types=1);

namespace Micro\Component\Common\Presentation\Cli;

use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Symfony Console command for emulating HTTP requests within the application context.
 *
 * This unified command provides two flexible modes of operation for testing and debugging APIs:
 * 1. Single Request Mode: Execute individual HTTP requests with custom method, URI, headers, and body
 * 2. Postman Collection Mode: Run complete Postman collection files with environment variable support
 *
 * The command bypasses the web server layer and executes requests directly through the Symfony Kernel,
 * making it useful for testing, debugging, CI/CD pipelines, and automated API validation without
 * external HTTP clients or network dependencies.
 *
 * Features:
 * - Execute single HTTP requests (GET, POST, PUT, PATCH, DELETE, etc.)
 * - Run Postman collection files (.json) for comprehensive API testing
 * - Load and apply Postman environment variables for different environments
 * - Filter execution by folder or specific request name for targeted testing
 * - Variable substitution using double-brace syntax (supports nested variables)
 * - Pretty-printed JSON response output for readability
 * - Detailed request/response logging with status codes and headers
 * - Error handling and comprehensive failure reporting
 *
 * Use Cases:
 * - Local development testing without external tools
 * - CI/CD pipeline integration for automated API validation
 * - Debugging API endpoints in isolation
 * - Testing authentication flows and state transitions
 * - Performance testing and load simulation preparation
 *
 * Usage Examples:
 * ```bash
 * # Single request - Execute a POST request
 * php bin/console app:emulate-request POST /api/v1/resources \
 *     '{"Content-Type":"application/json"}' \
 *     '{"name":"Resource Name","description":"Description"}'
 *
 * # Single request - Execute a GET request
 * php bin/console app:emulate-request GET /api/v1/resources/123
 *
 * # Postman collection - Run entire collection with environment
 * php bin/console app:emulate-request \
 *     --collection=/app/tests/postman/collection.json \
 *     --postman-env=/app/tests/postman/environment.json
 *
 * # Postman collection - Run specific folder (e.g., authentication tests)
 * php bin/console app:emulate-request \
 *     --collection=/path/to/collection.json \
 *     --folder="Authentication"
 *
 * # Postman collection - Run single request by name
 * php bin/console app:emulate-request \
 *     --collection=/path/to/collection.json \
 *     --request="Create Resource"
 *
 * # Combined filters - Specific request within a folder
 * php bin/console app:emulate-request \
 *     --collection=/path/to/collection.json \
 *     --folder="User Management" \
 *     --request="Update User Profile"
 * ```
 *
 * @since   1.0.0
 * @see https://learning.postman.com/docs/running-collections/using-newman-cli/command-line-integration-with-newman/
 * @see https://symfony.com/doc/current/console.html
 * @see https://symfony.com/doc/current/components/http_kernel.html
 */
#[AsCommand(
    name: 'app:emulate-request',
    description: 'Unified HTTP request emulation: Execute single requests or Postman collections'
)]
final class EmulateHttpRequestCommand
{
    /**
     * Constructor - Initializes the command with required Symfony services.
     *
     * @param KernelInterface $kernel Symfony HTTP Kernel for request handling
     */
    public function __construct(
        private readonly KernelInterface $kernel,
    ) {
    }

    /**
     * Executes the command - unified entry point for both execution modes.
     *
     * Automatically determines the appropriate execution mode based on input:
     * - If --collection option is present: Executes Postman collection mode (batch testing)
     * - Otherwise: Executes single request mode (individual request)
     *
     * This unified approach provides flexibility for developers to choose the most
     * appropriate testing method based on their current needs without requiring
     * different commands for different scenarios.
     *
     * @param string|null  $method     HTTP method (GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD)
     * @param string|null  $uri        Request URI path (e.g., /api/v1/resources)
     * @param string|null  $headers    Request headers in JSON format
     * @param string|null  $content    Request body content (JSON, XML, or plain text)
     * @param string|null  $collection Path to Postman collection JSON file
     * @param string|null  $postmanEnv Path to Postman environment JSON file
     * @param string|null  $folder     Run specific folder from collection
     * @param string|null  $request    Run specific request from collection
     * @param SymfonyStyle $io         Symfony console style interface
     *
     * @return int Command exit code (Command::SUCCESS on success, Command::FAILURE on error)
     */
    public function __invoke(
        #[Argument(name: 'method', description: 'HTTP method (GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD)')]
        ?string $method = null,

        #[Argument(name: 'uri', description: 'Request URI path (e.g., /api/v1/resources)')]
        ?string $uri = null,

        #[Argument(
            name: 'headers',
            description: 'Request headers in JSON format (e.g., \'{"Content-Type":"application/json"}\')'
        )]
        ?string $headers = null,

        #[Argument(name: 'content', description: 'Request body content (JSON, XML, or plain text)')]
        ?string $content = null,

        #[Option(name: 'collection', shortcut: 'c', description: 'Path to Postman collection JSON file')]
        ?string $collection = null,

        #[Option(
            name: 'postman-env',
            shortcut: 'p',
            description: 'Path to Postman environment JSON file (for variable substitution)'
        )]
        ?string $postmanEnv = null,

        #[Option(
            name: 'folder',
            shortcut: 'f',
            description: 'Run specific folder from collection (filters by folder name)'
        )]
        ?string $folder = null,

        #[Option(
            name: 'request',
            shortcut: 'r',
            description: 'Run specific request from collection (filters by request name)'
        )]
        ?string $request = null,

        ?SymfonyStyle $io = null,
    ): int {
        if ($io === null) {
            throw new \RuntimeException('SymfonyStyle must be injected');
        }

        if ($collection !== null) {
            return $this->executePostmanCollection($collection, $postmanEnv, $folder, $request, $io);
        }

        return $this->executeSingleRequest($method, $uri, $headers, $content, $io);
    }

    /**
     * Executes a single HTTP request using provided arguments.
     *
     * This method handles individual request execution for testing specific endpoints.
     * It validates required arguments, parses JSON headers, and delegates to the
     * core request execution method for actual HTTP handling.
     *
     * @param string|null  $method  HTTP method
     * @param string|null  $uri     Request URI
     * @param string|null  $headers Request headers as JSON
     * @param string|null  $content Request body content
     * @param SymfonyStyle $io      Console output interface
     *
     * @return int Command exit code
     */
    private function executeSingleRequest(
        ?string $method,
        ?string $uri,
        ?string $headers,
        ?string $content,
        SymfonyStyle $io,
    ): int {
        if ($method === null || $uri === null) {
            $io->error('Method and URI are required for single request execution');

            return Command::FAILURE;
        }

        $parsedHeaders = $headers !== null
            ? json_decode($headers, true, 512, JSON_THROW_ON_ERROR)
            : [];

        return $this->executeRequest($method, $uri, $parsedHeaders, $content, $io);
    }

    /**
     * Executes a Postman collection file with optional environment variables.
     *
     * This method provides comprehensive batch testing capabilities by running multiple
     * requests from a Postman collection file.
     *
     * @param string       $collectionPath  Path to collection file
     * @param string|null  $environmentPath Path to environment file
     * @param string|null  $folderName      Folder filter
     * @param string|null  $requestName     Request filter
     * @param SymfonyStyle $io              Console output interface
     *
     * @return int Command exit code
     */
    private function executePostmanCollection(
        string $collectionPath,
        ?string $environmentPath,
        ?string $folderName,
        ?string $requestName,
        SymfonyStyle $io,
    ): int {
        if (! file_exists($collectionPath)) {
            $io->error('Collection file not found: ' . $collectionPath);

            return Command::FAILURE;
        }

        $collection = json_decode(file_get_contents($collectionPath), true, 512, JSON_THROW_ON_ERROR);
        $environment = [];

        if ($environmentPath !== null && file_exists($environmentPath)) {
            $envData = json_decode(file_get_contents($environmentPath), true, 512, JSON_THROW_ON_ERROR);
            $environment = $this->parseEnvironmentVariables($envData);
        }

        $io->info('Running Postman collection: ' . ($collection['info']['name'] ?? 'Unknown'));

        $requests = $this->extractRequests($collection, $folderName, $requestName);
        $successCount = 0;
        $totalCount = count($requests);

        foreach ($requests as $requestData) {
            $io->comment('Running request: ' . $requestData['name']);

            $method = $requestData['method'];
            $url = $this->substituteVariables($requestData['url'], $environment);
            $headers = $requestData['headers'] ?? [];
            $body = $requestData['body'] ?? '';

            // Substitute variables in headers and body
            foreach ($headers as $key => $value) {
                $headers[$key] = $this->substituteVariables($value, $environment);
            }

            $body = $this->substituteVariables($body, $environment);
            $result = $this->executeRequest($method, $url, $headers, $body, $io);

            if ($result === Command::SUCCESS) {
                ++$successCount;
            }
        }

        $io->success("Collection execution completed: {$successCount}/{$totalCount} requests successful");

        return $successCount === $totalCount ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Core request execution method - handles HTTP requests through Symfony Kernel.
     *
     * @param string               $method  HTTP method
     * @param string               $uri     Request URI path
     * @param array<string,string> $headers Request headers
     * @param string|null          $content Request body content
     * @param SymfonyStyle         $io      Console output interface
     *
     * @return int Command exit code
     */
    private function executeRequest(
        string $method,
        string $uri,
        array $headers,
        ?string $content,
        SymfonyStyle $io,
    ): int {
        try {
            $request = Request::create($uri, $method, [], [], [], [], $content);

            foreach ($headers as $name => $value) {
                $request->headers->set($name, $value);
            }

            $response = $this->kernel->handle($request);

            $io->info('Response status: ' . $response->getStatusCode());
            $io->info('Response headers:');

            foreach ($response->headers->all() as $name => $values) {
                $io->text('  ' . $name . ': ' . implode(', ', $values));
            }

            $io->newLine();
            $io->info('Response body:');
            $responseContent = $response->getContent();

            if ($response->headers->get('Content-Type') !== null && str_contains(
                $response->headers->get('Content-Type'),
                'application/json'
            )) {
                $decoded = json_decode($responseContent, true);
                if ($decoded !== null) {
                    $responseContent = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                }
            }

            $io->text($responseContent);

            return Command::SUCCESS;
        } catch (\Exception $exception) {
            $io->error('Request failed: ' . $exception->getMessage());

            return Command::FAILURE;
        }
    }

    /**
     * Parses Postman environment file and extracts enabled variables.
     *
     * @param array<string,mixed> $envData Parsed Postman environment JSON data
     *
     * @return array<string,string> Environment variables as key-value pairs
     */
    private function parseEnvironmentVariables(array $envData): array
    {
        $variables = [];

        if (isset($envData['values'])) {
            foreach ($envData['values'] as $variable) {
                if ($variable['enabled'] ?? true) {
                    $variables[$variable['key']] = $variable['value'];
                }
            }
        }

        return $variables;
    }

    /**
     * Extracts requests from Postman collection with optional filtering.
     *
     * @param array<string,mixed> $collection  Parsed Postman collection data
     * @param string|null         $folderName  Optional folder name filter
     * @param string|null         $requestName Optional request name filter
     *
     * @return array<int,array<string,mixed>> Array of extracted request data
     */
    private function extractRequests(array $collection, ?string $folderName, ?string $requestName): array
    {
        $requests = [];

        if (isset($collection['item'])) {
            $this->extractRequestsRecursive($collection['item'], $requests, $folderName, $requestName);
        }

        return $requests;
    }

    /**
     * Recursively extracts requests from nested Postman collection structure.
     *
     * @param array<int,array<string,mixed>> $items       Collection items
     * @param array<int,array<string,mixed>> $requests    Extracted requests (by reference)
     * @param string|null                    $folderName  Optional folder name filter
     * @param string|null                    $requestName Optional request name filter
     */
    private function extractRequestsRecursive(
        array $items,
        array &$requests,
        ?string $folderName,
        ?string $requestName,
    ): void {
        foreach ($items as $item) {
            if (isset($item['item'])) {
                if ($folderName === null || $item['name'] === $folderName) {
                    $this->extractRequestsRecursive($item['item'], $requests, null, $requestName);
                }
            } elseif (isset($item['request'])) {
                if ($requestName === null || $item['name'] === $requestName) {
                    $request = $item['request'];
                    $requests[] = [
                        'name' => $item['name'],
                        'method' => $request['method'],
                        'url' => $this->buildUrl($request['url']),
                        'headers' => $this->extractHeaders($request['header'] ?? []),
                        'body' => $this->extractBody($request['body'] ?? []),
                    ];
                }
            }
        }
    }

    /**
     * Builds complete URL string from Postman URL data structure.
     *
     * @param string|array<string,mixed> $urlData Postman URL data
     *
     * @return string Complete URL string
     */
    private function buildUrl(string|array $urlData): string
    {
        if (is_string($urlData)) {
            return $urlData;
        }

        $protocol = $urlData['protocol'] ?? 'http';
        $host = is_array($urlData['host']) ? implode('.', $urlData['host']) : ($urlData['host'] ?? 'localhost');
        $port = isset($urlData['port']) ? ':' . $urlData['port'] : '';
        $path = is_array($urlData['path']) ? '/' . implode('/', $urlData['path']) : ($urlData['path'] ?? '');

        $query = '';
        if (isset($urlData['query']) && is_array($urlData['query'])) {
            $queryParams = [];
            foreach ($urlData['query'] as $param) {
                if ($param['disabled'] ?? false) {
                    continue;
                }

                $queryParams[] = urlencode((string) $param['key']) . '=' . urlencode($param['value'] ?? '');
            }

            if ($queryParams !== []) {
                $query = '?' . implode('&', $queryParams);
            }
        }

        return $protocol . '://' . $host . $port . $path . $query;
    }

    /**
     * Extracts headers from Postman header data format.
     *
     * @param array<int,array<string,mixed>> $headerData Postman header array
     *
     * @return array<string,string> Headers as key-value pairs
     */
    private function extractHeaders(array $headerData): array
    {
        $headers = [];

        foreach ($headerData as $header) {
            if (! ($header['disabled'] ?? false)) {
                $headers[$header['key']] = $header['value'];
            }
        }

        return $headers;
    }

    /**
     * Extracts request body from Postman body data format.
     *
     * @param array<string,mixed>|string $bodyData Postman body data structure
     *
     * @return string Request body content
     */
    private function extractBody(array|string $bodyData): string
    {
        if (empty($bodyData)) {
            return '';
        }

        $mode = $bodyData['mode'] ?? 'raw';

        switch ($mode) {
            case 'raw':
                return $bodyData['raw'] ?? '';
            case 'urlencoded':
                $params = [];
                foreach ($bodyData['urlencoded'] ?? [] as $param) {
                    if (! ($param['disabled'] ?? false)) {
                        $params[] = urlencode((string) $param['key']) . '=' . urlencode($param['value'] ?? '');
                    }
                }

                return implode('&', $params);
            case 'formdata':
                $data = [];
                foreach ($bodyData['formdata'] ?? [] as $param) {
                    if (! ($param['disabled'] ?? false)) {
                        $data[$param['key']] = $param['value'] ?? '';
                    }
                }

                return json_encode($data);
            default:
                return '';
        }
    }

    /**
     * Substitutes environment variables in text using double-brace syntax.
     *
     * Performs recursive variable substitution to handle nested variables.
     * Uses a maximum iteration limit to prevent infinite loops.
     *
     * @param string               $text      Text containing variable markers
     * @param array<string,string> $variables Environment variables as key-value pairs
     *
     * @return string Text with substituted variables
     */
    private function substituteVariables(string $text, array $variables): string
    {
        $maxIterations = 10;
        $iterations = 0;
        $pattern = '/\{\{([^}]+)\}\}/';

        while (preg_match($pattern, $text) && $iterations < $maxIterations) {
            $previousText = $text;

            $text = preg_replace_callback($pattern, function (array $matches) use ($variables) {
                $varName = $matches[1];

                return $variables[$varName] ?? $matches[0];
            }, $text);

            if ($text === $previousText) {
                break;
            }

            ++$iterations;
        }

        return $text;
    }
}
