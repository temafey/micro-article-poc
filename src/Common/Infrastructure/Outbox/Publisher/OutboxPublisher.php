<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Outbox\Publisher;

use Micro\Component\Common\Domain\Outbox\OutboxEntryInterface;
use Micro\Component\Common\Domain\Outbox\OutboxMessageType;
use Micro\Component\Common\Infrastructure\Observability\Service\TracerFactoryInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Unified outbox publisher that routes messages to appropriate handlers.
 *
 * Delegates publishing based on message_type:
 * - 'event' → EventPublisher (QueueEventInterface)
 * - 'task' → TaskPublisher (ProducerInterface)
 *
 * Includes OpenTelemetry tracing for full distributed trace visibility
 * across the outbox publish pipeline.
 *
 * @see ADR-006: Transactional Outbox Pattern
 * @see TASK-14.4: Background Publisher
 */
final class OutboxPublisher implements OutboxPublisherInterface
{
    public function __construct(
        private readonly OutboxPublisherInterface $eventPublisher,
        private readonly OutboxPublisherInterface $taskPublisher,
        private readonly TracerFactoryInterface $tracerFactory,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function publish(OutboxEntryInterface $entry): void
    {
        $tracer = $this->tracerFactory->getTracer();
        $messageType = $entry->getMessageType();

        $span = $tracer->spanBuilder('outbox.publish')
            ->setSpanKind(SpanKind::KIND_PRODUCER)
            ->setAttribute('messaging.system', 'outbox')
            ->setAttribute('messaging.operation', 'publish')
            ->setAttribute('messaging.destination.name', $entry->getTopic())
            ->setAttribute('outbox.message_id', $entry->getId())
            ->setAttribute('outbox.message_type', $messageType->value)
            ->setAttribute('outbox.event_type', $entry->getEventType())
            ->setAttribute('outbox.aggregate_type', $entry->getAggregateType())
            ->setAttribute('outbox.aggregate_id', (string) $entry->getAggregateId())
            ->setAttribute('outbox.retry_count', $entry->getRetryCount())
            ->setAttribute('outbox.routing_key', $entry->getRoutingKey())
            ->startSpan();

        $scope = $span->activate();

        try {
            $publisher = $this->resolvePublisher($messageType);
            $publisher->publish($entry);

            $span->setStatus(StatusCode::STATUS_OK);
            $span->setAttribute('outbox.result', 'published');

            // Record trace ID for correlation
            $spanContext = $span->getContext();
            if ($spanContext->isValid()) {
                $span->setAttribute('outbox.trace_id', $spanContext->getTraceId());
            }

            $this->logger->info('Outbox message published', [
                'message_id' => $entry->getId(),
                'message_type' => $messageType->value,
                'event_type' => $entry->getEventType(),
                'topic' => $entry->getTopic(),
                'routing_key' => $entry->getRoutingKey(),
            ]);
        } catch (\Throwable $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            $span->setAttribute('outbox.result', 'failed');
            $span->setAttribute('outbox.error', $e->getMessage());

            $this->logger->error('Outbox message publish failed', [
                'message_id' => $entry->getId(),
                'message_type' => $messageType->value,
                'event_type' => $entry->getEventType(),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $messageType): bool
    {
        return in_array($messageType, [
            OutboxMessageType::EVENT->value,
            OutboxMessageType::TASK->value,
        ], true);
    }

    /**
     * Resolve the appropriate publisher for the message type.
     *
     * @throws OutboxPublishException If message type is unsupported
     */
    private function resolvePublisher(OutboxMessageType $messageType): OutboxPublisherInterface
    {
        return match ($messageType) {
            OutboxMessageType::EVENT => $this->eventPublisher,
            OutboxMessageType::TASK => $this->taskPublisher,
        };
    }
}
