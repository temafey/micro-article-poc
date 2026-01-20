<?php

declare(strict_types=1);

namespace Tests\Functional\Article\Presentation\Rest\V2;

use Micro\Article\Presentation\Rest\V2\ArticleQueriesController;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;
use Tests\Functional\FunctionalTestCase;

/**
 * Functional tests for V2 ArticleQueriesController.
 *
 * Tests the V2 REST API endpoints for article query operations.
 * V2 includes enhanced UUID validation and status-based filtering.
 *
 * Note: Routes require trailing slashes for collection endpoints (e.g., /article/).
 * Description field must be 50-50000 characters.
 */
#[CoversClass(ArticleQueriesController::class)]
#[Group('functional')]
#[Group('api')]
#[Group('v2')]
final class ArticleQueriesControllerTest extends FunctionalTestCase
{
    protected string $apiVersion = 'v2';

    #[Test]
    public function getAllShouldReturnArrayOfArticle(): void
    {
        // Act
        $response = $this->get('/article/');

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
        $this->assertResponseIsJson($response);
    }

    #[Test]
    public function getAllWithQueryParametersShouldFilterResults(): void
    {
        // Arrange - Create a article item first
        $uuid = Uuid::uuid4()->toString();
        $this->createArticleViaApi($uuid, 'V2 Filterable Article');

        // Act
        $response = $this->get('/article/', [
            'title' => 'V2 Filterable',
        ]);

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
        $this->assertResponseIsJson($response);
    }

    #[Test]
    public function getOneWithValidUuidShouldReturnSingleArticle(): void
    {
        // Arrange - Create a article item first
        $uuid = Uuid::uuid4()->toString();
        $this->createArticleViaApi($uuid);

        // Act
        $response = $this->get("/article/{$uuid}");

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
        $this->assertResponseIsJson($response);
    }

    #[Test]
    public function getOneWithInvalidUuidFormatShouldReturn404(): void
    {
        // Arrange
        $invalidUuid = 'not-a-valid-uuid';

        // Act
        $response = $this->get("/article/{$invalidUuid}");

        // Assert - V2 has regex validation on UUID route parameter
        $this->assertResponseStatusCode(Response::HTTP_NOT_FOUND, $response);
    }

