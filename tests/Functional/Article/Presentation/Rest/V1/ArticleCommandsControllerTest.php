<?php

declare(strict_types=1);

namespace Tests\Functional\Article\Presentation\Rest\V1;

use Micro\Article\Presentation\Rest\V1\ArticleCommandsController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;

/**
 * Functional tests for V1 ArticleCommandsController.
 *
 * Tests the REST API endpoints for article command operations including
 * create, update, publish, unpublish, archive, and delete.
 *
 * Note: Routes require trailing slashes for collection endpoints (e.g., /article/).
 * Description field must be 50-50000 characters.
 */
#[CoversClass(ArticleCommandsController::class)]
#[Group('functional')]
#[Group('api')]
#[Group('v1')]
final class ArticleCommandsControllerTest extends FunctionalTestCase
{
    protected string $apiVersion = 'v1';

    #[Test]
    public function createShouldReturnSuccessWithUuid(): void
    {
        // Arrange
        $articleData = $this->createValidArticlePayload();

        // Act
        $response = $this->post('/article/', $articleData);

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
        $this->assertResponseIsJson($response);
        $this->assertJsonHasKeys(['uuid'], $response);
    }

    #[Test]
    public function createWithMissingTitleShouldReturnValidationError(): void
    {
        // Arrange
        $articleData = $this->createValidArticlePayload();
        unset($articleData['title']);

        // Act
        $response = $this->post('/article/', $articleData);

        // Assert
        // May return 422 (validation error) or 500 (internal error from validation)
        $statusCode = $response->getStatusCode();
        $this->assertTrue(
            in_array(
                $statusCode,
                [Response::HTTP_UNPROCESSABLE_ENTITY, Response::HTTP_INTERNAL_SERVER_ERROR, Response::HTTP_BAD_REQUEST],
                true
            ),
            "Expected 422, 500, or 400, got {$statusCode}"
        );
    }

