<?php

declare(strict_types=1);

namespace Tests\Api;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;

/**
 * API endpoint tests for basic API functionality.
 *
 * Tests common API endpoints like health checks, documentation,
 * and error handling using Symfony's WebTestCase infrastructure.
 */
#[Group('api')]
final class ApiTest extends FunctionalTestCase
{
    #[Test]
    public function apiHealthCheckShouldReturnHealthy(): void
    {
        // Act - Health endpoint is at /health, not under /api
        $this->client->request('GET', '/health');
        $response = $this->client->getResponse();

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
        $this->assertResponseIsJson($response);

        $data = $this->getJsonResponse($response);
        $this->assertArrayHasKey('status', $data);
        $this->assertEquals('ok', $data['status']);
    }

    #[Test]
    public function apiHealthLiveEndpointShouldReturnOk(): void
    {
        // Act
        $this->client->request('GET', '/health/live');
        $response = $this->client->getResponse();

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
        $this->assertResponseIsJson($response);
    }

    #[Test]
    public function apiHealthReadyEndpointShouldReturnOk(): void
    {
        // Act
        $this->client->request('GET', '/health/ready');
        $response = $this->client->getResponse();

        // Assert
        $statusCode = $response->getStatusCode();
        // Ready endpoint may return 200 (ready) or 503 (not ready) depending on dependencies
        $this->assertTrue(
            in_array($statusCode, [Response::HTTP_OK, Response::HTTP_SERVICE_UNAVAILABLE], true),
            "Expected 200 or 503, got {$statusCode}"
        );
    }
}
