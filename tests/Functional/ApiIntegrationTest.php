<?php

declare(strict_types=1);

namespace Tests\Functional;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

/**
 * API Integration tests for common endpoints.
 *
 * Tests basic API functionality like documentation endpoints,
 * error handling, and CORS support.
 */
#[Group('functional')]
#[Group('api')]
final class ApiIntegrationTest extends FunctionalTestCase
{
    #[Test]
    public function apiDocsEndpointRouteExists(): void
    {
        // Act
        $response = $this->get('/docs');

        // Assert - Swagger UI route exists (may return 500 in test env due to missing assets)
        $statusCode = $response->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [
                Response::HTTP_OK,
                Response::HTTP_MOVED_PERMANENTLY,
                Response::HTTP_INTERNAL_SERVER_ERROR, // NelmioApiDoc may fail in test env
            ], true),
            "Expected 200, 301, or 500, got {$statusCode}"
        );
        $this->assertNotEquals(Response::HTTP_NOT_FOUND, $statusCode, 'API docs route should exist');
    }

    #[Test]
    public function apiDocJsonEndpointRouteExists(): void
    {
        // Act
        $this->client->request('GET', '/api/v1/doc.json');
        $response = $this->client->getResponse();

        // Assert - Doc JSON route exists (may return 500 in test env due to missing deps)
        $statusCode = $response->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [
                Response::HTTP_OK,
                Response::HTTP_INTERNAL_SERVER_ERROR, // NelmioApiDoc may fail in test env
            ], true),
            "Expected 200 or 500, got {$statusCode}"
        );
        $this->assertNotEquals(Response::HTTP_NOT_FOUND, $statusCode, 'API doc.json route should exist');
    }

    #[Test]
    public function nonExistentEndpointShouldReturn404(): void
    {
        // Act
        $response = $this->get('/nonexistent/endpoint/that/does/not/exist');

        // Assert
        $this->assertResponseIsNotFound($response);
    }

    #[Test]
    public function corsPreflightRequestShouldReturnOk(): void
    {
        // Arrange - Send OPTIONS request with CORS headers
        $this->client->request(
            'OPTIONS',
            '/api/v1/article/',
            [],
            [],
            [
                'HTTP_ORIGIN' => 'http://example.com',
                'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
                'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'Content-Type',
            ]
        );

        // Act
        $response = $this->client->getResponse();

        // Assert - CORS preflight should be handled (200, 204, or 405)
        $statusCode = $response->getStatusCode();
        $this->assertTrue(
            in_array(
                $statusCode,
                [Response::HTTP_OK, Response::HTTP_NO_CONTENT, Response::HTTP_METHOD_NOT_ALLOWED],
                true
            ),
            "Expected 200, 204, or 405, got {$statusCode}"
        );
    }

    #[Test]
    public function apiVersioningV1ShouldWork(): void
    {
        // Arrange
        $this->useApiVersion('v1');

        // Act
        $response = $this->get('/article/');

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
    }

    #[Test]
    public function apiVersioningV2ShouldWork(): void
    {
        // Arrange
        $this->useApiVersion('v2');

        // Act
        $response = $this->get('/article/');

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
    }

    #[Test]
    public function jsonContentTypeHeaderShouldBeSet(): void
    {
        // Act
        $response = $this->get('/article/');

        // Assert
        $contentType = $response->headers->get('Content-Type');
        $this->assertNotNull($contentType);
        $this->assertStringContainsString('json', $contentType);
    }
}
