<?php

declare(strict_types=1);

namespace Tests\Functional;

use Micro\Kernel;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base class for functional/API tests.
 *
 * Provides:
 * - HTTP client for making requests
 * - JSON response assertions
 * - Authentication helpers
 * - API versioning support
 */
abstract class FunctionalTestCase extends WebTestCase
{
    use MockeryPHPUnitIntegration;

    protected KernelBrowser $client;

    /**
     * Default API version for requests.
     */
    protected string $apiVersion = 'v1';

    /**
     * Default content type for requests.
     */
    protected string $contentType = 'application/json';

    /**
     * Authentication token for protected endpoints.
     */
    protected ?string $authToken = null;

    /**
     * Override to use the project's Kernel class.
     */
    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = self::createClient();
        // Catch exceptions to get proper HTTP error responses
        $this->client->catchExceptions(true);
        // Follow redirects automatically (e.g., trailing slash redirects)
        $this->client->followRedirects(true);
        // Disable kernel reboot between requests to preserve in-memory state
        // This is essential for event sourcing tests with in-memory stores
        $this->client->disableReboot();
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    /**
     * Make a GET request.
     *
     * @param array<string, mixed>  $query
     * @param array<string, string> $headers
     */
    protected function get(string $uri, array $query = [], array $headers = []): Response
    {
        return $this->request('GET', $uri, [], $query, $headers);
    }

    /**
     * Make a POST request.
     *
     * @param array<string, mixed>  $data
     * @param array<string, string> $headers
     */
    protected function post(string $uri, array $data = [], array $headers = []): Response
    {
        return $this->request('POST', $uri, $data, [], $headers);
    }

    /**
     * Make a PUT request.
     *
     * @param array<string, mixed>  $data
     * @param array<string, string> $headers
     */
    protected function put(string $uri, array $data = [], array $headers = []): Response
    {
        return $this->request('PUT', $uri, $data, [], $headers);
    }

    /**
     * Make a PATCH request.
     *
     * @param array<string, mixed>  $data
     * @param array<string, string> $headers
     */
    protected function patch(string $uri, array $data = [], array $headers = []): Response
    {
        return $this->request('PATCH', $uri, $data, [], $headers);
    }

    /**
     * Make a DELETE request.
     *
     * @param array<string, string> $headers
     */
    protected function delete(string $uri, array $headers = []): Response
    {
        return $this->request('DELETE', $uri, [], [], $headers);
    }

    /**
     * Make an HTTP request.
     *
     * @param array<string, mixed>  $data
     * @param array<string, mixed>  $query
     * @param array<string, string> $headers
     */
    protected function request(
        string $method,
        string $uri,
        array $data = [],
        array $query = [],
        array $headers = [],
    ): Response {
        $fullUri = $this->buildUri($uri, $query);

        $serverHeaders = $this->buildServerHeaders($headers);

        $content = $data !== [] ? json_encode($data, JSON_THROW_ON_ERROR) : null;

        $this->client->request($method, $fullUri, [], [], $serverHeaders, $content);

        return $this->client->getResponse();
    }

    /**
     * Build the full URI with API version prefix.
     *
     * @param array<string, mixed> $query
     */
    protected function buildUri(string $uri, array $query = []): string
    {
        // Add API version prefix if not already present
        if (! str_starts_with($uri, '/api/')) {
            $uri = sprintf('/api/%s%s', $this->apiVersion, $uri);
        }

        if ($query !== []) {
            $uri .= '?' . http_build_query($query);
        }

        return $uri;
    }

    /**
     * Build server headers array.
     *
     * @param array<string, string> $headers
     *
     * @return array<string, string>
     */
    protected function buildServerHeaders(array $headers): array
    {
        $serverHeaders = [
            'CONTENT_TYPE' => $this->contentType,
            'HTTP_ACCEPT' => 'application/json',
        ];

        if ($this->authToken !== null) {
            $serverHeaders['HTTP_AUTHORIZATION'] = 'Bearer ' . $this->authToken;
        }

        foreach ($headers as $name => $value) {
            $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
            $serverHeaders[$key] = $value;
        }

        return $serverHeaders;
    }

    /**
     * Set authentication token for subsequent requests.
     */
    protected function authenticate(string $token): self
    {
        $this->authToken = $token;

        return $this;
    }

