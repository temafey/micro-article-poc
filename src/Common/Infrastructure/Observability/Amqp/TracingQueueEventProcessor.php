<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Observability\Amqp;

use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Micro\Component\Common\Infrastructure\Observability\Service\TracerFactory;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;

/**
 * Tracing decorator for queue event processors.
 *
 * Wraps queue message processing with OpenTelemetry spans and extracts
 * trace context from incoming messages to continue distributed traces
 * across async boundaries.
 *
 * Note: This decorator does NOT implement TopicSubscriberInterface.
 * Topic subscription is handled by the inner processor. The decorator
 * only wraps the process() method with tracing instrumentation.
 *
 * @see ADR-014: Observability Stack Modernization - Phase 3.4
 */
final class TracingQueueEventProcessor implements Processor
{
    public function __construct(
        private readonly Processor $innerProcessor,
        private readonly TracerFactory $tracerFactory,
        private readonly TraceContextPropagator $propagator,
        private readonly string $topic,
    ) {
    }

    /**
     * Process message with tracing.
     *
     * Extracts trace context from message headers, creates a consumer span,
     * and continues the distributed trace across the async boundary.
     *
     * @return object|string ACK, REJECT, or REQUEUE
     */
    public function process(Message $message, Context $context): object|string
    {
        $tracer = $this->tracerFactory->getTracer();

        // Extract parent trace context from message headers
        $parentSpanContext = $this->propagator->extract($message);

        // Build span with proper messaging semantics
        $spanBuilder = $tracer->spanBuilder("amqp.{$this->topic}.process")
            ->setSpanKind(SpanKind::KIND_CONSUMER)
            ->setAttribute('messaging.system', 'rabbitmq')
            ->setAttribute('messaging.operation', 'process')
            ->setAttribute('messaging.destination.name', $this->topic)
            ->setAttribute('messaging.message.id', $message->getMessageId() ?? 'unknown');

        // Link to parent trace if available (async trace continuation)
        if ($parentSpanContext !== null && $parentSpanContext->isValid()) {
            $linkedContext = $this->propagator->createLinkedContext($parentSpanContext);
            $spanBuilder->setParent($linkedContext);
            $spanBuilder->setAttribute('messaging.parent_trace_id', $parentSpanContext->getTraceId());
        }

        $span = $spanBuilder->startSpan();
        $scope = $span->activate();

        try {
            $result = $this->innerProcessor->process($message, $context);

            // Map result to span status
            if ($result === self::ACK) {
                $span->setStatus(StatusCode::STATUS_OK);
                $span->setAttribute('messaging.result', 'ACK');
            } elseif ($result === self::REJECT) {
                $span->setStatus(StatusCode::STATUS_ERROR, 'Message rejected');
                $span->setAttribute('messaging.result', 'REJECT');
            } elseif ($result === self::REQUEUE) {
                $span->setStatus(StatusCode::STATUS_OK);
                $span->setAttribute('messaging.result', 'REQUEUE');
            }

            return $result;
        } catch (\Throwable $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            $span->setAttribute('messaging.result', 'EXCEPTION');
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    /**
     * Get the topic this processor handles.
     */
    public function getTopic(): string
    {
        return $this->topic;
    }
}
