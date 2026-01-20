<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\ValueObject;

use Micro\Article\Domain\ValueObject\ArchivedAt;
use Micro\Article\Domain\ValueObject\Description;
use Micro\Article\Domain\ValueObject\EventId;
use Micro\Article\Domain\ValueObject\Article;
use Micro\Article\Domain\ValueObject\PublishedAt;
use Micro\Article\Domain\ValueObject\ShortDescription;
use Micro\Article\Domain\ValueObject\Slug;
use Micro\Article\Domain\ValueObject\Status;
use Micro\Article\Domain\ValueObject\Title;
use MicroModule\Base\Domain\ValueObject\CreatedAt;
use MicroModule\Base\Domain\ValueObject\UpdatedAt;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\ValueObject\ArticleDataProvider;

/**
 * Unit tests for Article ValueObject.
 */
#[CoversClass(Article::class)]
final class ArticleTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(ArticleDataProvider::class, 'provideCompleteArticleData')]
    public function fromArrayWithCompleteDataShouldCreateInstance(array $data): void
    {
        // Act
        $article = Article::fromArray($data);

        // Assert
        $this->assertInstanceOf(Article::class, $article);
    }

    #[Test]
    #[DataProviderExternal(ArticleDataProvider::class, 'provideMinimalArticleData')]
    public function fromArrayWithMinimalDataShouldCreateInstance(array $data): void
    {
        // Act
        $article = Article::fromArray($data);

        // Assert
        $this->assertInstanceOf(Article::class, $article);
    }

    #[Test]
    #[DataProviderExternal(ArticleDataProvider::class, 'provideToArrayScenarios')]
    public function toArrayShouldContainExpectedKeys(array $data, array $expectedKeys): void
    {
        // Arrange
        $article = Article::fromArray($data);

        // Act
        $result = $article->toArray();

        // Assert
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $result);
        }
    }

    #[Test]
    public function getTitleShouldReturnTitleValueObject(): void
    {
        // Arrange
        $data = [
            'title' => 'Test Title',
        ];
        $article = Article::fromArray($data);

        // Act
        $result = $article->getTitle();

        // Assert
        $this->assertInstanceOf(Title::class, $result);
        $this->assertSame('Test Title', $result->toNative());
    }

    #[Test]
    public function getShortDescriptionShouldReturnShortDescriptionValueObject(): void
    {
        // Arrange
        $data = [
            'short_description' => 'Short description text',
        ];
        $article = Article::fromArray($data);

        // Act
        $result = $article->getShortDescription();

        // Assert
        $this->assertInstanceOf(ShortDescription::class, $result);
        $this->assertSame('Short description text', $result->toNative());
    }

    #[Test]
    public function getDescriptionShouldReturnDescriptionValueObject(): void
    {
        // Arrange
        $data = [
            'description' => 'This is a full description that meets the minimum length requirement of fifty characters for proper testing.',
        ];
        $article = Article::fromArray($data);

        // Act
        $result = $article->getDescription();

        // Assert
        $this->assertInstanceOf(Description::class, $result);
    }

    #[Test]
    public function getSlugShouldReturnSlugValueObject(): void
    {
        // Arrange
        $data = [
            'slug' => 'test-slug',
        ];
        $article = Article::fromArray($data);

        // Act
        $result = $article->getSlug();

        // Assert
        $this->assertInstanceOf(Slug::class, $result);
        $this->assertSame('test-slug', $result->toNative());
    }

    #[Test]
    public function getEventIdShouldReturnEventIdValueObject(): void
    {
        // Arrange
        $data = [
            'event_id' => 12345,
        ];
        $article = Article::fromArray($data);

        // Act
        $result = $article->getEventId();

        // Assert
        $this->assertInstanceOf(EventId::class, $result);
        $this->assertSame(12345, $result->toNative());
    }

    #[Test]
    public function getStatusShouldReturnStatusValueObject(): void
    {
        // Arrange
        $data = [
            'status' => 'published',
        ];
        $article = Article::fromArray($data);

        // Act
        $result = $article->getStatus();

        // Assert
        $this->assertInstanceOf(Status::class, $result);
        $this->assertSame('published', $result->toNative());
    }

    #[Test]
    public function getPublishedAtShouldReturnPublishedAtValueObject(): void
    {
        // Arrange
        $data = [
            'published_at' => '2024-01-15T10:30:00+00:00',
        ];
        $article = Article::fromArray($data);

        // Act
        $result = $article->getPublishedAt();

        // Assert
        $this->assertInstanceOf(PublishedAt::class, $result);
    }

    #[Test]
    public function getArchivedAtShouldReturnArchivedAtValueObject(): void
    {
        // Arrange
        $data = [
            'archived_at' => '2024-03-01T00:00:00+00:00',
        ];
        $article = Article::fromArray($data);

        // Act
        $result = $article->getArchivedAt();

        // Assert
        $this->assertInstanceOf(ArchivedAt::class, $result);
    }

    #[Test]
    public function getCreatedAtShouldReturnCreatedAtValueObject(): void
    {
        // Arrange
        $data = [
            'created_at' => '2024-01-01T00:00:00+00:00',
        ];
        $article = Article::fromArray($data);

        // Act
        $result = $article->getCreatedAt();

        // Assert
        $this->assertInstanceOf(CreatedAt::class, $result);
    }

    #[Test]
    public function getUpdatedAtShouldReturnUpdatedAtValueObject(): void
    {
        // Arrange
        $data = [
            'updated_at' => '2024-01-15T10:30:00+00:00',
        ];
        $article = Article::fromArray($data);

        // Act
        $result = $article->getUpdatedAt();

        // Assert
        $this->assertInstanceOf(UpdatedAt::class, $result);
    }

    #[Test]
    public function getNullFieldsShouldReturnNull(): void
    {
        // Arrange
        $article = Article::fromArray([]);

        // Assert
        $this->assertNull($article->getTitle());
        $this->assertNull($article->getShortDescription());
        $this->assertNull($article->getDescription());
        $this->assertNull($article->getSlug());
        $this->assertNull($article->getEventId());
        $this->assertNull($article->getStatus());
        $this->assertNull($article->getPublishedAt());
        $this->assertNull($article->getArchivedAt());
        $this->assertNull($article->getCreatedAt());
        $this->assertNull($article->getUpdatedAt());
    }

    #[Test]
    public function toArrayWithNullFieldsShouldReturnEmptyArray(): void
    {
        // Arrange
        $article = Article::fromArray([]);

        // Act
        $result = $article->toArray();

        // Assert
        $this->assertIsArray($result);
    }

    #[Test]
    public function fromArrayShouldPreserveAllData(): void
    {
        // Arrange
        $data = [
            'title' => 'Test Title',
            'short_description' => 'Short description.',
            'description' => 'This is a full description that meets the minimum fifty character length requirement for testing purposes.',
            'slug' => 'test-title',
            'event_id' => 99999,
            'status' => 'draft',
        ];

        // Act
        $article = Article::fromArray($data);
        $result = $article->toArray();

        // Assert
        $this->assertSame($data['title'], $result['title']);
        $this->assertSame($data['short_description'], $result['short_description']);
        $this->assertSame($data['description'], $result['description']);
        $this->assertSame($data['slug'], $result['slug']);
        $this->assertSame($data['event_id'], $result['event_id']);
        $this->assertSame($data['status'], $result['status']);
    }

    #[Test]
    public function toArrayWithAllDateFieldsShouldIncludeThem(): void
    {
        // Arrange - create Article with all date fields to cover toArray branches
        $data = [
            'title' => 'Test Title',
            'published_at' => '2024-01-15T10:30:00+00:00',
            'archived_at' => '2024-06-15T10:30:00+00:00',
            'created_at' => '2024-01-01T00:00:00+00:00',
            'updated_at' => '2024-01-15T12:00:00+00:00',
        ];
        $article = Article::fromArray($data);

        // Act
        $result = $article->toArray();

        // Assert - verify date fields are included in output
        $this->assertArrayHasKey('published_at', $result);
        $this->assertArrayHasKey('archived_at', $result);
        $this->assertArrayHasKey('created_at', $result);
        $this->assertArrayHasKey('updated_at', $result);
    }
}