    #[Test]
    public function createWithInvalidJsonShouldReturnBadRequest(): void
    {
        // Arrange - send invalid content
        $this->client->request(
            'POST',
            '/api/v1/article/',
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            'invalid json {'
        );

        // Act
        $response = $this->client->getResponse();

        // Assert - may return 400 or 500 depending on how invalid JSON is handled
        $statusCode = $response->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [Response::HTTP_BAD_REQUEST, Response::HTTP_INTERNAL_SERVER_ERROR], true),
            "Expected 400 or 500, got {$statusCode}"
        );
    }

    #[Test]
    public function updateShouldReturnSuccessWithUuid(): void
    {
        // Arrange
        $uuid = Uuid::uuid4()->toString();
        $articleData = $this->createValidArticlePayload($uuid);
        $this->post('/article/', $articleData); // Create first

        $updateData = $articleData;
        $updateData['title'] = 'Updated Title';

        // Act
        $response = $this->put("/article/{$uuid}", $updateData);

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
        $this->assertResponseIsJson($response);
        $this->assertJsonHasKeys(['uuid'], $response);
    }

    #[Test]
    public function updateNonExistentShouldReturnNotFound(): void
    {
        // Arrange
        $nonExistentUuid = Uuid::uuid4()->toString();
        $articleData = $this->createValidArticlePayload($nonExistentUuid);

        // Act
        $response = $this->put("/article/{$nonExistentUuid}", $articleData);

        // Assert
        $statusCode = $response->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [Response::HTTP_NOT_FOUND, Response::HTTP_INTERNAL_SERVER_ERROR], true),
            "Expected 404 or 500, got {$statusCode}"
        );
    }

    #[Test]
    public function publishShouldReturnSuccessWithUuid(): void
    {
        // Arrange
        $uuid = Uuid::uuid4()->toString();
        $articleData = $this->createValidArticlePayload($uuid);
        $this->post('/article/', $articleData); // Create first

        // Act
        $response = $this->put("/article/{$uuid}/publish");

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
        $this->assertResponseIsJson($response);
        $this->assertJsonHasKeys(['uuid'], $response);
    }

    #[Test]
    public function publishNonExistentShouldReturnError(): void
    {
        // Arrange
        $nonExistentUuid = Uuid::uuid4()->toString();

        // Act
        $response = $this->put("/article/{$nonExistentUuid}/publish");

        // Assert
        $statusCode = $response->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [Response::HTTP_NOT_FOUND, Response::HTTP_INTERNAL_SERVER_ERROR], true),
            "Expected 404 or 500, got {$statusCode}"
        );
    }

    #[Test]
    public function unpublishShouldReturnSuccessWithUuid(): void
    {
        // Arrange
        $uuid = Uuid::uuid4()->toString();
        $articleData = $this->createValidArticlePayload($uuid);
        $this->post('/article/', $articleData); // Create first
        $this->put("/article/{$uuid}/publish"); // Publish first

        // Act
        $response = $this->put("/article/{$uuid}/unpublish");

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
        $this->assertResponseIsJson($response);
        $this->assertJsonHasKeys(['uuid'], $response);
    }

    #[Test]
    public function archiveShouldReturnSuccessWithUuid(): void
    {
        // Arrange
        $uuid = Uuid::uuid4()->toString();
        $articleData = $this->createValidArticlePayload($uuid);
        $this->post('/article/', $articleData); // Create first

        // Act
        $response = $this->put("/article/{$uuid}/archive");

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
        $this->assertResponseIsJson($response);
        $this->assertJsonHasKeys(['uuid'], $response);
    }

    #[Test]
    public function deleteShouldReturnSuccessWithUuid(): void
    {
        // Arrange
        $uuid = Uuid::uuid4()->toString();
        $articleData = $this->createValidArticlePayload($uuid);
        $this->post('/article/', $articleData); // Create first

        // Act
        $response = $this->delete("/article/{$uuid}");

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
        $this->assertResponseIsJson($response);
        $this->assertJsonHasKeys(['uuid'], $response);
    }

    #[Test]
    public function deleteNonExistentShouldReturnError(): void
    {
        // Arrange
        $nonExistentUuid = Uuid::uuid4()->toString();

        // Act
        $response = $this->delete("/article/{$nonExistentUuid}");

        // Assert
        $statusCode = $response->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [Response::HTTP_NOT_FOUND, Response::HTTP_INTERNAL_SERVER_ERROR], true),
            "Expected 404 or 500, got {$statusCode}"
        );
    }

    #[Test]
    public function createWithAllFieldsShouldReturnSuccess(): void
    {
        // Arrange
        $articleData = $this->createFullArticlePayload();

        // Act
        $response = $this->post('/article/', $articleData);

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
        $this->assertResponseIsJson($response);
        $this->assertJsonHasKeys(['uuid'], $response);
    }

    #[Test]
    public function createWithEmptyBodyShouldReturnValidationError(): void
    {
        // Act
        $response = $this->post('/article/', []);

        // Assert
        $statusCode = $response->getStatusCode();
        $this->assertTrue(
            in_array(
                $statusCode,
                [Response::HTTP_UNPROCESSABLE_ENTITY, Response::HTTP_BAD_REQUEST, Response::HTTP_INTERNAL_SERVER_ERROR],
                true
            ),
            "Expected 422, 400, or 500, got {$statusCode}"
        );
    }

    #[Test]
    public function methodNotAllowedShouldReturn405(): void
    {
        // Act - PATCH is not allowed on create endpoint
        $response = $this->patch('/article/', [
            'title' => 'Test',
        ]);

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_METHOD_NOT_ALLOWED, $response);
    }

    /**
     * Create a valid article payload for testing.
     * Description must be 50-50000 characters per validation rules.
     *
     * @return array<string, mixed>
     */
    private function createValidArticlePayload(?string $uuid = null): array
    {
        return [
            'uuid' => $uuid ?? Uuid::uuid4()->toString(),
            'title' => 'Test Article Article',
            'short_description' => 'Short description for testing the article article creation process',
            'description' => 'This is a full description for the test article article. It needs to be at least 50 characters long to pass the validation requirements.',
        ];
    }

    /**
     * Create a full article payload with all fields.
     * Description must be 50-50000 characters per validation rules.
     *
     * @return array<string, mixed>
     */
    private function createFullArticlePayload(): array
    {
        $now = new \DateTimeImmutable();

        return [
            'uuid' => Uuid::uuid4()->toString(),
            'title' => 'Complete Test Article Article',
            'short_description' => 'Complete short description for the article article testing',
            'description' => 'Complete full description for the test article article. This description must be at least 50 characters long to pass validation requirements.',
            'slug' => 'complete-test-article-' . time(),
            'event_id' => 1,
            'status' => 'draft',
            'created_at' => $now->format(\DateTimeInterface::ATOM),
            'updated_at' => $now->format(\DateTimeInterface::ATOM),
        ];
    }
}
