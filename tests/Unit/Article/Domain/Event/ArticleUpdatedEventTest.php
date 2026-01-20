<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\Event;

use Micro\Article\Domain\Event\ArticleUpdatedEvent;
use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Domain\ValueObject\Payload;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\Event\ArticleUpdatedEventDataProvider;

/**
 * Unit tests for ArticleUpdatedEvent.
 */
#[CoversClass(ArticleUpdatedEvent::class)]
final class ArticleUpdatedEventTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(ArticleUpdatedEventDataProvider::class, 'provideValidConstructionData')]
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
        $event = new ArticleUpdatedEvent($processUuidVo, $uuidVo, $articleVo);

        // Assert
        $this->assertInstanceOf(ArticleUpdatedEvent::class, $event);
        $this->assertSame($processUuid, $event->getProcessUuid()->toNative());
        $this->assertSame($uuid, $event->getUuid()->toNative());
        $this->assertInstanceOf(Article::class, $event->getArticle());
    }

    #[Test]
    #[DataProviderExternal(ArticleUpdatedEventDataProvider::class, 'provideSerializationScenarios')]
    public function serializeShouldReturnCorrectArray(string $processUuid, string $uuid, array $articleData): void
    {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $articleVo = Article::fromNative($articleData);
        $event = new ArticleUpdatedEvent($processUuidVo, $uuidVo, $articleVo);

        // Act
        $serialized = $event->serialize();

        // Assert
        $this->assertArrayHasKey('process_uuid', $serialized);
        $this->assertArrayHasKey('uuid', $serialized);
        $this->assertArrayHasKey('article', $serialized);
        $this->assertSame($processUuid, $serialized['process_uuid']);
        $this->assertSame($uuid, $serialized['uuid']);
    }

    #[Test]
    #[DataProviderExternal(ArticleUpdatedEventDataProvider::class, 'provideSerializationScenarios')]
    public function deserializeShouldRecreateEvent(string $processUuid, string $uuid, array $articleData): void
    {
        // Arrange
        $data = [
            'process_uuid' => $processUuid,
            'uuid' => $uuid,
            'article' => $articleData,
        ];

        // Act
        $event = ArticleUpdatedEvent::deserialize($data);

        // Assert
        $this->assertInstanceOf(ArticleUpdatedEvent::class, $event);
        $this->assertSame($processUuid, $event->getProcessUuid()->toNative());
        $this->assertSame($uuid, $event->getUuid()->toNative());
        $this->assertInstanceOf(Article::class, $event->getArticle());
    }

    #[Test]
    public function getArticleShouldReturnArticleValueObject(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $articleData = [
            'title' => 'Test Update Title',
            'short_description' => 'Test short description.',
            'description' => 'This is a test description that meets the minimum fifty character length requirement.',
        ];
        $article = Article::fromNative($articleData);
        $event = new ArticleUpdatedEvent($processUuid, $uuid, $article);

        // Act
        $result = $event->getArticle();

        // Assert
        $this->assertInstanceOf(Article::class, $result);
        $this->assertSame('Test Update Title', $result->getTitle()->toNative());
    }

    #[Test]
    public function constructWithPayloadShouldIncludePayload(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $article = Article::fromNative([
            'title' => 'Test Title',
            'short_description' => 'Test short description.',
            'description' => 'This is a test description that meets the minimum fifty character length requirement.',
        ]);
        $payload = Payload::fromNative([
            'editor' => 'admin',
        ]);

        // Act
        $event = new ArticleUpdatedEvent($processUuid, $uuid, $article, $payload);
        $serialized = $event->serialize();

        // Assert
        $this->assertArrayHasKey('payload', $serialized);
        $this->assertSame([
            'editor' => 'admin',
        ], $serialized['payload']);
    }

    #[Test]
    public function serializeDeserializeRoundTripShouldPreserveData(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $article = Article::fromNative([
            'title' => 'Round Trip Test',
            'short_description' => 'Round trip short description.',
            'description' => 'This is a round trip test description meeting the minimum character requirement.',
        ]);
        $original = new ArticleUpdatedEvent($processUuid, $uuid, $article);

        // Act
        $serialized = $original->serialize();
        $restored = ArticleUpdatedEvent::deserialize($serialized);

        // Assert
        $this->assertSame($original->getProcessUuid()->toNative(), $restored->getProcessUuid()->toNative());
        $this->assertSame($original->getUuid()->toNative(), $restored->getUuid()->toNative());
    }

    #[Test]
    public function deserializeWithPayloadShouldRestorePayload(): void
    {
        // Arrange
        $payload = ['editor' => 'admin', 'version' => 2];
        $data = [
            'process_uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'article' => [
                'title' => 'Test Title',
                'short_description' => 'Test short description.',
                'description' => 'This is a test description that meets the minimum fifty character length requirement.',
            ],
            'payload' => $payload,
        ];

        // Act
        $event = ArticleUpdatedEvent::deserialize($data);

        // Assert
        $this->assertInstanceOf(ArticleUpdatedEvent::class, $event);
        $this->assertNotNull($event->getPayload());
        $this->assertSame($payload, $event->getPayload()->toNative());
    }
}
