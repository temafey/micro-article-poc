<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\Event;

use Micro\Article\Domain\Event\ArticlePublishedEvent;
use Micro\Article\Domain\ValueObject\PublishedAt;
use Micro\Article\Domain\ValueObject\Status;
use MicroModule\Base\Domain\ValueObject\Payload;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\UpdatedAt;
use MicroModule\Base\Domain\ValueObject\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\Event\ArticlePublishedEventDataProvider;

/**
 * Unit tests for ArticlePublishedEvent.
 */
#[CoversClass(ArticlePublishedEvent::class)]
final class ArticlePublishedEventTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(ArticlePublishedEventDataProvider::class, 'provideValidConstructionData')]
    public function constructWithValidDataShouldCreateInstance(
        string $processUuid,
        string $uuid,
        string $status,
        string $publishedAt,
        string $updatedAt,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $statusVo = Status::fromNative($status);
        $publishedAtVo = PublishedAt::fromNative($publishedAt);
        $updatedAtVo = UpdatedAt::fromNative($updatedAt);

        // Act
        $event = new ArticlePublishedEvent($processUuidVo, $uuidVo, $statusVo, $publishedAtVo, $updatedAtVo);

        // Assert
        $this->assertInstanceOf(ArticlePublishedEvent::class, $event);
        $this->assertSame($processUuid, $event->getProcessUuid()->toNative());
        $this->assertSame($uuid, $event->getUuid()->toNative());
        $this->assertSame($status, $event->getStatus()->toNative());
        $this->assertInstanceOf(PublishedAt::class, $event->getPublishedAt());
        $this->assertInstanceOf(UpdatedAt::class, $event->getUpdatedAt());
    }

    #[Test]
    #[DataProviderExternal(ArticlePublishedEventDataProvider::class, 'provideSerializationScenarios')]
    public function serializeShouldReturnCorrectArray(
        string $processUuid,
        string $uuid,
        string $status,
        string $publishedAt,
        string $updatedAt,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $statusVo = Status::fromNative($status);
        $publishedAtVo = PublishedAt::fromNative($publishedAt);
        $updatedAtVo = UpdatedAt::fromNative($updatedAt);
        $event = new ArticlePublishedEvent($processUuidVo, $uuidVo, $statusVo, $publishedAtVo, $updatedAtVo);

        // Act
        $serialized = $event->serialize();

        // Assert
        $this->assertArrayHasKey('process_uuid', $serialized);
        $this->assertArrayHasKey('uuid', $serialized);
        $this->assertArrayHasKey('status', $serialized);
        $this->assertArrayHasKey('published_at', $serialized);
        $this->assertArrayHasKey('updated_at', $serialized);
        $this->assertSame($processUuid, $serialized['process_uuid']);
        $this->assertSame($uuid, $serialized['uuid']);
        $this->assertSame($status, $serialized['status']);
    }

    #[Test]
    #[DataProviderExternal(ArticlePublishedEventDataProvider::class, 'provideSerializationScenarios')]
    public function deserializeShouldRecreateEvent(
        string $processUuid,
        string $uuid,
        string $status,
        string $publishedAt,
        string $updatedAt,
    ): void {
        // Arrange
        $data = [
            'process_uuid' => $processUuid,
            'uuid' => $uuid,
            'status' => $status,
            'published_at' => $publishedAt,
            'updated_at' => $updatedAt,
        ];

        // Act
        $event = ArticlePublishedEvent::deserialize($data);

        // Assert
        $this->assertInstanceOf(ArticlePublishedEvent::class, $event);
        $this->assertSame($processUuid, $event->getProcessUuid()->toNative());
        $this->assertSame($uuid, $event->getUuid()->toNative());
        $this->assertSame($status, $event->getStatus()->toNative());
    }

    #[Test]
    public function getStatusShouldReturnStatusValueObject(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $status = Status::fromNative('published');
        $publishedAt = PublishedAt::fromNative('2024-01-15T10:30:00+00:00');
        $updatedAt = UpdatedAt::fromNative('2024-01-15T10:30:00+00:00');
        $event = new ArticlePublishedEvent($processUuid, $uuid, $status, $publishedAt, $updatedAt);

        // Act
        $result = $event->getStatus();

        // Assert
        $this->assertInstanceOf(Status::class, $result);
        $this->assertSame('published', $result->toNative());
    }

    #[Test]
    public function getPublishedAtShouldReturnPublishedAtValueObject(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $status = Status::fromNative('published');
        $publishedAt = PublishedAt::fromNative('2024-01-15T10:30:00+00:00');
        $updatedAt = UpdatedAt::fromNative('2024-01-15T10:30:00+00:00');
        $event = new ArticlePublishedEvent($processUuid, $uuid, $status, $publishedAt, $updatedAt);

        // Act
        $result = $event->getPublishedAt();

        // Assert
        $this->assertInstanceOf(PublishedAt::class, $result);
    }

    #[Test]
    public function getUpdatedAtShouldReturnUpdatedAtValueObject(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $status = Status::fromNative('published');
        $publishedAt = PublishedAt::fromNative('2024-01-15T10:30:00+00:00');
        $updatedAt = UpdatedAt::fromNative('2024-01-15T10:30:00+00:00');
        $event = new ArticlePublishedEvent($processUuid, $uuid, $status, $publishedAt, $updatedAt);

        // Act
        $result = $event->getUpdatedAt();

        // Assert
        $this->assertInstanceOf(UpdatedAt::class, $result);
    }

    #[Test]
    public function serializeDeserializeRoundTripShouldPreserveData(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $status = Status::fromNative('published');
        $publishedAt = PublishedAt::fromNative('2024-01-15T10:30:00+00:00');
        $updatedAt = UpdatedAt::fromNative('2024-01-15T10:30:00+00:00');
        $original = new ArticlePublishedEvent($processUuid, $uuid, $status, $publishedAt, $updatedAt);

        // Act
        $serialized = $original->serialize();
        $restored = ArticlePublishedEvent::deserialize($serialized);

        // Assert
        $this->assertSame($original->getProcessUuid()->toNative(), $restored->getProcessUuid()->toNative());
        $this->assertSame($original->getUuid()->toNative(), $restored->getUuid()->toNative());
        $this->assertSame($original->getStatus()->toNative(), $restored->getStatus()->toNative());
    }

    #[Test]
    public function constructWithPayloadShouldIncludePayload(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $status = Status::fromNative('published');
        $publishedAt = PublishedAt::fromNative('2024-01-15T10:30:00+00:00');
        $updatedAt = UpdatedAt::fromNative('2024-01-15T10:30:00+00:00');
        $payload = Payload::fromNative(['source' => 'api', 'publisher' => 'admin']);

        // Act
        $event = new ArticlePublishedEvent($processUuid, $uuid, $status, $publishedAt, $updatedAt, $payload);
        $serialized = $event->serialize();

        // Assert
        $this->assertArrayHasKey('payload', $serialized);
        $this->assertSame(['source' => 'api', 'publisher' => 'admin'], $serialized['payload']);
    }

    #[Test]
    public function deserializeWithPayloadShouldRestorePayload(): void
    {
        // Arrange
        $payload = ['source' => 'api', 'publisher' => 'admin'];
        $data = [
            'process_uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'status' => 'published',
            'published_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-01-15T10:30:00+00:00',
            'payload' => $payload,
        ];

        // Act
        $event = ArticlePublishedEvent::deserialize($data);

        // Assert
        $this->assertInstanceOf(ArticlePublishedEvent::class, $event);
        $this->assertNotNull($event->getPayload());
        $this->assertSame($payload, $event->getPayload()->toNative());
    }
}
