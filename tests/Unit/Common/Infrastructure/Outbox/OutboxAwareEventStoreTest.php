<?php

declare(strict_types=1);

namespace Tests\Unit\Common\Infrastructure\Outbox;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventStore\EventStore;
use Micro\Component\Common\Domain\Outbox\DomainEventSerializerInterface;
use Micro\Component\Common\Domain\Outbox\OutboxRepositoryInterface;
use Micro\Component\Common\Infrastructure\Outbox\Metrics\OutboxMetricsInterface;
use Micro\Component\Common\Infrastructure\Outbox\OutboxAwareEventStore;
use Micro\Component\Common\Infrastructure\Outbox\OutboxEntry;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for OutboxAwareEventStore decorator.
 *
 * Tests the decorator pattern that intercepts event storage
 * and creates outbox entries within the same transaction.
 */
#[CoversClass(OutboxAwareEventStore::class)]
final class OutboxAwareEventStoreTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private EventStore&MockInterface $innerStore;
    private OutboxRepositoryInterface&MockInterface $outboxRepository;
    private DomainEventSerializerInterface&MockInterface $serializer;
    private OutboxMetricsInterface&MockInterface $metrics;
    private LoggerInterface&MockInterface $logger;
    private OutboxAwareEventStore $eventStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->innerStore = Mockery::mock(EventStore::class);
        $this->outboxRepository = Mockery::mock(OutboxRepositoryInterface::class);
        $this->serializer = Mockery::mock(DomainEventSerializerInterface::class);

        // Metrics mock with lenient expectations
        $this->metrics = Mockery::mock(OutboxMetricsInterface::class);
        $this->metrics->shouldReceive('recordEventStored')->andReturnNull()->byDefault();
        $this->metrics->shouldReceive('recordOutboxCreation')->andReturnNull()->byDefault();
        $this->metrics->shouldReceive('recordMessageEnqueued')->andReturnNull()->byDefault();

        // Logger mock with lenient expectations
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->logger->shouldReceive('info')->andReturnNull()->byDefault();
        $this->logger->shouldReceive('warning')->andReturnNull()->byDefault();
        $this->logger->shouldReceive('error')->andReturnNull()->byDefault();

        $this->eventStore = new OutboxAwareEventStore(
            $this->innerStore,
            $this->outboxRepository,
            $this->serializer,
            $this->metrics,
            $this->logger,
        );
    }

    // ========================================================================
    // Constructor and Default State Tests
    // ========================================================================

    #[Test]
    public function isEnabledByDefault(): void
    {
        self::assertTrue($this->eventStore->isEnabled());
    }

    // ========================================================================
    // enable() / disable() / isEnabled() Tests
    // ========================================================================

    #[Test]
    public function disableSetsEnabledToFalse(): void
    {
        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Outbox event store decorator disabled');

        $this->eventStore->disable();

        self::assertFalse($this->eventStore->isEnabled());
    }

    #[Test]
    public function enableSetsEnabledToTrue(): void
    {
        // First disable it
        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Outbox event store decorator disabled');
        $this->eventStore->disable();

        // Then enable it
        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Outbox event store decorator enabled');
        $this->eventStore->enable();

        self::assertTrue($this->eventStore->isEnabled());
    }

    // ========================================================================
    // load() Tests - Delegates to Inner Store
    // ========================================================================

    #[Test]
    public function loadDelegatesToInnerStore(): void
    {
        $aggregateId = 'aggregate-123';
        $expectedStream = $this->createEmptyEventStream();

        $this->innerStore
            ->shouldReceive('load')
            ->once()
            ->with($aggregateId)
            ->andReturn($expectedStream);

        $result = $this->eventStore->load($aggregateId);

        self::assertSame($expectedStream, $result);
    }

    // ========================================================================
    // loadFromPlayhead() Tests - Delegates to Inner Store
    // ========================================================================

    #[Test]
    public function loadFromPlayheadDelegatesToInnerStore(): void
    {
        $aggregateId = 'aggregate-123';
        $playhead = 5;
        $expectedStream = $this->createEmptyEventStream();

        $this->innerStore
            ->shouldReceive('loadFromPlayhead')
            ->once()
            ->with($aggregateId, $playhead)
            ->andReturn($expectedStream);

        $result = $this->eventStore->loadFromPlayhead($aggregateId, $playhead);

        self::assertSame($expectedStream, $result);
    }

    // ========================================================================
    // append() Tests - Core Functionality
    // ========================================================================

    #[Test]
    public function appendDelegatesToInnerStoreFirst(): void
    {
        $aggregateId = 'aggregate-123';
        $eventStream = $this->createEmptyEventStream();

        // Inner store should be called
        $this->innerStore
            ->shouldReceive('append')
            ->once()
            ->with($aggregateId, $eventStream);

        $this->eventStore->append($aggregateId, $eventStream);
    }

    #[Test]
    public function appendWithEmptyStreamDoesNotSaveToOutbox(): void
    {
        $aggregateId = 'aggregate-123';
        $eventStream = $this->createEmptyEventStream();

        $this->innerStore
            ->shouldReceive('append')
            ->once()
            ->with($aggregateId, $eventStream);

        // Outbox repository should NOT be called for empty streams
        $this->outboxRepository
            ->shouldNotReceive('saveAll');

        $this->eventStore->append($aggregateId, $eventStream);
    }

    #[Test]
    public function appendCreatesOutboxEntriesForEachEvent(): void
    {
        $aggregateId = 'aggregate-123';
        $event1 = $this->createMockEvent();
        $event2 = $this->createMockEvent();

        $message1 = $this->createDomainMessage($aggregateId, 0, $event1);
        $message2 = $this->createDomainMessage($aggregateId, 1, $event2);
        $eventStream = new DomainEventStream([$message1, $message2]);

        $this->innerStore
            ->shouldReceive('append')
            ->once()
            ->with($aggregateId, $eventStream);

        // Serializer called for each message
        $this->serializer
            ->shouldReceive('extractEventType')
            ->twice()
            ->andReturn('TestEvent');

        $this->serializer
            ->shouldReceive('serialize')
            ->twice()
            ->andReturn(['event' => 'data']);

        $this->serializer
            ->shouldReceive('determineTopicName')
            ->twice()
            ->andReturn('events.test');

        $this->serializer
            ->shouldReceive('determineRoutingKey')
            ->twice()
            ->andReturn('event.test');

        // Outbox repository should save 2 entries
        $this->outboxRepository
            ->shouldReceive('saveAll')
            ->once()
            ->with(Mockery::on(function (array $entries) {
                return count($entries) === 2;
            }));

        $this->logger
            ->shouldReceive('debug')
            ->once()
            ->with('Created outbox entries for domain events', Mockery::any());

        $this->eventStore->append($aggregateId, $eventStream);
    }

    #[Test]
    public function appendSkipsOutboxWhenDisabled(): void
    {
        $aggregateId = 'aggregate-123';
        $event = $this->createMockEvent();
        $message = $this->createDomainMessage($aggregateId, 0, $event);
        $eventStream = new DomainEventStream([$message]);

        // Disable the outbox
        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Outbox event store decorator disabled');
        $this->eventStore->disable();

        // Inner store still called
        $this->innerStore
            ->shouldReceive('append')
            ->once()
            ->with($aggregateId, $eventStream);

        // Debug log for skipped outbox
        $this->logger
            ->shouldReceive('debug')
            ->once()
            ->with('Outbox disabled, skipping outbox entry creation', Mockery::any());

        // Serializer should NOT be called
        $this->serializer->shouldNotReceive('extractEventType');
        $this->serializer->shouldNotReceive('serialize');

        // Outbox repository should NOT be called
        $this->outboxRepository->shouldNotReceive('saveAll');

        $this->eventStore->append($aggregateId, $eventStream);
    }

    #[Test]
    public function appendExtractsAggregateTypeFromMetadata(): void
    {
        $aggregateId = 'aggregate-123';
        $event = $this->createMockEvent();

        // Create metadata with aggregate_type
        $metadata = new Metadata(['aggregate_type' => 'Article']);
        $message = DomainMessage::recordNow($aggregateId, 0, $metadata, $event);
        $eventStream = new DomainEventStream([$message]);

        $this->innerStore
            ->shouldReceive('append')
            ->once();

        $this->serializer
            ->shouldReceive('extractEventType')
            ->once()
            ->andReturn('TestEvent');

        $this->serializer
            ->shouldReceive('serialize')
            ->once()
            ->andReturn(['event' => 'data']);

        $this->serializer
            ->shouldReceive('determineTopicName')
            ->once()
            ->andReturn('events.test');

        $this->serializer
            ->shouldReceive('determineRoutingKey')
            ->once()
            ->andReturn('event.test');

        // Capture the saved entry to verify aggregate type
        $this->outboxRepository
            ->shouldReceive('saveAll')
            ->once()
            ->with(Mockery::on(function (array $entries) {
                /** @var OutboxEntry $entry */
                $entry = $entries[0];
                // Verify the aggregate type was extracted from metadata
                $data = $entry->toArray();

                return $data['aggregate_type'] === 'Article';
            }));

        $this->logger
            ->shouldReceive('debug')
            ->once();

        $this->eventStore->append($aggregateId, $eventStream);
    }

    #[Test]
    public function appendExtractsAggregateTypeFromEventNamespace(): void
    {
        $aggregateId = 'aggregate-123';
        // Use a namespaced event class
        $event = new TestDomainEvent('test-data');
        $message = $this->createDomainMessage($aggregateId, 0, $event);
        $eventStream = new DomainEventStream([$message]);

        $this->innerStore
            ->shouldReceive('append')
            ->once();

        $this->serializer
            ->shouldReceive('extractEventType')
            ->once()
            ->andReturn('TestDomainEvent');

        $this->serializer
            ->shouldReceive('serialize')
            ->once()
            ->andReturn(['event' => 'data']);

        $this->serializer
            ->shouldReceive('determineTopicName')
            ->once()
            ->andReturn('events.test');

        $this->serializer
            ->shouldReceive('determineRoutingKey')
            ->once()
            ->andReturn('event.test');

        // Capture the saved entry to verify aggregate type is extracted from namespace
        $this->outboxRepository
            ->shouldReceive('saveAll')
            ->once()
            ->with(Mockery::on(function (array $entries) {
                /** @var OutboxEntry $entry */
                $entry = $entries[0];
                $data = $entry->toArray();
                // First part of namespace: Tests\Unit\Common\Infrastructure\Outbox -> Tests

                return $data['aggregate_type'] === 'Tests';
            }));

        $this->logger
            ->shouldReceive('debug')
            ->once();

        $this->eventStore->append($aggregateId, $eventStream);
    }

    #[Test]
    public function appendWithSingleEvent(): void
    {
        $aggregateId = 'article-456';
        $event = $this->createMockEvent();
        $message = $this->createDomainMessage($aggregateId, 0, $event);
        $eventStream = new DomainEventStream([$message]);

        $this->innerStore
            ->shouldReceive('append')
            ->once()
            ->with($aggregateId, $eventStream);

        $this->serializer
            ->shouldReceive('extractEventType')
            ->once()
            ->andReturn('ArticleCreated');

        $this->serializer
            ->shouldReceive('serialize')
            ->once()
            ->andReturn([
                'id' => 'event-id',
                'payload' => ['title' => 'Test Title'],
            ]);

        $this->serializer
            ->shouldReceive('determineTopicName')
            ->once()
            ->andReturn('events.article');

        $this->serializer
            ->shouldReceive('determineRoutingKey')
            ->once()
            ->andReturn('event.article.created');

        $this->outboxRepository
            ->shouldReceive('saveAll')
            ->once()
            ->with(Mockery::on(function (array $entries) {
                return count($entries) === 1;
            }));

        $this->logger
            ->shouldReceive('debug')
            ->once()
            ->with('Created outbox entries for domain events', Mockery::on(function (array $context) {
                return $context['entry_count'] === 1
                    && $context['aggregate_id'] === 'article-456';
            }));

        $this->eventStore->append($aggregateId, $eventStream);
    }

    #[Test]
    public function appendWithMultipleEventsPreservesOrder(): void
    {
        $aggregateId = 'article-789';
        $events = [];
        $messages = [];

        for ($i = 0; $i < 5; ++$i) {
            $events[$i] = $this->createMockEvent();
            $messages[$i] = $this->createDomainMessage($aggregateId, $i, $events[$i]);
        }

        $eventStream = new DomainEventStream($messages);

        $this->innerStore
            ->shouldReceive('append')
            ->once()
            ->with($aggregateId, $eventStream);

        $this->serializer
            ->shouldReceive('extractEventType')
            ->times(5)
            ->andReturn('TestEvent');

        $this->serializer
            ->shouldReceive('serialize')
            ->times(5)
            ->andReturn(['data' => 'test']);

        $this->serializer
            ->shouldReceive('determineTopicName')
            ->times(5)
            ->andReturn('events.test');

        $this->serializer
            ->shouldReceive('determineRoutingKey')
            ->times(5)
            ->andReturn('event.test');

        // Verify order is preserved by checking sequence numbers in the entries
        $this->outboxRepository
            ->shouldReceive('saveAll')
            ->once()
            ->with(Mockery::on(function (array $entries) {
                if (count($entries) !== 5) {
                    return false;
                }

                // Verify sequence numbers match playhead order (0, 1, 2, 3, 4)
                for ($i = 0; $i < 5; ++$i) {
                    $data = $entries[$i]->toArray();
                    if ($data['sequence_number'] !== $i) {
                        return false;
                    }
                }

                return true;
            }));

        $this->logger
            ->shouldReceive('debug')
            ->once()
            ->with('Created outbox entries for domain events', Mockery::on(function (array $context) {
                return $context['entry_count'] === 5;
            }));

        $this->eventStore->append($aggregateId, $eventStream);
    }

    // ========================================================================
    // Error Handling Tests
    // ========================================================================

    #[Test]
    public function appendPropagatesInnerStoreException(): void
    {
        $aggregateId = 'aggregate-123';
        $eventStream = $this->createEmptyEventStream();

        $this->innerStore
            ->shouldReceive('append')
            ->once()
            ->andThrow(new \RuntimeException('Inner store failed'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Inner store failed');

        $this->eventStore->append($aggregateId, $eventStream);
    }

    #[Test]
    public function appendPropagatesOutboxRepositoryException(): void
    {
        $aggregateId = 'aggregate-123';
        $event = $this->createMockEvent();
        $message = $this->createDomainMessage($aggregateId, 0, $event);
        $eventStream = new DomainEventStream([$message]);

        $this->innerStore
            ->shouldReceive('append')
            ->once();

        $this->serializer
            ->shouldReceive('extractEventType')
            ->once()
            ->andReturn('TestEvent');

        $this->serializer
            ->shouldReceive('serialize')
            ->once()
            ->andReturn(['data' => 'test']);

        $this->serializer
            ->shouldReceive('determineTopicName')
            ->once()
            ->andReturn('events.test');

        $this->serializer
            ->shouldReceive('determineRoutingKey')
            ->once()
            ->andReturn('event.test');

        $this->outboxRepository
            ->shouldReceive('saveAll')
            ->once()
            ->andThrow(new \RuntimeException('Outbox save failed'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Outbox save failed');

        $this->eventStore->append($aggregateId, $eventStream);
    }

    #[Test]
    public function appendPropagatesSerializerException(): void
    {
        $aggregateId = 'aggregate-123';
        $event = $this->createMockEvent();
        $message = $this->createDomainMessage($aggregateId, 0, $event);
        $eventStream = new DomainEventStream([$message]);

        $this->innerStore
            ->shouldReceive('append')
            ->once();

        $this->serializer
            ->shouldReceive('extractEventType')
            ->once()
            ->andThrow(new \RuntimeException('Serialization failed'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Serialization failed');

        $this->eventStore->append($aggregateId, $eventStream);
    }

    // ========================================================================
    // Helper Methods
    // ========================================================================

    private function createEmptyEventStream(): DomainEventStream
    {
        return new DomainEventStream([]);
    }

    private function createMockEvent(): object
    {
        return new class {
            public function getData(): string
            {
                return 'test-data';
            }
        };
    }

    private function createDomainMessage(string $id, int $playhead, object $event): DomainMessage
    {
        return DomainMessage::recordNow($id, $playhead, new Metadata([]), $event);
    }
}

/**
 * Test domain event class for namespace extraction tests.
 */
class TestDomainEvent
{
    public function __construct(
        public readonly string $data,
    ) {
    }
}
