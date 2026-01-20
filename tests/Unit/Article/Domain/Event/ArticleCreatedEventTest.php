<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\Event;

use Micro\Article\Domain\Event\ArticleCreatedEvent;
use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Domain\ValueObject\Payload;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\Event\ArticleCreatedEventDataProvider;

/**
 * Unit tests for ArticleCreatedEvent.
 */
#[CoversClass(ArticleCreatedEvent::class)]
final class ArticleCreatedEventTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(ArticleCreatedEventDataProvider::class, 'provideValidConstructionData')]
    public function constructWithValidDataShouldCreateInstance(
        string $processUuid,
        string $uuid,
        array $articleData,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $articleVo = Article::fromNative($articleData);

        // Act
        $event = new ArticleCreatedEvent($processUuidVo, $uuidVo, $articleVo);

        // Assert
        $this->assertInstanceOf(ArticleCreatedEvent::class, $event);
        $this->assertSame($processUuid, $event->getProcessUuid()->toNative());
        $this->assertSame($uuid, $event->getUuid()->toNative());
        $this->assertInstanceOf(Article::class, $event->getArticle());
    }

    #[Test]
    #[DataProviderExternal(ArticleCreatedEventDataProvider::class, 'provideSerializationScenarios')]
    public function serializeShouldReturnCorrectArray(
        string $processUuid,
        string $uuid,
        string $title,
        string $description,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $articleData = [
            'title' => $title,
            'short_description' => 'Short description for the test article here.',
            'description' => $description,
            'slug' => 'test-slug',
            'status' => 'draft',
        ];
        $articleVo = Article::fromNative($articleData);
        $event = new ArticleCreatedEvent($processUuidVo, $uuidVo, $articleVo);

        // Act
        $serialized = $event->serialize();

        // Assert
        $this->assertIsArray($serialized);
        $this->assertArrayHasKey('process_uuid', $serialized);
        $this->assertArrayHasKey('uuid', $serialized);
        $this->assertArrayHasKey('article', $serialized);
        $this->assertSame($processUuid, $serialized['process_uuid']);
        $this->assertSame($uuid, $serialized['uuid']);
    }

    #[Test]
    #[DataProviderExternal(ArticleCreatedEventDataProvider::class, 'provideDeserializationScenarios')]
    public function deserializeShouldCreateEventFromArray(array $serializedData): void
    {
        // Act
        $event = ArticleCreatedEvent::deserialize($serializedData);

        // Assert
        $this->assertInstanceOf(ArticleCreatedEvent::class, $event);
        $this->assertSame($serializedData['process_uuid'], $event->getProcessUuid()->toNative());
        $this->assertSame($serializedData['uuid'], $event->getUuid()->toNative());
        $this->assertInstanceOf(Article::class, $event->getArticle());
    }

    #[Test]
    public function getArticleShouldReturnArticleValueObject(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $articleData = [
            'title' => 'Test Title',
            'short_description' => 'Test short description here.',
            'description' => 'This is a test description that meets the minimum length requirement for validation.',
            'slug' => 'test-title',
            'status' => 'draft',
        ];
        $article = Article::fromNative($articleData);
        $event = new ArticleCreatedEvent($processUuid, $uuid, $article);

        // Act
        $result = $event->getArticle();

        // Assert
        $this->assertInstanceOf(Article::class, $result);
        $this->assertSame('Test Title', $result->getTitle()->toNative());
    }

    #[Test]
    public function constructWithPayloadShouldIncludePayload(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $article = Article::fromNative([
            'title' => 'Test Title',
            'short_description' => 'Short description here.',
            'description' => 'This is a test description that meets the minimum length requirement for validation purposes.',
            'slug' => 'test-title',
            'status' => 'draft',
        ]);
        $payload = Payload::fromNative([
            'source' => 'api',
            'user_id' => '123',
        ]);

        // Act
        $event = new ArticleCreatedEvent($processUuid, $uuid, $article, $payload);
        $serialized = $event->serialize();

        // Assert
        $this->assertArrayHasKey('payload', $serialized);
        $this->assertSame([
            'source' => 'api',
            'user_id' => '123',
        ], $serialized['payload']);
    }

    #[Test]
    public function getProcessUuidShouldReturnCorrectValue(): void
    {
        // Arrange
        $expectedUuid = '550e8400-e29b-41d4-a716-446655440000';
        $processUuid = ProcessUuid::fromNative($expectedUuid);
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $article = Article::fromNative([
            'title' => 'Test',
            'short_description' => 'Short desc here.',
            'description' => 'This is a test description that meets the minimum fifty character length requirement.',
            'slug' => 'test',
            'status' => 'draft',
        ]);
        $event = new ArticleCreatedEvent($processUuid, $uuid, $article);

        // Act
        $result = $event->getProcessUuid();

        // Assert
        $this->assertSame($expectedUuid, $result->toNative());
    }

    #[Test]
    public function getUuidShouldReturnCorrectValue(): void
    {
        // Arrange
        $expectedUuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative($expectedUuid);
        $article = Article::fromNative([
            'title' => 'Test',
            'short_description' => 'Short desc here.',
            'description' => 'This is a test description that meets the minimum fifty character length requirement.',
            'slug' => 'test',
            'status' => 'draft',
        ]);
        $event = new ArticleCreatedEvent($processUuid, $uuid, $article);

        // Act
        $result = $event->getUuid();

        // Assert
        $this->assertSame($expectedUuid, $result->toNative());
    }
}
