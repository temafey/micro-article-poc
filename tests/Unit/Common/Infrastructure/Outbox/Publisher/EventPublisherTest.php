<?php

declare(strict_types=1);

namespace Micro\Tests\Unit\Common\Infrastructure\Outbox\Publisher;

use Broadway\Serializer\Serializable;
use Micro\Component\Common\Domain\Outbox\OutboxEntryInterface;
use Micro\Component\Common\Domain\Outbox\OutboxMessageType;
use Micro\Component\Common\Infrastructure\Outbox\OutboxEntry;
use Micro\Component\Common\Infrastructure\Outbox\Publisher\EventPublisher;
use Micro\Component\Common\Infrastructure\Outbox\Publisher\OutboxPublishException;
use MicroModule\EventQueue\Domain\EventHandling\QueueEventInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for EventPublisher.
 *
 * @see EventPublisher
 */
#[CoversClass(EventPublisher::class)]
final class EventPublisherTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private QueueEventInterface&MockInterface $queueEventProducer;
    private LoggerInterface&MockInterface $logger;
    private EventPublisher $publisher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->queueEventProducer = Mockery::mock(QueueEventInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->publisher = new EventPublisher(
            $this->queueEventProducer,
            $this->logger,
        );
    }

    // =========================================================================
    // supports() Tests
    // =========================================================================

    #[Test]
    public function supportsTrueForEventMessageType(): void
    {
        self::assertTrue($this->publisher->supports(OutboxMessageType::EVENT->value));
    }

    #[Test]
    public function supportsFalseForTaskMessageType(): void
    {
        self::assertFalse($this->publisher->supports(OutboxMessageType::TASK->value));
    }

    #[Test]
    public function supportsFalseForUnknownMessageType(): void
    {
        self::assertFalse($this->publisher->supports('unknown'));
    }

    // =========================================================================
    // registerEventClass() Tests
    // =========================================================================

    #[Test]
    public function registerEventClassAddsToMap(): void
    {
        $this->publisher->registerEventClass(
            'Micro.Article.Domain.Event.ArticleCreatedEvent',
            TestSerializableEvent::class,
        );

        // Verify by publishing an event that uses this registration
        $entry = $this->createEventEntry(
            'Micro.Article.Domain.Event.ArticleCreatedEvent',
            json_encode(['id' => 'test-123', 'title' => 'Test Article'], JSON_THROW_ON_ERROR),
        );

        $this->queueEventProducer
            ->shouldReceive('publishEventToQueue')
            ->once()
            ->with(Mockery::type(TestSerializableEvent::class));

        $this->logger->shouldReceive('debug')->once();

        $this->publisher->publish($entry);
    }

    #[Test]
    public function registerEventClassesAddsMultipleToMap(): void
    {
        $this->publisher->registerEventClasses([
            'Event.TypeA' => TestSerializableEvent::class,
            'Event.TypeB' => TestSerializableEvent::class,
        ]);

        // Verify first registration
        $entry = $this->createEventEntry(
            'Event.TypeA',
            json_encode(['id' => 'test-123', 'title' => 'Test'], JSON_THROW_ON_ERROR),
        );

        $this->queueEventProducer
            ->shouldReceive('publishEventToQueue')
            ->once()
            ->with(Mockery::type(TestSerializableEvent::class));

        $this->logger->shouldReceive('debug')->once();

        $this->publisher->publish($entry);
    }

    // =========================================================================
    // publish() Tests
    // =========================================================================

    #[Test]
    public function publishWithRegisteredEventClass(): void
    {
        $this->publisher->registerEventClass(
            'Micro.Article.Event.Created',
            TestSerializableEvent::class,
        );

        $payload = json_encode([
            'id' => 'article-uuid-123',
            'title' => 'Test Title',
        ], JSON_THROW_ON_ERROR);

        $entry = $this->createEventEntry('Micro.Article.Event.Created', $payload);

        $this->queueEventProducer
            ->shouldReceive('publishEventToQueue')
            ->once()
            ->with(Mockery::on(function (TestSerializableEvent $event) {
                return $event->id === 'article-uuid-123'
                    && $event->title === 'Test Title';
            }));

        $this->logger
            ->shouldReceive('debug')
            ->once()
            ->with('Event published from outbox', Mockery::type('array'));

        $this->publisher->publish($entry);
    }

    #[Test]
    public function publishWithFqcnEventType(): void
    {
        // Uses event type as FQCN directly without registration
        $payload = json_encode([
            'id' => 'test-123',
            'title' => 'FQCN Test',
        ], JSON_THROW_ON_ERROR);

        $entry = $this->createEventEntry(TestSerializableEvent::class, $payload);

        $this->queueEventProducer
            ->shouldReceive('publishEventToQueue')
            ->once()
            ->with(Mockery::type(TestSerializableEvent::class));

        $this->logger->shouldReceive('debug')->once();

        $this->publisher->publish($entry);
    }

    #[Test]
    public function publishThrowsOnInvalidJsonPayload(): void
    {
        $entry = $this->createEventEntry('SomeEvent', 'invalid{json');

        $this->expectException(OutboxPublishException::class);
        $this->expectExceptionMessage('Invalid JSON payload');

        $this->publisher->publish($entry);
    }

    #[Test]
    public function publishThrowsOnUnresolvedEventClass(): void
    {
        $payload = json_encode(['data' => 'test'], JSON_THROW_ON_ERROR);
        $entry = $this->createEventEntry('NonExistent.Event.Type', $payload);

        $this->expectException(OutboxPublishException::class);
        $this->expectExceptionMessage('Cannot resolve event class');

        $this->publisher->publish($entry);
    }

    #[Test]
    public function publishThrowsOnDeserializationFailure(): void
    {
        $this->publisher->registerEventClass(
            'Failing.Event',
            FailingDeserializeEvent::class,
        );

        $payload = json_encode(['data' => 'test'], JSON_THROW_ON_ERROR);
        $entry = $this->createEventEntry('Failing.Event', $payload);

        $this->expectException(OutboxPublishException::class);
        $this->expectExceptionMessage('Failed to deserialize event');

        $this->publisher->publish($entry);
    }

    #[Test]
    public function publishLogsCorrectContext(): void
    {
        $this->publisher->registerEventClass(
            'TestEvent',
            TestSerializableEvent::class,
        );

        $payload = json_encode(['id' => 'id-1', 'title' => 'Title'], JSON_THROW_ON_ERROR);
        $entry = $this->createEventEntry('TestEvent', $payload);

        $this->queueEventProducer->shouldReceive('publishEventToQueue')->once();

        $this->logger
            ->shouldReceive('debug')
            ->once()
            ->with('Event published from outbox', Mockery::on(function (array $context) use ($entry) {
                return $context['message_id'] === $entry->getId()
                    && $context['event_type'] === 'TestEvent'
                    && $context['event_class'] === TestSerializableEvent::class
                    && $context['topic'] === $entry->getTopic()
                    && $context['aggregate_id'] === $entry->getAggregateId();
            }));

        $this->publisher->publish($entry);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    private function createEventEntry(string $eventType, string $payload): OutboxEntryInterface
    {
        return OutboxEntry::createForEvent(
            aggregateType: 'Article',
            aggregateId: 'aggregate-123',
            eventType: $eventType,
            eventPayload: $payload,
            topic: 'events.article',
            routingKey: 'event.article.created',
        );
    }
}

/**
 * Test event class implementing Serializable.
 */
class TestSerializableEvent implements Serializable
{
    public function __construct(
        public readonly string $id,
        public readonly string $title,
    ) {
    }

    public static function deserialize(array $data): self
    {
        return new self(
            $data['id'] ?? '',
            $data['title'] ?? '',
        );
    }

    public function serialize(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
        ];
    }
}

/**
 * Test event that throws during deserialization.
 */
class FailingDeserializeEvent implements Serializable
{
    public static function deserialize(array $data): self
    {
        throw new \RuntimeException('Deserialization intentionally failed');
    }

    public function serialize(): array
    {
        return [];
    }
}
