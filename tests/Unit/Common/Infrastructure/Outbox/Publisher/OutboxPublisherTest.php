<?php

declare(strict_types=1);

namespace Micro\Tests\Unit\Common\Infrastructure\Outbox\Publisher;

use Micro\Component\Common\Domain\Outbox\OutboxEntryInterface;
use Micro\Component\Common\Domain\Outbox\OutboxMessageType;
use Micro\Component\Common\Infrastructure\Observability\Service\TracerFactoryInterface;
use Micro\Component\Common\Infrastructure\Outbox\OutboxEntry;
use Micro\Component\Common\Infrastructure\Outbox\Publisher\OutboxPublisher;
use Micro\Component\Common\Infrastructure\Outbox\Publisher\OutboxPublishException;
use Micro\Component\Common\Infrastructure\Outbox\Publisher\OutboxPublisherInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use OpenTelemetry\API\Trace\SpanBuilderInterface;
use OpenTelemetry\API\Trace\SpanContextInterface;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\API\Trace\TracerInterface;
use OpenTelemetry\Context\ScopeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for OutboxPublisher (unified publisher with OTel tracing).
 *
 * @see OutboxPublisher
 */
#[CoversClass(OutboxPublisher::class)]
final class OutboxPublisherTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private OutboxPublisherInterface&MockInterface $eventPublisher;
    private OutboxPublisherInterface&MockInterface $taskPublisher;
    private TracerFactoryInterface&MockInterface $tracerFactory;
    private LoggerInterface&MockInterface $logger;
    private TracerInterface&MockInterface $tracer;
    private SpanBuilderInterface&MockInterface $spanBuilder;
    private SpanInterface&MockInterface $span;
    private ScopeInterface&MockInterface $scope;
    private SpanContextInterface&MockInterface $spanContext;
    private OutboxPublisher $publisher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->eventPublisher = Mockery::mock(OutboxPublisherInterface::class);
        $this->taskPublisher = Mockery::mock(OutboxPublisherInterface::class);
        $this->tracerFactory = Mockery::mock(TracerFactoryInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);

        // Setup tracer chain
        $this->tracer = Mockery::mock(TracerInterface::class);
        $this->spanBuilder = Mockery::mock(SpanBuilderInterface::class);
        $this->span = Mockery::mock(SpanInterface::class);
        $this->scope = Mockery::mock(ScopeInterface::class);
        $this->spanContext = Mockery::mock(SpanContextInterface::class);

        $this->publisher = new OutboxPublisher(
            $this->eventPublisher,
            $this->taskPublisher,
            $this->tracerFactory,
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
    public function supportsTrueForTaskMessageType(): void
    {
        self::assertTrue($this->publisher->supports(OutboxMessageType::TASK->value));
    }

    #[Test]
    public function supportsFalseForUnknownMessageType(): void
    {
        self::assertFalse($this->publisher->supports('unknown'));
        self::assertFalse($this->publisher->supports(''));
    }

    // =========================================================================
    // publish() Event Routing Tests
    // =========================================================================

    #[Test]
    public function publishRoutesEventToEventPublisher(): void
    {
        $entry = $this->createEventEntry();

        $this->setupSuccessfulSpan();

        $this->eventPublisher
            ->shouldReceive('publish')
            ->once()
            ->with($entry);

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Outbox message published', Mockery::type('array'));

        $this->publisher->publish($entry);
    }

    #[Test]
    public function publishRoutesTaskToTaskPublisher(): void
    {
        $entry = $this->createTaskEntry();

        $this->setupSuccessfulSpan();

        $this->taskPublisher
            ->shouldReceive('publish')
            ->once()
            ->with($entry);

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Outbox message published', Mockery::type('array'));

        $this->publisher->publish($entry);
    }

    // =========================================================================
    // publish() OpenTelemetry Span Tests
    // =========================================================================

    #[Test]
    public function publishCreatesSpanWithCorrectAttributes(): void
    {
        $entry = $this->createEventEntry();

        $this->tracerFactory->shouldReceive('getTracer')->once()->andReturn($this->tracer);

        $this->tracer
            ->shouldReceive('spanBuilder')
            ->once()
            ->with('outbox.publish')
            ->andReturn($this->spanBuilder);

        // Verify span attributes
        $this->spanBuilder->shouldReceive('setSpanKind')->once()->andReturnSelf();
        $this->spanBuilder
            ->shouldReceive('setAttribute')
            ->with('messaging.system', 'outbox')
            ->once()
            ->andReturnSelf();
        $this->spanBuilder
            ->shouldReceive('setAttribute')
            ->with('messaging.operation', 'publish')
            ->once()
            ->andReturnSelf();
        $this->spanBuilder
            ->shouldReceive('setAttribute')
            ->with('messaging.destination.name', $entry->getTopic())
            ->once()
            ->andReturnSelf();
        $this->spanBuilder
            ->shouldReceive('setAttribute')
            ->with('outbox.message_id', $entry->getId())
            ->once()
            ->andReturnSelf();
        $this->spanBuilder
            ->shouldReceive('setAttribute')
            ->with('outbox.message_type', OutboxMessageType::EVENT->value)
            ->once()
            ->andReturnSelf();
        $this->spanBuilder
            ->shouldReceive('setAttribute')
            ->with('outbox.event_type', $entry->getEventType())
            ->once()
            ->andReturnSelf();
        $this->spanBuilder
            ->shouldReceive('setAttribute')
            ->with('outbox.aggregate_type', $entry->getAggregateType())
            ->once()
            ->andReturnSelf();
        $this->spanBuilder
            ->shouldReceive('setAttribute')
            ->with('outbox.aggregate_id', $entry->getAggregateId())
            ->once()
            ->andReturnSelf();
        $this->spanBuilder
            ->shouldReceive('setAttribute')
            ->with('outbox.retry_count', $entry->getRetryCount())
            ->once()
            ->andReturnSelf();
        $this->spanBuilder
            ->shouldReceive('setAttribute')
            ->with('outbox.routing_key', $entry->getRoutingKey())
            ->once()
            ->andReturnSelf();

        $this->spanBuilder->shouldReceive('startSpan')->once()->andReturn($this->span);
        $this->span->shouldReceive('activate')->once()->andReturn($this->scope);

        // Success path
        $this->eventPublisher->shouldReceive('publish')->once();
        $this->span->shouldReceive('setStatus')->with(StatusCode::STATUS_OK)->once();
        $this->span->shouldReceive('setAttribute')->with('outbox.result', 'published')->once();
        $this->spanContext->shouldReceive('isValid')->andReturn(true);
        $this->spanContext->shouldReceive('getTraceId')->andReturn('trace-123');
        $this->span->shouldReceive('getContext')->andReturn($this->spanContext);
        $this->span->shouldReceive('setAttribute')->with('outbox.trace_id', 'trace-123')->once();

        $this->scope->shouldReceive('detach')->once();
        $this->span->shouldReceive('end')->once();

        $this->logger->shouldReceive('info')->once();

        $this->publisher->publish($entry);
    }

    #[Test]
    public function publishSetsErrorStatusOnFailure(): void
    {
        $entry = $this->createEventEntry();
        $exception = new \RuntimeException('Publish failed');

        $this->setupSpanForException($exception);

        $this->eventPublisher
            ->shouldReceive('publish')
            ->once()
            ->andThrow($exception);

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Outbox message publish failed', Mockery::type('array'));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Publish failed');

        $this->publisher->publish($entry);
    }

    // =========================================================================
    // publish() Logging Tests
    // =========================================================================

    #[Test]
    public function publishLogsCorrectContextOnSuccess(): void
    {
        $entry = $this->createEventEntry();

        $this->setupSuccessfulSpan();

        $this->eventPublisher->shouldReceive('publish')->once();

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Outbox message published', Mockery::on(function (array $context) use ($entry) {
                return $context['message_id'] === $entry->getId()
                    && $context['message_type'] === OutboxMessageType::EVENT->value
                    && $context['event_type'] === $entry->getEventType()
                    && $context['topic'] === $entry->getTopic()
                    && $context['routing_key'] === $entry->getRoutingKey();
            }));

        $this->publisher->publish($entry);
    }

    #[Test]
    public function publishLogsCorrectContextOnFailure(): void
    {
        $entry = $this->createTaskEntry();
        $exception = new OutboxPublishException('Invalid format');

        $this->setupSpanForException($exception);

        $this->taskPublisher
            ->shouldReceive('publish')
            ->once()
            ->andThrow($exception);

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Outbox message publish failed', Mockery::on(function (array $context) use ($entry) {
                return $context['message_id'] === $entry->getId()
                    && $context['message_type'] === OutboxMessageType::TASK->value
                    && $context['error'] === 'Invalid format';
            }));

        $this->expectException(OutboxPublishException::class);

        $this->publisher->publish($entry);
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    #[Test]
    public function publishHandlesInvalidSpanContext(): void
    {
        $entry = $this->createEventEntry();

        $this->tracerFactory->shouldReceive('getTracer')->andReturn($this->tracer);
        $this->tracer->shouldReceive('spanBuilder')->andReturn($this->spanBuilder);
        $this->spanBuilder->shouldReceive('setSpanKind')->andReturnSelf();
        $this->spanBuilder->shouldReceive('setAttribute')->andReturnSelf();
        $this->spanBuilder->shouldReceive('startSpan')->andReturn($this->span);
        $this->span->shouldReceive('activate')->andReturn($this->scope);

        $this->eventPublisher->shouldReceive('publish')->once();

        $this->span->shouldReceive('setStatus')->with(StatusCode::STATUS_OK)->once();
        $this->span->shouldReceive('setAttribute')->with('outbox.result', 'published')->once();

        // Invalid span context - should not add trace_id
        $this->spanContext->shouldReceive('isValid')->andReturn(false);
        $this->span->shouldReceive('getContext')->andReturn($this->spanContext);
        // Note: trace_id should NOT be set when context is invalid

        $this->scope->shouldReceive('detach')->once();
        $this->span->shouldReceive('end')->once();

        $this->logger->shouldReceive('info')->once();

        $this->publisher->publish($entry);
    }

    #[Test]
    public function publishAlwaysClosesSpanEvenOnException(): void
    {
        $entry = $this->createEventEntry();

        $this->tracerFactory->shouldReceive('getTracer')->andReturn($this->tracer);
        $this->tracer->shouldReceive('spanBuilder')->andReturn($this->spanBuilder);
        $this->spanBuilder->shouldReceive('setSpanKind')->andReturnSelf();
        $this->spanBuilder->shouldReceive('setAttribute')->andReturnSelf();
        $this->spanBuilder->shouldReceive('startSpan')->andReturn($this->span);
        $this->span->shouldReceive('activate')->andReturn($this->scope);

        $this->eventPublisher
            ->shouldReceive('publish')
            ->andThrow(new \RuntimeException('Error'));

        $this->span->shouldReceive('recordException')->once();
        $this->span->shouldReceive('setStatus')->with(StatusCode::STATUS_ERROR, 'Error')->once();
        $this->span->shouldReceive('setAttribute')->andReturnSelf();

        // These MUST be called in finally block
        $this->scope->shouldReceive('detach')->once();
        $this->span->shouldReceive('end')->once();

        $this->logger->shouldReceive('error')->once();

        $this->expectException(\RuntimeException::class);

        $this->publisher->publish($entry);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    private function createEventEntry(): OutboxEntryInterface
    {
        return OutboxEntry::createForEvent(
            aggregateType: 'Article',
            aggregateId: 'aggregate-123',
            eventType: 'ArticleCreatedEvent',
            eventPayload: '{"id":"123"}',
            topic: 'events.article',
            routingKey: 'event.article.created',
        );
    }

    private function createTaskEntry(): OutboxEntryInterface
    {
        return OutboxEntry::createForTask(
            aggregateType: 'Article',
            aggregateId: 'aggregate-456',
            commandType: 'article.create.command',
            commandPayload: '{"type":"article.create.command","args":[]}',
            topic: 'job_command_bus',
            routingKey: 'task.command.run',
        );
    }

    private function setupSuccessfulSpan(): void
    {
        $this->tracerFactory->shouldReceive('getTracer')->andReturn($this->tracer);
        $this->tracer->shouldReceive('spanBuilder')->andReturn($this->spanBuilder);
        $this->spanBuilder->shouldReceive('setSpanKind')->andReturnSelf();
        $this->spanBuilder->shouldReceive('setAttribute')->andReturnSelf();
        $this->spanBuilder->shouldReceive('startSpan')->andReturn($this->span);
        $this->span->shouldReceive('activate')->andReturn($this->scope);
        $this->span->shouldReceive('setStatus')->with(StatusCode::STATUS_OK)->once();
        $this->span->shouldReceive('setAttribute')->andReturnSelf();
        $this->spanContext->shouldReceive('isValid')->andReturn(true);
        $this->spanContext->shouldReceive('getTraceId')->andReturn('trace-123');
        $this->span->shouldReceive('getContext')->andReturn($this->spanContext);
        $this->scope->shouldReceive('detach')->once();
        $this->span->shouldReceive('end')->once();
    }

    private function setupSpanForException(\Throwable $exception): void
    {
        $this->tracerFactory->shouldReceive('getTracer')->andReturn($this->tracer);
        $this->tracer->shouldReceive('spanBuilder')->andReturn($this->spanBuilder);
        $this->spanBuilder->shouldReceive('setSpanKind')->andReturnSelf();
        $this->spanBuilder->shouldReceive('setAttribute')->andReturnSelf();
        $this->spanBuilder->shouldReceive('startSpan')->andReturn($this->span);
        $this->span->shouldReceive('activate')->andReturn($this->scope);

        $this->span->shouldReceive('recordException')->with($exception)->once();
        $this->span
            ->shouldReceive('setStatus')
            ->with(StatusCode::STATUS_ERROR, $exception->getMessage())
            ->once();
        $this->span->shouldReceive('setAttribute')->andReturnSelf();

        $this->scope->shouldReceive('detach')->once();
        $this->span->shouldReceive('end')->once();
    }
}
