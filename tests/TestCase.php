<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Set up test environment.
     * Symfony handles environment loading via .env.test automatically.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Helper method to create HTTP request simulation.
     * Note: For functional tests, use FunctionalTestCase with KernelBrowser instead.
     *
     * @param array<string, mixed> $data
     */
    protected function makeRequest(string $method, string $uri, array $data = []): string
    {
        $originalMethod = $_SERVER['REQUEST_METHOD'] ?? '';
        $originalUri = $_SERVER['REQUEST_URI'] ?? '';

        $_SERVER['REQUEST_METHOD'] = strtoupper($method);
        $_SERVER['REQUEST_URI'] = $uri;

        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $_POST = $data;
        }

        ob_start();

        try {
            require __DIR__ . '/../routes/web.php';
        } catch (\Exception) {
            // Handle any exceptions during request
        }

        $output = ob_get_clean();

        // Restore original values
        $_SERVER['REQUEST_METHOD'] = $originalMethod;
        $_SERVER['REQUEST_URI'] = $originalUri;
        $_POST = [];

        return $output ?: '';
    }

    /**
     * Assert that a response is valid JSON and optionally contains expected data.
     *
     * @param array<string, mixed>|null $expectedData
     *
     * @return array<string, mixed>
     */
    protected function assertJsonResponse(string $response, ?array $expectedData = null): array
    {
        $decoded = json_decode($response, true);

        $this->assertIsArray($decoded, 'Response is not valid JSON');

        if ($expectedData !== null) {
            foreach ($expectedData as $key => $value) {
                $this->assertArrayHasKey($key, $decoded);
                $this->assertEquals($value, $decoded[$key]);
            }
        }

        return $decoded;
    }
}