    /**
     * Clear authentication.
     */
    protected function unauthenticate(): self
    {
        $this->authToken = null;

        return $this;
    }

    /**
     * Set API version for subsequent requests.
     */
    protected function useApiVersion(string $version): self
    {
        $this->apiVersion = $version;

        return $this;
    }

    /**
     * Get the response as decoded JSON.
     *
     * @return array<string, mixed>|null
     */
    protected function getJsonResponse(?Response $response = null): ?array
    {
        $response ??= $this->client->getResponse();
        $content = $response->getContent();

        if (empty($content) || $content === 'null') {
            return null;
        }

        return json_decode($content, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Assert that the response is successful (2xx).
     * Note: Named assertSuccessfulResponse to avoid conflict with WebTestCase method.
     */
    protected function assertSuccessfulResponse(?Response $response = null): void
    {
        $response ??= $this->client->getResponse();

        self::assertTrue(
            $response->isSuccessful(),
            sprintf('Expected successful response, got %d: %s', $response->getStatusCode(), $response->getContent())
        );
    }

    /**
     * Assert exact status code.
     */
    protected function assertResponseStatusCode(int $expectedCode, ?Response $response = null): void
    {
        $response ??= $this->client->getResponse();

        self::assertEquals(
            $expectedCode,
            $response->getStatusCode(),
            sprintf(
                'Expected status %d, got %d: %s',
                $expectedCode,
                $response->getStatusCode(),
                $response->getContent()
            )
        );
    }

    /**
     * Assert that response is JSON.
     */
    protected function assertResponseIsJson(?Response $response = null): void
    {
        $response ??= $this->client->getResponse();
        $content = $response->getContent();

        // Empty response or null is valid JSON
        if (empty($content) || $content === 'null') {
            return;
        }

        self::assertJson($content, 'Response is not valid JSON');
    }

    /**
     * Assert that response contains specific JSON structure.
     *
     * @param array<string> $keys
     */
    protected function assertJsonHasKeys(array $keys, ?Response $response = null): void
    {
        $data = $this->getJsonResponse($response);

        if ($data === null) {
            self::fail('Response is null or empty');
        }

        foreach ($keys as $key) {
            self::assertArrayHasKey($key, $data, sprintf('JSON response missing key: %s', $key));
        }
    }

    /**
     * Assert that response JSON contains specific values.
     *
     * @param array<string, mixed> $expected
     */
    protected function assertJsonContains(array $expected, ?Response $response = null): void
    {
        $data = $this->getJsonResponse($response);

        if ($data === null) {
            self::fail('Response is null or empty');
        }

        foreach ($expected as $key => $value) {
            self::assertArrayHasKey($key, $data, sprintf('JSON response missing key: %s', $key));
            self::assertEquals($value, $data[$key], sprintf('JSON value mismatch for key: %s', $key));
        }
    }

    /**
     * Assert that response is a validation error (422).
     */
    protected function assertResponseIsValidationError(?Response $response = null): void
    {
        $this->assertResponseStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY, $response);
    }

    /**
     * Assert that response is not found (404).
     */
    protected function assertResponseIsNotFound(?Response $response = null): void
    {
        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND, $response);
    }

    /**
     * Assert that response is unauthorized (401).
     */
    protected function assertResponseIsUnauthorized(?Response $response = null): void
    {
        $this->assertResponseStatusCode(Response::HTTP_UNAUTHORIZED, $response);
    }

    /**
     * Assert that response is forbidden (403).
     */
    protected function assertResponseIsForbidden(?Response $response = null): void
    {
        $this->assertResponseStatusCode(Response::HTTP_FORBIDDEN, $response);
    }

    /**
     * Assert paginated response structure.
     */
    protected function assertPaginatedResponse(?Response $response = null): void
    {
        $this->assertJsonHasKeys(['data', 'meta'], $response);
        $data = $this->getJsonResponse($response);
        self::assertArrayHasKey('pagination', $data['meta']);
    }

    /**
     * Assert HAL+JSON response structure.
     */
    protected function assertHalResponse(?Response $response = null): void
    {
        $this->assertJsonHasKeys(['_links'], $response);
    }
}
