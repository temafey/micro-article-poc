<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\Event;

use Micro\Article\Domain\Event\ArticleUnpublishedEvent;
use Micro\Article\Domain\ValueObject\Status;
use MicroModule\Base\Domain\ValueObject\Payload;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\UpdatedAt;
use MicroModule\Base\Domain\ValueObject\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\Event\ArticleUnpublishedEventDataProvider;

/**
 * Unit tests for ArticleUnpublishedEvent.
 */
#[CoversClass(ArticleUnpublishedEvent::class)]
final class ArticleUnpublishedEventTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(ArticleUnpublishedEventDataProvider::class, 'provideValidConstructionData')]
    public function constructWithValidDataShouldCreateInstance(
        string $processUuid,
        string $uuid,
        string $status,
        string $updatedAt,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $statusVo = Status::fromNative($status);
        $updatedAtVo = UpdatedAt::fromNative($updatedAt);

        // Act
        $event = new ArticleUnpublishedEvent($processUuidVo, $uuidVo, $statusVo, $updatedAtVo);

        // Assert
        $this->assertInstanceOf(ArticleUnpublishedEvent::class, $event);
        $this->assertSame($processUuid, $event->getProcessUuid()->toNative());
        $this->assertSame($uuid, $event->getUuid()->toNative());
        $this->assertSame($status, $event->getStatus()->toNative());
        $this->assertInstanceOf(UpdatedAt::class, $event->getUpdatedAt());
    }

    #[Test]
    #[DataProviderExternal(ArticleUnpublishedEventDataProvider::class, 'provideSerializationScenarios')]
    public function serializeShouldReturnCorrectArray(
        string $processUuid,
        string $uuid,
        string $status,
        string $updatedAt,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $statusVo = Status::fromNative($status);
        $updatedAtVo = UpdatedAt::fromNative($updatedAt);
        $event = new ArticleUnpublishedEvent($processUuidVo, $uuidVo, $statusVo, $updatedAtVo);

        // Act
        $serialized = $event->serialize();

        // Assert
        $this->assertArrayHasKey('process_uuid', $serialized);
        $this->assertArrayHasKey('uuid', $serialized);
        $this->assertArrayHasKey('status', $serialized);
        $this->assertArrayHasKey('updated_at', $serialized);
        $this->assertSame($processUuid, $serialized['process_uuid']);
        $this->assertSame($uuid, $serialized['uuid']);
        $this->assertSame($status, $serialized['status']);
    }

    #[Test]
    #[DataProviderExternal(ArticleUnpublishedEventDataProvider::class, 'provideSerializationScenarios')]
    public function deserializeShouldRecreateEvent(
        string $processUuid,
        string $uuid,
        string $status,
        string $updatedAt,
    ): void {
        // Arrange
        $data = [
            'process_uuid' => $processUuid,
            'uuid' => $uuid,
            'status' => $status,
            'updated_at' => $updatedAt,
        ];

        // Act
        $event = ArticleUnpublishedEvent::deserialize($data);

        // Assert
        $this->assertInstanceOf(ArticleUnpublishedEvent::class, $event);
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
        $status = Status::fromNative('draft');
        $updatedAt = UpdatedAt::fromNative('2024-01-15T10:30:00+00:00');
        $event = new ArticleUnpublishedEvent($processUuid, $uuid, $status, $updatedAt);

        // Act
        $result = $event->getStatus();

        // Assert
        $this->assertInstanceOf(Status::class, $result);
        $this->assertSame('draft', $result->toNative());
    }

    #[Test]
    public function getUpdatedAtShouldReturnUpdatedAtValueObject(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $status = Status::fromNative('draft');
        $updatedAt = UpdatedAt::fromNative('2024-01-15T10:30:00+00:00');
        $event = new ArticleUnpublishedEvent($processUuid, $uuid, $status, $updatedAt);

        // Act
        $result = $event->getUpdatedAt();

        // Assert
        $this->assertInstanceOf(UpdatedAt::class, $result);
    }

    #[Test]
    #[DataProviderExternal(ArticleUnpublishedEventDataProvider::class, 'provideWithPayloadScenarios')]
    public function constructWithPayloadShouldIncludePayload(
        string $processUuid,
        string $uuid,
        string $status,
        string $updatedAt,
        array $payload,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $statusVo = Status::fromNative($status);
        $updatedAtVo = UpdatedAt::fromNative($updatedAt);
        $payloadVo = Payload::fromNative($payload);

        // Act
        $event = new ArticleUnpublishedEvent($processUuidVo, $uuidVo, $statusVo, $updatedAtVo, $payloadVo);
        $serialized = $event->serialize();

        // Assert
        $this->assertArrayHasKey('payload', $serialized);
        $this->assertSame($payload, $serialized['payload']);
    }

    #[Test]
    public function serializeDeserializeRoundTripShouldPreserveData(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $status = Status::fromNative('draft');
        $updatedAt = UpdatedAt::fromNative('2024-01-15T10:30:00+00:00');
        $original = new ArticleUnpublishedEvent($processUuid, $uuid, $status, $updatedAt);

        // Act
        $serialized = $original->serialize();
        $restored = ArticleUnpublishedEvent::deserialize($serialized);

        // Assert
        $this->assertSame($original->getProcessUuid()->toNative(), $restored->getProcessUuid()->toNative());
        $this->assertSame($original->getUuid()->toNative(), $restored->getUuid()->toNative());
        $this->assertSame($original->getStatus()->toNative(), $restored->getStatus()->toNative());
    }

    #[Test]
    public function deserializeWithPayloadShouldRestorePayload(): void
    {
        // Arrange
        $payload = ['source' => 'api', 'reason' => 'content review'];
        $data = [
            'process_uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'status' => 'draft',
            'updated_at' => '2024-01-15T10:30:00+00:00',
            'payload' => $payload,
        ];

        // Act
        $event = ArticleUnpublishedEvent::deserialize($data);

        // Assert
        $this->assertInstanceOf(ArticleUnpublishedEvent::class, $event);
        $this->assertNotNull($event->getPayload());
        $this->assertSame($payload, $event->getPayload()->toNative());
    }
}
