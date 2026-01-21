<?php

declare(strict_types=1);

namespace Tests\Integration\Common\Infrastructure\Outbox;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventStore\EventStore;
use Broadway\Serializer\Serializable;
use Broadway\Serializer\SimpleInterfaceSerializer;
use Micro\Component\Common\Domain\Outbox\OutboxMessageType;
use Micro\Component\Common\Domain\Outbox\OutboxRepositoryInterface;
use Micro\Component\Common\Infrastructure\Outbox\BroadwayDomainEventSerializer;
use Micro\Component\Common\Infrastructure\Outbox\DbalOutboxRepository;
use Micro\Component\Common\Infrastructure\Outbox\Metrics\OutboxMetricsInterface;
use Micro\Component\Common\Infrastructure\Outbox\OutboxAwareEventStore;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use Ramsey\Uuid\Uuid;
use Tests\Integration\IntegrationTestCase;

/**
 * Integration tests for OutboxAwareEventStore.
 *
 * Tests the event store decorator's ability to create outbox entries
 * when events are appended to the event store.
 *
 * Uses a real database for outbox persistence with mocked inner EventStore.
 *
 * @see OutboxAwareEventStore
 * @see ADR-006: Transactional Outbox Pattern
 * @see TASK-14.2: EventStore Decorator
 */
#[CoversClass(OutboxAwareEventStore::class)]
#[Group('integration')]
#[Group('eventstore')]
#[Group('outbox')]
final class OutboxAwareEventStoreIntegrationTest extends IntegrationTestCase
{
    private const TABLE_NAME = 'outbox';

    private EventStore&MockInterface $innerEventStore;
    private OutboxRepositoryInterface $outboxRepository;
    private OutboxAwareEventStore $decorator;
    private BroadwayDomainEventSerializer $serializer;

    protected function setUp(): void
    {
        parent::setUp();

        // Get real outbox repository from container
        $this->outboxRepository = $this->getService(OutboxRepositoryInterface::class);

        // Create mock inner event store
        $this->innerEventStore = Mockery::mock(EventStore::class);

        // Create serializers using Broadway's SimpleInterfaceSerializer
        $payloadSerializer = new SimpleInterfaceSerializer();
        $metadataSerializer = new SimpleInterfaceSerializer();

        $this->serializer = new BroadwayDomainEventSerializer(
            $payloadSerializer,
            $metadataSerializer,
        );

        // Create mock metrics
        $metrics = Mockery::mock(OutboxMetricsInterface::class);
        $metrics->shouldReceive('recordEventStored')->andReturnNull()->byDefault();
        $metrics->shouldReceive('recordOutboxCreation')->andReturnNull()->byDefault();
        $metrics->shouldReceive('recordMessageEnqueued')->andReturnNull()->byDefault();

        // Create the decorator under test
        $this->decorator = new OutboxAwareEventStore(
            $this->innerEventStore,
            $this->outboxRepository,
            $this->serializer,
            $metrics,
            new NullLogger(),
        );

        // Clear outbox table before each test for isolation
        $this->executeStatement('DELETE FROM ' . self::TABLE_NAME);
    }

    // =========================================================================
    // append() with Outbox Entry Creation Tests
    // =========================================================================

    #[Test]
    public function appendShouldDelegateToInnerStoreAndCreateOutboxEntry(): void
    {
        // Arrange
        $aggregateId = Uuid::uuid4()->toString();
        $eventStream = $this->createEventStream($aggregateId, [
            new TestDomainEvent('event-1', 'Test Event 1'),
        ]);

        // Inner store should receive the append call
        $this->innerEventStore
            ->shouldReceive('append')
            ->once()
            ->with($aggregateId, $eventStream);

        // Act
        $this->decorator->append($aggregateId, $eventStream);

        // Assert - outbox entry should be created
        $this->assertDatabaseHas(self::TABLE_NAME, [
            'aggregate_id' => $aggregateId,
            'message_type' => OutboxMessageType::EVENT->value,
        ]);

        // Verify entry count
        $entries = $this->executeQuery(
            'SELECT * FROM ' . self::TABLE_NAME . ' WHERE aggregate_id = :id',
            ['id' => $aggregateId]
        );

        self::assertCount(1, $entries);
        self::assertStringContainsString('TestDomainEvent', $entries[0]['event_type']);
    }

