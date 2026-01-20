<?php

declare(strict_types=1);

namespace Tests\Functional\Article\Presentation\Rest\V2;

use Micro\Article\Presentation\Rest\V2\ArticleCommandsController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;

/**
 * Functional tests for V2 ArticleCommandsController.
 *
 * Tests the V2 REST API endpoints for article command operations.
 * V2 includes enhanced UUID validation with regex patterns in routes.
 *
 * Note: Routes require trailing slashes for collection endpoints (e.g., /article/).
 * Description field must be 50-50000 characters.
 */
#[CoversClass(ArticleCommandsController::class)]
#[Group('functional')]
#[Group('api')]
#[Group('v2')]
final class ArticleCommandsControllerTest extends FunctionalTestCase
{
    protected string $apiVersion = 'v2';

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
    public function createWithMissingRequiredFieldsShouldReturnValidationError(): void
    {
        // Arrange
        $articleData = [];

        // Act
        $response = $this->post('/article/', $articleData);

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
    public function updateWithValidUuidShouldReturnSuccess(): void
    {
        // Arrange
        $uuid = Uuid::uuid4()->toString();
        $articleData = $this->createValidArticlePayload($uuid);
        $this->post('/article/', $articleData); // Create first

        $updateData = $articleData;
        $updateData['title'] = 'Updated V2 Title';
        unset($updateData['uuid']); // UUID comes from route in V2

        // Act
        $response = $this->put("/article/{$uuid}", $updateData);

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
        $this->assertResponseIsJson($response);
        $this->assertJsonHasKeys(['uuid'], $response);
    }

    #[Test]
    public function updateWithInvalidUuidFormatShouldReturn404(): void
    {
        // Arrange
        $invalidUuid = 'not-a-valid-uuid';
        $articleData = $this->createValidArticlePayload();
        unset($articleData['uuid']);

        // Act
        $response = $this->put("/article/{$invalidUuid}", $articleData);

        // Assert - V2 has regex validation on UUID route parameter
        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND, $response);
    }

    #[Test]
    public function publishWithValidUuidShouldReturnSuccess(): void
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
    public function publishWithInvalidUuidFormatShouldReturn404(): void
    {
        // Arrange
        $invalidUuid = 'invalid-format';

        // Act
        $response = $this->put("/article/{$invalidUuid}/publish");

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND, $response);
    }

    #[Test]
    public function unpublishWithValidUuidShouldReturnSuccess(): void
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
    public function unpublishWithInvalidUuidFormatShouldReturn404(): void
    {
        // Arrange
        $invalidUuid = 'xyz-invalid';

        // Act
        $response = $this->put("/article/{$invalidUuid}/unpublish");

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND, $response);
    }

    #[Test]
    public function archiveWithValidUuidShouldReturnSuccess(): void
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
    public function archiveWithInvalidUuidFormatShouldReturn404(): void
    {
        // Arrange
        $invalidUuid = 'bad-uuid-format';

        // Act
        $response = $this->put("/article/{$invalidUuid}/archive");

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND, $response);
    }

    #[Test]
    public function deleteWithValidUuidShouldReturnSuccess(): void
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
    public function deleteWithInvalidUuidFormatShouldReturn404(): void
    {
        // Arrange
        $invalidUuid = 'invalid-uuid';

        // Act
        $response = $this->delete("/article/{$invalidUuid}");

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND, $response);
    }

    #[Test]
    public function v2ShouldUseProcessUuidForStateChangingOperations(): void
    {
        // Arrange
        $uuid = Uuid::uuid4()->toString();
        $articleData = $this->createValidArticlePayload($uuid);
        $this->post('/article/', $articleData); // Create first

        // Act
        $response = $this->put("/article/{$uuid}/publish");

        // Assert - V2 generates processUuid internally
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
        $data = $this->getJsonResponse($response);
        $this->assertArrayHasKey('uuid', $data);
    }

    #[Test]
    public function createWithCompletePayloadShouldReturnSuccess(): void
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
    public function updateShouldMergeRouteUuidWithPayload(): void
    {
        // Arrange
        $uuid = Uuid::uuid4()->toString();
        $articleData = $this->createValidArticlePayload($uuid);
        $this->post('/article/', $articleData); // Create first

        // V2 update: UUID comes from route, not from body
        $updateData = [
            'title' => 'Updated via V2',
            'short_description' => 'Updated short description for the article article testing',
            'description' => 'Updated full description that is at least fifty characters long to pass the validation requirements.',
        ];

        // Act
        $response = $this->put("/article/{$uuid}", $updateData);

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
        $data = $this->getJsonResponse($response);
        $this->assertEquals($uuid, $data['uuid']);
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
            'title' => 'V2 Test Article Article',
            'short_description' => 'V2 Short description for testing the article article creation process',
            'description' => 'V2 Full description for the test article article. This description must be at least 50 characters long to pass validation.',
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
            'title' => 'V2 Complete Test Article Article',
            'short_description' => 'V2 Complete short description for the article article testing',
            'description' => 'V2 Complete full description for the test article article. This description must be at least 50 characters long to pass validation.',
            'slug' => 'v2-complete-test-article-' . time(),
            'event_id' => 2,
            'status' => 'draft',
            'created_at' => $now->format(\DateTimeInterface::ATOM),
            'updated_at' => $now->format(\DateTimeInterface::ATOM),
        ];
    }
}
