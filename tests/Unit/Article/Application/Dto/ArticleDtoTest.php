<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\Dto;

use Micro\Article\Application\Dto\ArticleDto;
use Micro\Article\Application\Dto\ArticleDtoInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Application\Dto\ArticleDtoDataProvider;

/**
 * Unit tests for ArticleDto.
 */
#[CoversClass(ArticleDto::class)]
final class ArticleDtoTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(ArticleDtoDataProvider::class, 'provideValidConstructionData')]
    public function constructShouldCreateDtoWithValidData(
        string $uuid,
        string $title,
        string $description,
        string $shortDescription,
        string $status,
    ): void {
        // Act
        $dto = new ArticleDto(
            uuid: $uuid,
            title: $title,
            description: $description,
            shortDescription: $shortDescription,
            status: $status
        );

        // Assert
        $this->assertSame($uuid, $dto->uuid);
        $this->assertSame($title, $dto->title);
        $this->assertSame($description, $dto->description);
        $this->assertSame($shortDescription, $dto->shortDescription);
        $this->assertSame($status, $dto->status);
    }

    #[Test]
    public function constructShouldCreateDtoWithAllFields(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $title = 'Test Article';
        $shortDescription = 'Short description.';
        $description = 'Full description.';
        $slug = 'test-article';
        $eventId = 12345;
        $status = 'draft';
        $publishedAt = '2024-01-15T00:00:00+00:00';
        $archivedAt = null;
        $createdAt = '2024-01-01T00:00:00+00:00';
        $updatedAt = '2024-01-15T00:00:00+00:00';

        // Act
        $dto = new ArticleDto(
            $uuid,
            $title,
            $shortDescription,
            $description,
            $slug,
            $eventId,
            $status,
            $publishedAt,
            $archivedAt,
            $createdAt,
            $updatedAt
        );

        // Assert
        $this->assertSame($uuid, $dto->uuid);
        $this->assertSame($title, $dto->title);
        $this->assertSame($shortDescription, $dto->shortDescription);
        $this->assertSame($description, $dto->description);
        $this->assertSame($slug, $dto->slug);
        $this->assertSame($eventId, $dto->eventId);
        $this->assertSame($status, $dto->status);
        $this->assertSame($publishedAt, $dto->publishedAt);
        $this->assertNull($dto->archivedAt);
        $this->assertSame($createdAt, $dto->createdAt);
        $this->assertSame($updatedAt, $dto->updatedAt);
    }

    #[Test]
    public function constructWithNullValuesShouldCreateEmptyDto(): void
    {
        // Act
        $dto = new ArticleDto();

        // Assert
        $this->assertNull($dto->uuid);
        $this->assertNull($dto->title);
        $this->assertNull($dto->shortDescription);
        $this->assertNull($dto->description);
        $this->assertNull($dto->slug);
        $this->assertNull($dto->eventId);
        $this->assertNull($dto->status);
    }

    #[Test]
    #[DataProviderExternal(ArticleDtoDataProvider::class, 'provideSerializationData')]
    public function denormalizeShouldCreateDtoFromArray(array $dtoData, array $expectedJson): void
    {
        // Arrange
        $data = [
            'uuid' => $dtoData['uuid'],
            'title' => $dtoData['title'],
            'short_description' => $dtoData['shortDescription'],
            'description' => $dtoData['description'],
            'status' => $dtoData['status'],
        ];

        // Act
        $dto = ArticleDto::denormalize($data);

        // Assert
        $this->assertInstanceOf(ArticleDto::class, $dto);
        $this->assertSame($expectedJson['uuid'], $dto->uuid);
        $this->assertSame($expectedJson['title'], $dto->title);
        $this->assertSame($expectedJson['description'], $dto->description);
    }

    #[Test]
    public function denormalizeWithEmptyArrayShouldCreateEmptyDto(): void
    {
        // Arrange
        $data = [];

        // Act
        $dto = ArticleDto::denormalize($data);

        // Assert
        $this->assertInstanceOf(ArticleDto::class, $dto);
        $this->assertNull($dto->uuid);
        $this->assertNull($dto->title);
    }

    #[Test]
    public function normalizeShouldReturnArrayWithNonNullValues(): void
    {
        // Arrange
        $dto = new ArticleDto(uuid: '550e8400-e29b-41d4-a716-446655440000', title: 'Test Article', status: 'draft');

        // Act
        $result = $dto->normalize();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('uuid', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayNotHasKey('short_description', $result);
        $this->assertArrayNotHasKey('description', $result);
    }

    #[Test]
    public function normalizeWithEmptyDtoShouldReturnEmptyArray(): void
    {
        // Arrange
        $dto = new ArticleDto();

        // Act
        $result = $dto->normalize();

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function normalizeWithDateFieldsShouldIncludeThem(): void
    {
        // Arrange - all date fields are set
        $dto = new ArticleDto(
            publishedAt: '2024-01-15T10:30:00+00:00',
            archivedAt: '2024-06-15T10:30:00+00:00',
            createdAt: '2024-01-01T00:00:00+00:00',
            updatedAt: '2024-01-15T12:00:00+00:00'
        );

        // Act
        $result = $dto->normalize();

        // Assert - date fields should be included in normalized output
        $this->assertIsArray($result);
        $this->assertArrayHasKey('published_at', $result);
        $this->assertArrayHasKey('archived_at', $result);
        $this->assertArrayHasKey('created_at', $result);
        $this->assertArrayHasKey('updated_at', $result);
        $this->assertSame('2024-01-15T10:30:00+00:00', $result['published_at']);
        $this->assertSame('2024-06-15T10:30:00+00:00', $result['archived_at']);
        $this->assertSame('2024-01-01T00:00:00+00:00', $result['created_at']);
        $this->assertSame('2024-01-15T12:00:00+00:00', $result['updated_at']);
    }

    #[Test]
    public function dtoShouldImplementInterface(): void
    {
        // Arrange
        $dto = new ArticleDto();

        // Assert
        $this->assertInstanceOf(ArticleDtoInterface::class, $dto);
    }

    #[Test]
    public function denormalizeShouldCreateDtoWithAllDateFields(): void
    {
        // Arrange
        $data = [
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'title' => 'Test Article With Dates',
            'short_description' => 'Short description with dates.',
            'description' => 'Full description with all date fields.',
            'slug' => 'test-article-dates',
            'event_id' => 12345,
            'status' => 'published',
            'published_at' => '2024-01-15T10:30:00+00:00',
            'archived_at' => '2024-06-15T10:30:00+00:00',
            'created_at' => '2024-01-01T00:00:00+00:00',
            'updated_at' => '2024-01-15T12:00:00+00:00',
        ];

        // Act
        $dto = ArticleDto::denormalize($data);

        // Assert
        $this->assertInstanceOf(ArticleDto::class, $dto);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $dto->uuid);
        $this->assertSame('Test Article With Dates', $dto->title);
        $this->assertSame('Short description with dates.', $dto->shortDescription);
        $this->assertSame('Full description with all date fields.', $dto->description);
        $this->assertSame('test-article-dates', $dto->slug);
        $this->assertSame(12345, $dto->eventId);
        $this->assertSame('published', $dto->status);
        $this->assertSame('2024-01-15T10:30:00+00:00', $dto->publishedAt);
        $this->assertSame('2024-06-15T10:30:00+00:00', $dto->archivedAt);
        $this->assertSame('2024-01-01T00:00:00+00:00', $dto->createdAt);
        $this->assertSame('2024-01-15T12:00:00+00:00', $dto->updatedAt);
    }

    #[Test]
    public function denormalizeShouldHandlePartialDateFields(): void
    {
        // Arrange - only some date fields provided
        $data = [
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'title' => 'Partial Dates Article',
            'status' => 'draft',
            'created_at' => '2024-01-01T00:00:00+00:00',
        ];

        // Act
        $dto = ArticleDto::denormalize($data);

        // Assert
        $this->assertSame('2024-01-01T00:00:00+00:00', $dto->createdAt);
        $this->assertNull($dto->publishedAt);
        $this->assertNull($dto->archivedAt);
        $this->assertNull($dto->updatedAt);
    }

    #[Test]
    public function normalizeShouldReturnAllNonNullFields(): void
    {
        // Arrange - test with all fields populated
        $dto = new ArticleDto(
            uuid: '550e8400-e29b-41d4-a716-446655440000',
            title: 'Full Article',
            shortDescription: 'Short desc.',
            description: 'Full description here.',
            slug: 'full-article',
            eventId: 99999,
            status: 'published'
        );

        // Act
        $result = $dto->normalize();

        // Assert
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $result['uuid']);
        $this->assertSame('Full Article', $result['title']);
        $this->assertSame('Short desc.', $result['short_description']);
        $this->assertSame('Full description here.', $result['description']);
        $this->assertSame('full-article', $result['slug']);
        $this->assertSame(99999, $result['event_id']);
        $this->assertSame('published', $result['status']);
    }
}
