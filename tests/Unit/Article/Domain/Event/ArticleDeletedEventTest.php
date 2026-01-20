<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\Event;

use Micro\Article\Domain\Event\ArticleDeletedEvent;
use MicroModule\Base\Domain\ValueObject\Payload;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\Event\ArticleDeletedEventDataProvider;

/**
 * Unit tests for ArticleDeletedEvent.
 */
#[CoversClass(ArticleDeletedEvent::class)]
final class ArticleDeletedEventTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(ArticleDeletedEventDataProvider::class, 'provideValidConstructionData')]
    public function constructWithValidDataShouldCreateInstance(string $processUuid, string $uuid): void
    {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);

        // Act
        $event = new ArticleDeletedEvent($processUuidVo, $uuidVo);

        // Assert
        $this->assertInstanceOf(ArticleDeletedEvent::class, $event);
        $this->assertSame($processUuid, $event->getProcessUuid()->toNative());
        $this->assertSame($uuid, $event->getUuid()->toNative());
    }

    #[Test]
    #[DataProviderExternal(ArticleDeletedEventDataProvider::class, 'provideSerializationScenarios')]
    public function serializeShouldReturnCorrectArray(string $processUuid, string $uuid): void
    {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $event = new ArticleDeletedEvent($processUuidVo, $uuidVo);

        // Act
        $serialized = $event->serialize();

        // Assert
        $this->assertArrayHasKey('process_uuid', $serialized);
        $this->assertArrayHasKey('uuid', $serialized);
        $this->assertSame($processUuid, $serialized['process_uuid']);
        $this->assertSame($uuid, $serialized['uuid']);
    }

    #[Test]
    #[DataProviderExternal(ArticleDeletedEventDataProvider::class, 'provideSerializationScenarios')]
    public function deserializeShouldRecreateEvent(string $processUuid, string $uuid): void
    {
        // Arrange
        $data = [
            'process_uuid' => $processUuid,
            'uuid' => $uuid,
        ];

        // Act
        $event = ArticleDeletedEvent::deserialize($data);

        // Assert
        $this->assertInstanceOf(ArticleDeletedEvent::class, $event);
        $this->assertSame($processUuid, $event->getProcessUuid()->toNative());
        $this->assertSame($uuid, $event->getUuid()->toNative());
    }

    #[Test]
    #[DataProviderExternal(ArticleDeletedEventDataProvider::class, 'provideWithPayloadScenarios')]
    public function constructWithPayloadShouldIncludePayload(
        string $processUuid,
        string $uuid,
        array $payload,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $payloadVo = Payload::fromNative($payload);

        // Act
        $event = new ArticleDeletedEvent($processUuidVo, $uuidVo, $payloadVo);
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
        $original = new ArticleDeletedEvent($processUuid, $uuid);

        // Act
        $serialized = $original->serialize();
        $restored = ArticleDeletedEvent::deserialize($serialized);

        // Assert
        $this->assertSame($original->getProcessUuid()->toNative(), $restored->getProcessUuid()->toNative());
        $this->assertSame($original->getUuid()->toNative(), $restored->getUuid()->toNative());
    }

    #[Test]
    public function deserializeWithPayloadShouldRestorePayload(): void
    {
        // Arrange
        $data = [
            'process_uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'payload' => [
                'deleted_by' => 'admin',
            ],
        ];

        // Act
        $event = ArticleDeletedEvent::deserialize($data);
        $serialized = $event->serialize();

        // Assert
        $this->assertArrayHasKey('payload', $serialized);
        $this->assertSame([
            'deleted_by' => 'admin',
        ], $serialized['payload']);
    }
}
