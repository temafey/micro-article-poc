<?php

declare(strict_types=1);

namespace Tests\Integration\Common\Infrastructure\Outbox;

use Broadway\Serializer\Serializable;
use Enqueue\Client\ProducerInterface;
use Micro\Component\Common\Domain\Outbox\OutboxMessageType;
use Micro\Component\Common\Infrastructure\Observability\Service\TracerFactoryInterface;
use Micro\Component\Common\Infrastructure\Outbox\OutboxEntry;
use Micro\Component\Common\Infrastructure\Outbox\Publisher\EventPublisher;
use Micro\Component\Common\Infrastructure\Outbox\Publisher\OutboxPublishException;
use Micro\Component\Common\Infrastructure\Outbox\Publisher\OutboxPublisher;
use Micro\Component\Common\Infrastructure\Outbox\Publisher\OutboxPublisherInterface;
use Micro\Component\Common\Infrastructure\Outbox\Publisher\TaskPublisher;
use MicroModule\EventQueue\Domain\EventHandling\QueueEventInterface;
use Mockery;
use Mockery\MockInterface;
use OpenTelemetry\API\Trace\NoopTracer;
use OpenTelemetry\API\Trace\TracerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use Tests\Integration\IntegrationTestCase;

/**
 * Integration tests for Outbox Publishing components.
 *
 * Tests the publishing pipeline with mocked message brokers:
 * - OutboxPublisher routing behavior
 * - EventPublisher with mocked QueueEventInterface
 * - TaskPublisher with mocked ProducerInterface
 *
 * @see OutboxPublisher
 * @see EventPublisher
 * @see TaskPublisher
 * @see ADR-006: Transactional Outbox Pattern
 * @see TASK-14.4: Background Publisher
 */
#[CoversClass(OutboxPublisher::class)]
#[CoversClass(EventPublisher::class)]
#[CoversClass(TaskPublisher::class)]
#[Group('integration')]
#[Group('outbox')]
#[Group('publisher')]
final class OutboxPublishingIntegrationTest extends IntegrationTestCase
{
    protected bool $useTransaction = false;

    private QueueEventInterface&MockInterface $queueEventProducer;
    private ProducerInterface&MockInterface $taskProducer;
    private TracerFactoryInterface&MockInterface $tracerFactory;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for external dependencies
        $this->queueEventProducer = Mockery::mock(QueueEventInterface::class);
        $this->taskProducer = Mockery::mock(ProducerInterface::class);
        $this->tracerFactory = Mockery::mock(TracerFactoryInterface::class);

