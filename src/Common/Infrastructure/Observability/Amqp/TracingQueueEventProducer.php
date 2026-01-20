<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Observability\Amqp;

use Broadway\Serializer\Serializable;
use Micro\Component\Common\Infrastructure\Observability\Service\TracerFactory;
use MicroModule\EventQueue\Domain\EventHandling\QueueEventInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;

/**
 * Tracing decorator for queue event producers.
 *
 * Wraps event publishing with OpenTelemetry spans for distributed trace
 * visibility across async message boundaries.
 *
 * Note: W3C Trace Context injection into message headers requires enqueue-level
 * instrumentation. This decorator provides span visibility for the publish operation.
 *
 * @see ADR-014: Observability Stack Modernization - Phase 3.4
 */
final class TracingQueueEventProducer implements QueueEventInterface
{
    public function __construct(
        private readonly QueueEventInterface $innerProducer,
        private readonly TracerFactory $tracerFactory,
        private readonly string $topic,
    ) {
    }

    /**
     * Publish event to queue with tracing.
     *
     * Creates a producer span wrapping the inner producer's publish operation.
     */
    public function publishEventToQueue(Serializable $event): void
    {
        $tracer = $this->tracerFactory->getTracer();
        $eventClass = get_class($event);
        $eventShortName = substr($eventClass, strrpos($eventClass, '\\') + 1);

        $span = $tracer->spanBuilder("amqp.{$this->topic}.publish")
            ->setSpanKind(SpanKind::KIND_PRODUCER)
            ->setAttribute('messaging.system', 'rabbitmq')
            ->setAttribute('messaging.operation', 'publish')
            ->setAttribute('messaging.destination.name', $this->topic)
            ->setAttribute('messaging.message.type', $eventShortName)
            ->startSpan();

        $scope = $span->activate();

        try {
            // Delegate to inner producer
            $this->innerProducer->publishEventToQueue($event);

            $span->setStatus(StatusCode::STATUS_OK);
            $span->setAttribute('messaging.result', 'SENT');

            // Record trace ID for correlation
            $spanContext = $span->getContext();
            if ($spanContext->isValid()) {
                $span->setAttribute('messaging.trace_id', $spanContext->getTraceId());
            }
        } catch (\Throwable $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            $span->setAttribute('messaging.result', 'FAILED');
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    /**
     * Get the topic this producer publishes to.
     */
    public function getTopic(): string
    {
        return $this->topic;
    }
}