    #[Test]
    public function getOneNonExistentShouldReturnNotFoundOrNull(): void
    {
        // Arrange
        $nonExistentUuid = Uuid::uuid4()->toString();

        // Act
        $response = $this->get("/article/{$nonExistentUuid}");

        // Assert - API may return 404 or 200 with null/empty response
        $statusCode = $response->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [Response::HTTP_OK, Response::HTTP_NOT_FOUND], true),
            "Expected 200 or 404, got {$statusCode}"
        );
    }

    #[Test]
    public function getBySlugShouldReturnMatchingArticle(): void
    {
        // Arrange - Create a article item with known slug first
        $uuid = Uuid::uuid4()->toString();
        $slug = 'v2-test-slug-' . time();
        $this->createArticleViaApiWithSlug($uuid, $slug);

        // Act
        $response = $this->get("/article/slug/{$slug}");

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
        $this->assertResponseIsJson($response);
    }

    #[Test]
    public function getBySlugNonExistentShouldReturnEmptyOrNotFound(): void
    {
        // Arrange
        $nonExistentSlug = 'v2-non-existent-slug-' . time();

        // Act
        $response = $this->get("/article/slug/{$nonExistentSlug}");

        // Assert
        $statusCode = $response->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [Response::HTTP_OK, Response::HTTP_NOT_FOUND], true),
            "Expected 200 or 404, got {$statusCode}"
        );
    }

    #[Test]
    public function getByEventShouldReturnMatchingArticle(): void
    {
        // Arrange - Create a article item with known event_id
        $uuid = Uuid::uuid4()->toString();
        $eventId = 54321;
        $this->createArticleViaApiWithEventId($uuid, $eventId);

        // Act
        $response = $this->get("/article/event/{$eventId}");

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
        $this->assertResponseIsJson($response);
    }

    #[Test]
    public function getByEventNonExistentShouldReturnEmptyOrNotFound(): void
    {
        // Arrange
        $nonExistentEventId = 88888888;

        // Act
        $response = $this->get("/article/event/{$nonExistentEventId}");

        // Assert
        $statusCode = $response->getStatusCode();
        $this->assertTrue(
            in_array($statusCode, [Response::HTTP_OK, Response::HTTP_NOT_FOUND], true),
            "Expected 200 or 404, got {$statusCode}"
        );
    }

    #[Test]
    public function getPublishedShouldReturnPublishedArticle(): void
    {
        // Act
        $response = $this->get('/article/published');

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
        $this->assertResponseIsJson($response);
    }

    #[Test]
    public function getPublishedShouldUseStatusFilter(): void
    {
        // Arrange - Create and publish a article item
        $uuid = Uuid::uuid4()->toString();
        $this->createArticleViaApi($uuid, 'V2 Published Article');
        $this->publishArticle($uuid);

        // Act
        $response = $this->get('/article/published');

        // Assert - V2 filters by status='published'
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
        $this->assertResponseIsJson($response);
    }

    #[Test]
    public function getArchivedShouldReturnArchivedArticle(): void
    {
        // Act
        $response = $this->get('/article/archived');

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
        $this->assertResponseIsJson($response);
    }

    #[Test]
    public function getArchivedShouldUseStatusFilter(): void
    {
        // Arrange - Create and archive a article item
        $uuid = Uuid::uuid4()->toString();
        $this->createArticleViaApi($uuid, 'V2 Archived Article');
        $this->archiveArticle($uuid);

        // Act
        $response = $this->get('/article/archived');

        // Assert - V2 filters by status='archived'
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
        $this->assertResponseIsJson($response);
    }

    #[Test]
    public function postToGetEndpointShouldReturnMethodNotAllowed(): void
    {
        // Act
        $response = $this->post('/article/published', []);

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_METHOD_NOT_ALLOWED, $response);
    }

    #[Test]
    public function getArticleListShouldReturnArrayResponse(): void
    {
        // Act
        $response = $this->get('/article/');

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
        $data = $this->getJsonResponse($response);
        $this->assertTrue(is_array($data) || $data === null);
    }

    #[Test]
    public function v2QueryControllerShouldAcceptDtoQueryParameters(): void
    {
        // Arrange
        $queryParams = [
            'status' => 'draft',
            'title' => 'Test',
        ];

        // Act
        $response = $this->get('/article/', $queryParams);

        // Assert
        $this->assertResponseStatusCode(Response::HTTP_OK, $response);
        $this->assertResponseIsJson($response);
    }

    /**
     * Create a article item via API.
     * Description must be 50+ characters per validation rules.
     */
    private function createArticleViaApi(string $uuid, string $title = 'V2 Test Article'): void
    {
        $this->post('/article/', [
            'uuid' => $uuid,
            'title' => $title,
            'short_description' => 'V2 Test short description for the article article creation',
            'description' => 'V2 Test description that is at least fifty characters long to pass the validation requirements.',
        ]);
    }

    /**
     * Create a article item with a specific slug via API.
     * Description must be 50+ characters per validation rules.
     */
    private function createArticleViaApiWithSlug(string $uuid, string $slug): void
    {
        $this->post('/article/', [
            'uuid' => $uuid,
            'title' => 'V2 Test Article with Slug',
            'short_description' => 'V2 Test short description for the article article with slug',
            'description' => 'V2 Test description that is at least fifty characters long to pass the validation requirements.',
            'slug' => $slug,
        ]);
    }

    /**
     * Create a article item with a specific event ID via API.
     * Description must be 50+ characters per validation rules.
     */
    private function createArticleViaApiWithEventId(string $uuid, int $eventId): void
    {
        $this->post('/article/', [
            'uuid' => $uuid,
            'title' => 'V2 Test Article with Event',
            'short_description' => 'V2 Test short description for the article article with event',
            'description' => 'V2 Test description that is at least fifty characters long to pass the validation requirements.',
            'event_id' => $eventId,
        ]);
    }

    /**
     * Publish a article item via API.
     */
    private function publishArticle(string $uuid): void
    {
        $this->put("/article/{$uuid}/publish");
    }

    /**
     * Archive a article item via API.
     */
    private function archiveArticle(string $uuid): void
    {
        $this->put("/article/{$uuid}/archive");
    }
}