        // Setup tracer factory to return NoopTracer
        $this->tracerFactory
            ->shouldReceive('getTracer')
            ->andReturn(new NoopTracer());
    }

    // =========================================================================
    // EventPublisher Tests
    // =========================================================================

    #[Test]
    public function eventPublisherShouldPublishValidEvent(): void
    {
        // Arrange
        $eventPublisher = new EventPublisher(
            $this->queueEventProducer,
            new NullLogger(),
        );

        // Register the test event class
        $eventPublisher->registerEventClass(
            TestSerializableEvent::class,
            TestSerializableEvent::class,
        );

        $entry = $this->createEventEntryWithPayload(
            TestSerializableEvent::class,
            json_encode([
                'uuid' => 'test-uuid-123',
                'playhead' => 1,
                'metadata' => [],
                'payload' => ['id' => 'test-id', 'name' => 'Test Event'],
                'recorded_on' => '2024-01-15T10:00:00.000000+00:00',
            ], JSON_THROW_ON_ERROR),
        );

        // Expect the event to be published
        $this->queueEventProducer
            ->shouldReceive('publishEventToQueue')
            ->once()
            ->with(Mockery::type(TestSerializableEvent::class));

        // Act
        $eventPublisher->publish($entry);

        // Assert - verification is done by Mockery expectations
        self::assertTrue(true);
    }

    #[Test]
    public function eventPublisherShouldSupportEventMessageType(): void
    {
        // Arrange
        $eventPublisher = new EventPublisher(
            $this->queueEventProducer,
            new NullLogger(),
        );

        // Act & Assert
        self::assertTrue($eventPublisher->supports(OutboxMessageType::EVENT->value));
        self::assertFalse($eventPublisher->supports(OutboxMessageType::TASK->value));
    }

    #[Test]
    public function eventPublisherShouldThrowOnInvalidJsonPayload(): void
    {
        // Arrange
        $eventPublisher = new EventPublisher(
            $this->queueEventProducer,
            new NullLogger(),
        );

        $entry = $this->createEventEntryWithPayload(
            'TestEvent',
            'invalid-json{',
        );

        // Expect
        $this->expectException(OutboxPublishException::class);
        $this->expectExceptionMessage('Invalid JSON payload');

        // Act
        $eventPublisher->publish($entry);
    }

    #[Test]
    public function eventPublisherShouldThrowOnUnregisteredEventClass(): void
    {
        // Arrange
        $eventPublisher = new EventPublisher(
            $this->queueEventProducer,
            new NullLogger(),
        );

        $entry = $this->createEventEntryWithPayload(
            'NonExistent\Event\Class',
            json_encode(['test' => 'data'], JSON_THROW_ON_ERROR),
        );

        // Expect
        $this->expectException(OutboxPublishException::class);

        // Act
        $eventPublisher->publish($entry);
    }

    // =========================================================================
    // TaskPublisher Tests
    // =========================================================================

    #[Test]
    public function taskPublisherShouldPublishValidTask(): void
    {
        // Arrange
        $taskPublisher = new TaskPublisher(
            $this->taskProducer,
            new NullLogger(),
        );

        $payload = [
            'type' => 'article.create.command',
            'args' => ['process-uuid-123', 'arg1', 'arg2'],
        ];

        $entry = $this->createTaskEntryWithPayload(
            json_encode($payload, JSON_THROW_ON_ERROR),
        );

        // Expect the task to be sent
        $this->taskProducer
            ->shouldReceive('sendCommand')
            ->once()
            ->with(Mockery::type('string'), $payload);

        // Act
        $taskPublisher->publish($entry);

        // Assert - verification is done by Mockery expectations
        self::assertTrue(true);
    }

    #[Test]
    public function taskPublisherShouldSupportTaskMessageType(): void
    {
        // Arrange
        $taskPublisher = new TaskPublisher(
            $this->taskProducer,
            new NullLogger(),
        );

        // Act & Assert
        self::assertTrue($taskPublisher->supports(OutboxMessageType::TASK->value));
        self::assertFalse($taskPublisher->supports(OutboxMessageType::EVENT->value));
    }

    #[Test]
    public function taskPublisherShouldThrowOnMissingTypeKey(): void
    {
        // Arrange
        $taskPublisher = new TaskPublisher(
            $this->taskProducer,
            new NullLogger(),
        );

        $entry = $this->createTaskEntryWithPayload(
            json_encode(['args' => ['arg1']], JSON_THROW_ON_ERROR),
        );

        // Expect
        $this->expectException(OutboxPublishException::class);
        $this->expectExceptionMessage('Missing required key: type');

        // Act
        $taskPublisher->publish($entry);
    }

    #[Test]
    public function taskPublisherShouldThrowOnMissingArgsKey(): void
    {
        // Arrange
        $taskPublisher = new TaskPublisher(
            $this->taskProducer,
            new NullLogger(),
        );

        $entry = $this->createTaskEntryWithPayload(
            json_encode(['type' => 'test.command'], JSON_THROW_ON_ERROR),
        );

        // Expect
        $this->expectException(OutboxPublishException::class);
        $this->expectExceptionMessage('Missing or invalid key: args');

        // Act
        $taskPublisher->publish($entry);
    }

    #[Test]
    public function taskPublisherShouldThrowOnInvalidArgsType(): void
    {
        // Arrange
        $taskPublisher = new TaskPublisher(
            $this->taskProducer,
            new NullLogger(),
        );

        $entry = $this->createTaskEntryWithPayload(
            json_encode(['type' => 'test.command', 'args' => 'not-an-array'], JSON_THROW_ON_ERROR),
        );

        // Expect
        $this->expectException(OutboxPublishException::class);
        $this->expectExceptionMessage('Missing or invalid key: args');

        // Act
        $taskPublisher->publish($entry);
    }

    #[Test]
    public function taskPublisherShouldThrowOnInvalidJsonPayload(): void
    {
        // Arrange
        $taskPublisher = new TaskPublisher(
            $this->taskProducer,
            new NullLogger(),
        );

        $entry = $this->createTaskEntryWithPayload('invalid-json{');

        // Expect
        $this->expectException(OutboxPublishException::class);
        $this->expectExceptionMessage('Invalid JSON payload');

        // Act
        $taskPublisher->publish($entry);
    }

    // =========================================================================
    // OutboxPublisher Routing Tests
    // =========================================================================

    #[Test]
    public function outboxPublisherShouldRouteEventToEventPublisher(): void
    {
        // Arrange
        $eventPublisher = Mockery::mock(OutboxPublisherInterface::class);
        $taskPublisher = Mockery::mock(OutboxPublisherInterface::class);

        $outboxPublisher = new OutboxPublisher(
            $eventPublisher,
            $taskPublisher,
            $this->tracerFactory,
            new NullLogger(),
        );

        $entry = $this->createEventEntryWithPayload(
            'TestEvent',
            json_encode(['test' => 'data'], JSON_THROW_ON_ERROR),
        );

        // Expect event publisher to be called
        $eventPublisher
            ->shouldReceive('publish')
            ->once()
            ->with($entry);

        // Task publisher should not be called
        $taskPublisher
            ->shouldNotReceive('publish');

        // Act
        $outboxPublisher->publish($entry);

        // Assert - verification is done by Mockery expectations
        self::assertTrue(true);
    }

    #[Test]
    public function outboxPublisherShouldRouteTaskToTaskPublisher(): void
    {
        // Arrange
        $eventPublisher = Mockery::mock(OutboxPublisherInterface::class);
        $taskPublisher = Mockery::mock(OutboxPublisherInterface::class);

        $outboxPublisher = new OutboxPublisher(
            $eventPublisher,
            $taskPublisher,
            $this->tracerFactory,
            new NullLogger(),
        );

        $entry = $this->createTaskEntryWithPayload(
            json_encode(['type' => 'test.command', 'args' => []], JSON_THROW_ON_ERROR),
        );

        // Task publisher should be called
        $taskPublisher
            ->shouldReceive('publish')
            ->once()
            ->with($entry);

        // Event publisher should not be called
        $eventPublisher
            ->shouldNotReceive('publish');

        // Act
        $outboxPublisher->publish($entry);

        // Assert - verification is done by Mockery expectations
        self::assertTrue(true);
    }

    #[Test]
    public function outboxPublisherShouldSupportBothMessageTypes(): void
    {
        // Arrange
        $outboxPublisher = new OutboxPublisher(
            Mockery::mock(OutboxPublisherInterface::class),
            Mockery::mock(OutboxPublisherInterface::class),
            $this->tracerFactory,
            new NullLogger(),
        );

        // Act & Assert
        self::assertTrue($outboxPublisher->supports(OutboxMessageType::EVENT->value));
        self::assertTrue($outboxPublisher->supports(OutboxMessageType::TASK->value));
        self::assertFalse($outboxPublisher->supports('unknown'));
    }

    #[Test]
    public function outboxPublisherShouldPropagateExceptionsFromDelegates(): void
    {
        // Arrange
        $eventPublisher = Mockery::mock(OutboxPublisherInterface::class);
        $taskPublisher = Mockery::mock(OutboxPublisherInterface::class);

        $outboxPublisher = new OutboxPublisher(
            $eventPublisher,
            $taskPublisher,
            $this->tracerFactory,
            new NullLogger(),
        );

        $entry = $this->createEventEntryWithPayload(
            'TestEvent',
            json_encode(['test' => 'data'], JSON_THROW_ON_ERROR),
        );

        $expectedException = new \RuntimeException('Publishing failed');

        $eventPublisher
            ->shouldReceive('publish')
            ->once()
            ->andThrow($expectedException);

        // Expect
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Publishing failed');

        // Act
        $outboxPublisher->publish($entry);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    private function createEventEntryWithPayload(string $eventType, string $payload): OutboxEntry
    {
        return OutboxEntry::createForEvent(
            aggregateType: 'Test',
            aggregateId: 'test-aggregate-' . uniqid(),
            eventType: $eventType,
            eventPayload: $payload,
            topic: 'events.test',
            routingKey: 'event.test_created',
        );
    }

    private function createTaskEntryWithPayload(string $payload): OutboxEntry
    {
        return OutboxEntry::createForTask(
            aggregateType: 'Test',
            aggregateId: 'test-aggregate-' . uniqid(),
            commandType: 'test.create.command',
            commandPayload: $payload,
            topic: 'tasks.test',
            routingKey: 'task.test_create',
        );
    }
}

/**
 * Test serializable event for integration tests.
 */
final class TestSerializableEvent implements Serializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
    ) {
    }

    /**
     * @param array{id: string, name: string}|array{payload: array{id: string, name: string}} $data
     */
    public static function deserialize(array $data): self
    {
        // Handle both direct payload and wrapped payload formats
        if (isset($data['payload'])) {
            $data = $data['payload'];
        }

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
