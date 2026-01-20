<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\Event;

use Micro\Article\Domain\Event\ArticleArchivedEvent;
use Micro\Article\Domain\ValueObject\ArchivedAt;
use Micro\Article\Domain\ValueObject\Status;
use MicroModule\Base\Domain\ValueObject\Payload;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\UpdatedAt;
use MicroModule\Base\Domain\ValueObject\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\Event\ArticleArchivedEventDataProvider;

/**
 * Unit tests for ArticleArchivedEvent.
 */
#[CoversClass(ArticleArchivedEvent::class)]
final class ArticleArchivedEventTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(ArticleArchivedEventDataProvider::class, 'provideValidConstructionData')]
    public function constructWithValidDataShouldCreateInstance(
        string $processUuid,
        string $uuid,
        string $status,
        string $archivedAt,
        string $updatedAt,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $statusVo = Status::fromNative($status);
        $archivedAtVo = ArchivedAt::fromNative($archivedAt);
        $updatedAtVo = UpdatedAt::fromNative($updatedAt);

        // Act
        $event = new ArticleArchivedEvent($processUuidVo, $uuidVo, $statusVo, $archivedAtVo, $updatedAtVo);

        // Assert
        $this->assertInstanceOf(ArticleArchivedEvent::class, $event);
        $this->assertSame($processUuid, $event->getProcessUuid()->toNative());
        $this->assertSame($uuid, $event->getUuid()->toNative());
        $this->assertSame($status, $event->getStatus()->toNative());
        $this->assertInstanceOf(ArchivedAt::class, $event->getArchivedAt());
        $this->assertInstanceOf(UpdatedAt::class, $event->getUpdatedAt());
    }

    #[Test]
    #[DataProviderExternal(ArticleArchivedEventDataProvider::class, 'provideSerializationScenarios')]
    public function serializeShouldReturnCorrectArray(
        string $processUuid,
        string $uuid,
        string $status,
        string $archivedAt,
        string $updatedAt,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $statusVo = Status::fromNative($status);
        $archivedAtVo = ArchivedAt::fromNative($archivedAt);
        $updatedAtVo = UpdatedAt::fromNative($updatedAt);
        $event = new ArticleArchivedEvent($processUuidVo, $uuidVo, $statusVo, $archivedAtVo, $updatedAtVo);

        // Act
        $serialized = $event->serialize();

        // Assert
        $this->assertArrayHasKey('process_uuid', $serialized);
        $this->assertArrayHasKey('uuid', $serialized);
        $this->assertArrayHasKey('status', $serialized);
        $this->assertArrayHasKey('archived_at', $serialized);
        $this->assertArrayHasKey('updated_at', $serialized);
        $this->assertSame($processUuid, $serialized['process_uuid']);
        $this->assertSame($uuid, $serialized['uuid']);
        $this->assertSame($status, $serialized['status']);
    }

    #[Test]
    #[DataProviderExternal(ArticleArchivedEventDataProvider::class, 'provideSerializationScenarios')]
    public function deserializeShouldRecreateEvent(
        string $processUuid,
        string $uuid,
        string $status,
        string $archivedAt,
        string $updatedAt,
    ): void {
        // Arrange
        $data = [
            'process_uuid' => $processUuid,
            'uuid' => $uuid,
            'status' => $status,
            'archived_at' => $archivedAt,
            'updated_at' => $updatedAt,
        ];

        // Act
        $event = ArticleArchivedEvent::deserialize($data);

        // Assert
        $this->assertInstanceOf(ArticleArchivedEvent::class, $event);
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
        $status = Status::fromNative('archived');
        $archivedAt = ArchivedAt::fromNative('2024-01-15T10:30:00+00:00');
        $updatedAt = UpdatedAt::fromNative('2024-01-15T10:30:00+00:00');
        $event = new ArticleArchivedEvent($processUuid, $uuid, $status, $archivedAt, $updatedAt);

        // Act
        $result = $event->getStatus();

        // Assert
        $this->assertInstanceOf(Status::class, $result);
        $this->assertSame('archived', $result->toNative());
    }

    #[Test]
    public function getArchivedAtShouldReturnArchivedAtValueObject(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $status = Status::fromNative('archived');
        $archivedAt = ArchivedAt::fromNative('2024-01-15T10:30:00+00:00');
        $updatedAt = UpdatedAt::fromNative('2024-01-15T10:30:00+00:00');
        $event = new ArticleArchivedEvent($processUuid, $uuid, $status, $archivedAt, $updatedAt);

        // Act
        $result = $event->getArchivedAt();

        // Assert
        $this->assertInstanceOf(ArchivedAt::class, $result);
    }

    #[Test]
    public function getUpdatedAtShouldReturnUpdatedAtValueObject(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $status = Status::fromNative('archived');
        $archivedAt = ArchivedAt::fromNative('2024-01-15T10:30:00+00:00');
        $updatedAt = UpdatedAt::fromNative('2024-01-15T10:30:00+00:00');
        $event = new ArticleArchivedEvent($processUuid, $uuid, $status, $archivedAt, $updatedAt);

        // Act
        $result = $event->getUpdatedAt();

        // Assert
        $this->assertInstanceOf(UpdatedAt::class, $result);
    }

    #[Test]
    #[DataProviderExternal(ArticleArchivedEventDataProvider::class, 'provideWithPayloadScenarios')]
    public function constructWithPayloadShouldIncludePayload(
        string $processUuid,
        string $uuid,
        string $status,
        string $archivedAt,
        string $updatedAt,
        array $payload,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $statusVo = Status::fromNative($status);
        $archivedAtVo = ArchivedAt::fromNative($archivedAt);
        $updatedAtVo = UpdatedAt::fromNative($updatedAt);
        $payloadVo = Payload::fromNative($payload);

        // Act
        $event = new ArticleArchivedEvent($processUuidVo, $uuidVo, $statusVo, $archivedAtVo, $updatedAtVo, $payloadVo);
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
        $status = Status::fromNative('archived');
        $archivedAt = ArchivedAt::fromNative('2024-01-15T10:30:00+00:00');
        $updatedAt = UpdatedAt::fromNative('2024-01-15T10:30:00+00:00');
        $original = new ArticleArchivedEvent($processUuid, $uuid, $status, $archivedAt, $updatedAt);

        // Act
        $serialized = $original->serialize();
        $restored = ArticleArchivedEvent::deserialize($serialized);

        // Assert
        $this->assertSame($original->getProcessUuid()->toNative(), $restored->getProcessUuid()->toNative());
        $this->assertSame($original->getUuid()->toNative(), $restored->getUuid()->toNative());
        $this->assertSame($original->getStatus()->toNative(), $restored->getStatus()->toNative());
    }

    #[Test]
    public function deserializeWithPayloadShouldRestorePayload(): void
    {
        // Arrange
        $payload = ['source' => 'api', 'archived_by' => 'admin'];
        $data = [
            'process_uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'status' => 'archived',
            'archived_at' => '2024-01-15T10:30:00+00:00',
            'updated_at' => '2024-01-15T10:30:00+00:00',
            'payload' => $payload,
        ];

        // Act
        $event = ArticleArchivedEvent::deserialize($data);

        // Assert
        $this->assertInstanceOf(ArticleArchivedEvent::class, $event);
        $this->assertNotNull($event->getPayload());
        $this->assertSame($payload, $event->getPayload()->toNative());
    }
}