    #[Test]
    public function appendShouldCreateMultipleOutboxEntriesForMultipleEvents(): void
    {
        // Arrange
        $aggregateId = Uuid::uuid4()->toString();
        $eventStream = $this->createEventStream($aggregateId, [
            new TestDomainEvent('event-1', 'First Event'),
            new TestDomainEvent('event-2', 'Second Event'),
            new TestDomainEvent('event-3', 'Third Event'),
        ]);

        $this->innerEventStore
            ->shouldReceive('append')
            ->once()
            ->with($aggregateId, $eventStream);

        // Act
        $this->decorator->append($aggregateId, $eventStream);

        // Assert
        $entries = $this->executeQuery(
            'SELECT * FROM ' . self::TABLE_NAME . ' WHERE aggregate_id = :id ORDER BY sequence_number',
            ['id' => $aggregateId]
        );

        self::assertCount(3, $entries);

        foreach ($entries as $entry) {
            self::assertSame(OutboxMessageType::EVENT->value, $entry['message_type']);
            // Pending status is determined by published_at being NULL
            self::assertNull($entry['published_at']);
        }
    }

    #[Test]
    public function appendShouldSetCorrectOutboxEntryFields(): void
    {
        // Arrange
        $aggregateId = Uuid::uuid4()->toString();
        $eventStream = $this->createEventStream($aggregateId, [
            new TestDomainEvent('test-id', 'Test Name'),
        ]);

        $this->innerEventStore
            ->shouldReceive('append')
            ->once();

        // Act
        $this->decorator->append($aggregateId, $eventStream);

        // Assert
        $entries = $this->executeQuery(
            'SELECT * FROM ' . self::TABLE_NAME . ' WHERE aggregate_id = :id',
            ['id' => $aggregateId]
        );

        self::assertCount(1, $entries);
        $entry = $entries[0];

        // Verify all required fields
        self::assertNotEmpty($entry['id']);
        self::assertSame($aggregateId, $entry['aggregate_id']);
        self::assertSame(OutboxMessageType::EVENT->value, $entry['message_type']);
        self::assertSame('Tests\\Integration\\Common\\Infrastructure\\Outbox\\TestDomainEvent', $entry['event_type']);
        self::assertNotEmpty($entry['event_payload']);
        // Pending status is determined by published_at being NULL
        self::assertNull($entry['published_at']);
        self::assertNotNull($entry['created_at']);

        // Verify payload is valid JSON
        $payload = json_decode($entry['event_payload'], true);
        self::assertIsArray($payload);
        self::assertArrayHasKey('payload', $payload);
    }

    #[Test]
    public function appendShouldExtractCorrectTopicAndRoutingKey(): void
    {
        // Arrange
        $aggregateId = Uuid::uuid4()->toString();
        $eventStream = $this->createEventStream($aggregateId, [
            new TestDomainEvent('id', 'name'),
        ]);

        $this->innerEventStore
            ->shouldReceive('append')
            ->once();

        // Act
        $this->decorator->append($aggregateId, $eventStream);

        // Assert
        $entries = $this->executeQuery(
            'SELECT topic, routing_key FROM ' . self::TABLE_NAME . ' WHERE aggregate_id = :id',
            ['id' => $aggregateId]
        );

        self::assertCount(1, $entries);

        // Topic should be based on namespace: Tests\Integration\... -> events.tests
        self::assertStringStartsWith('events.', $entries[0]['topic']);

        // Routing key should be snake_case version of event class
        self::assertStringStartsWith('event.', $entries[0]['routing_key']);
        self::assertStringContainsString('test_domain_event', $entries[0]['routing_key']);
    }

    // =========================================================================
    // Enable/Disable Tests
    // =========================================================================

    #[Test]
    public function appendShouldNotCreateOutboxEntriesWhenDisabled(): void
    {
        // Arrange
        $aggregateId = Uuid::uuid4()->toString();
        $eventStream = $this->createEventStream($aggregateId, [
            new TestDomainEvent('event-1', 'Event'),
        ]);

        $this->innerEventStore
            ->shouldReceive('append')
            ->once();

        // Disable outbox
        $this->decorator->disable();

        // Act
        $this->decorator->append($aggregateId, $eventStream);

        // Assert - no outbox entry should be created
        $this->assertDatabaseMissing(self::TABLE_NAME, [
            'aggregate_id' => $aggregateId,
        ]);
    }

    #[Test]
    public function appendShouldCreateOutboxEntriesAfterReEnable(): void
    {
        // Arrange
        $aggregateId = Uuid::uuid4()->toString();
        $eventStream = $this->createEventStream($aggregateId, [
            new TestDomainEvent('event-1', 'Event'),
        ]);

        $this->innerEventStore
            ->shouldReceive('append')
            ->once();

        // Disable, then re-enable
        $this->decorator->disable();
        $this->decorator->enable();

        // Act
        $this->decorator->append($aggregateId, $eventStream);

        // Assert - outbox entry should be created
        $this->assertDatabaseHas(self::TABLE_NAME, [
            'aggregate_id' => $aggregateId,
        ]);
    }

    #[Test]
    public function isEnabledShouldReturnCorrectState(): void
    {
        // Assert - enabled by default
        self::assertTrue($this->decorator->isEnabled());

        // Act & Assert - disabled
        $this->decorator->disable();
        self::assertFalse($this->decorator->isEnabled());

        // Act & Assert - re-enabled
        $this->decorator->enable();
        self::assertTrue($this->decorator->isEnabled());
    }

    // =========================================================================
    // load() Delegation Tests
    // =========================================================================

    #[Test]
    public function loadShouldDelegateToInnerStore(): void
    {
        // Arrange
        $aggregateId = Uuid::uuid4()->toString();
        $expectedStream = $this->createEventStream($aggregateId, [
            new TestDomainEvent('event-1', 'Event'),
        ]);

        $this->innerEventStore
            ->shouldReceive('load')
            ->once()
            ->with($aggregateId)
            ->andReturn($expectedStream);

        // Act
        $result = $this->decorator->load($aggregateId);

        // Assert
        self::assertSame($expectedStream, $result);
    }

    #[Test]
    public function loadFromPlayheadShouldDelegateToInnerStore(): void
    {
        // Arrange
        $aggregateId = Uuid::uuid4()->toString();
        $playhead = 5;
        $expectedStream = $this->createEventStream($aggregateId, [
            new TestDomainEvent('event-6', 'Event 6'),
        ], $playhead);

        $this->innerEventStore
            ->shouldReceive('loadFromPlayhead')
            ->once()
            ->with($aggregateId, $playhead)
            ->andReturn($expectedStream);

        // Act
        $result = $this->decorator->loadFromPlayhead($aggregateId, $playhead);

        // Assert
        self::assertSame($expectedStream, $result);
    }

    // =========================================================================
    // Error Handling Tests
    // =========================================================================

    #[Test]
    public function appendShouldPropagateInnerStoreExceptions(): void
    {
        // Arrange
        $aggregateId = Uuid::uuid4()->toString();
        $eventStream = $this->createEventStream($aggregateId, [
            new TestDomainEvent('event-1', 'Event'),
        ]);

        $expectedException = new \RuntimeException('Inner store error');

        $this->innerEventStore
            ->shouldReceive('append')
            ->once()
            ->andThrow($expectedException);

        // Expect
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Inner store error');

        // Act
        $this->decorator->append($aggregateId, $eventStream);
    }

    #[Test]
    public function appendShouldNotCreateOutboxEntriesIfInnerStoreFails(): void
    {
        // Arrange
        $aggregateId = Uuid::uuid4()->toString();
        $eventStream = $this->createEventStream($aggregateId, [
            new TestDomainEvent('event-1', 'Event'),
        ]);

        $this->innerEventStore
            ->shouldReceive('append')
            ->once()
            ->andThrow(new \RuntimeException('Store failed'));

        // Act - expect exception but verify no outbox entry
        try {
            $this->decorator->append($aggregateId, $eventStream);
        } catch (\RuntimeException) {
            // Expected
        }

        // Assert - no outbox entry should be created due to transaction rollback
        // Note: In real scenario with transaction, outbox would be rolled back too
        $this->assertDatabaseMissing(self::TABLE_NAME, [
            'aggregate_id' => $aggregateId,
        ]);
    }

    // =========================================================================
    // Aggregate Type Extraction Tests
    // =========================================================================

    #[Test]
    public function appendShouldExtractAggregateTypeFromMetadata(): void
    {
        // Arrange
        $aggregateId = Uuid::uuid4()->toString();
        $metadata = new Metadata(['aggregate_type' => 'Article']);
        $eventStream = $this->createEventStreamWithMetadata($aggregateId, [
            new TestDomainEvent('event-1', 'Event'),
        ], $metadata);

        $this->innerEventStore
            ->shouldReceive('append')
            ->once();

        // Act
        $this->decorator->append($aggregateId, $eventStream);

        // Assert
        $this->assertDatabaseHas(self::TABLE_NAME, [
            'aggregate_id' => $aggregateId,
            'aggregate_type' => 'Article',
        ]);
    }

    #[Test]
    public function appendShouldExtractAggregateTypeFromEventNamespaceWhenMetadataMissing(): void
    {
        // Arrange
        $aggregateId = Uuid::uuid4()->toString();
        $eventStream = $this->createEventStream($aggregateId, [
            new TestDomainEvent('event-1', 'Event'),
        ]);

        $this->innerEventStore
            ->shouldReceive('append')
            ->once();

        // Act
        $this->decorator->append($aggregateId, $eventStream);

        // Assert - should use first namespace part: Tests\Integration\... -> Tests
        $entries = $this->executeQuery(
            'SELECT aggregate_type FROM ' . self::TABLE_NAME . ' WHERE aggregate_id = :id',
            ['id' => $aggregateId]
        );

        self::assertSame('Tests', $entries[0]['aggregate_type']);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Create a DomainEventStream with the given events.
     *
     * @param Serializable[] $events
     */
    private function createEventStream(string $aggregateId, array $events, int $startPlayhead = 0): DomainEventStream
    {
        return $this->createEventStreamWithMetadata($aggregateId, $events, new Metadata([]), $startPlayhead);
    }

    /**
     * Create a DomainEventStream with the given events and metadata.
     *
     * @param Serializable[] $events
     */
    private function createEventStreamWithMetadata(
        string $aggregateId,
        array $events,
        Metadata $metadata,
        int $startPlayhead = 0,
    ): DomainEventStream {
        $messages = [];

        foreach ($events as $index => $event) {
            $messages[] = new DomainMessage(
                $aggregateId,
                $startPlayhead + $index,
                $metadata,
                $event,
                DateTime::now(),
            );
        }

        return new DomainEventStream($messages);
    }
}

/**
 * Test domain event for integration tests.
 */
final class TestDomainEvent implements Serializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
    ) {
    }

    /**
     * @param array{id: string, name: string} $data
     */
    public static function deserialize(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
        );
    }

    /**
     * @return array{id: string, name: string}
     */
    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
