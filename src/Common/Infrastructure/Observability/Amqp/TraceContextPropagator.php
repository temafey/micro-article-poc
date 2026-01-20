<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Observability\Amqp;

use Interop\Queue\Message;
use OpenTelemetry\API\Trace\SpanContextInterface;
use OpenTelemetry\API\Trace\SpanContextValidator;
use OpenTelemetry\API\Trace\TraceFlags;
use OpenTelemetry\Context\Context;
use OpenTelemetry\SDK\Trace\SpanContext;

/**
 * W3C Trace Context propagator for AMQP messages.
 *
 * Handles injection and extraction of trace context following W3C Trace Context
 * specification (https://www.w3.org/TR/trace-context/).
 *
 * Format: traceparent = 00-{traceId}-{spanId}-{flags}
 * Example: 00-0af7651916cd43dd8448eb211c80319c-b7ad6b7169203331-01
 *
 * @see ADR-014: Observability Stack Modernization - Phase 3.4
 */
final class TraceContextPropagator
{
    public const TRACEPARENT_HEADER = 'traceparent';
    public const TRACESTATE_HEADER = 'tracestate';

    private const VERSION = '00';
    private const TRACEPARENT_PATTERN = '/^00-[0-9a-f]{32}-[0-9a-f]{16}-[0-9a-f]{2}$/';

    /**
     * Inject trace context into message headers.
     *
     * @param Message $message The AMQP message to inject context into
     * @param SpanContextInterface $spanContext The span context to inject
     */
    public function inject(Message $message, SpanContextInterface $spanContext): void
    {
        if (!$spanContext->isValid()) {
            return;
        }

        $traceparent = sprintf(
            '%s-%s-%s-%s',
            self::VERSION,
            $spanContext->getTraceId(),
            $spanContext->getSpanId(),
            $spanContext->isSampled() ? '01' : '00',
        );

        $message->setHeader(self::TRACEPARENT_HEADER, $traceparent);

        // Also propagate tracestate if available
        $traceState = $spanContext->getTraceState();
        if ($traceState !== null && (string) $traceState !== '') {
            $message->setHeader(self::TRACESTATE_HEADER, (string) $traceState);
        }
    }

    /**
     * Extract trace context from message headers.
     *
     * @param Message $message The AMQP message to extract context from
     *
     * @return SpanContextInterface|null The extracted span context, or null if not found/invalid
     */
    public function extract(Message $message): ?SpanContextInterface
    {
        $traceparent = $message->getHeader(self::TRACEPARENT_HEADER);

        if ($traceparent === null || !is_string($traceparent)) {
            return null;
        }

        if (!preg_match(self::TRACEPARENT_PATTERN, $traceparent)) {
            return null;
        }

        $parts = explode('-', $traceparent);
        if (count($parts) !== 4) {
            return null;
        }

        [$version, $traceId, $spanId, $traceFlags] = $parts;

        // We only support version 00
        if ($version !== self::VERSION) {
            return null;
        }

        // Validate trace ID and span ID
        if (!SpanContextValidator::isValidTraceId($traceId)) {
            return null;
        }

        if (!SpanContextValidator::isValidSpanId($spanId)) {
            return null;
        }

        // Parse flags (only sampled flag is defined in spec)
        $sampled = $traceFlags === '01';

        return SpanContext::createFromRemoteParent(
            $traceId,
            $spanId,
            $sampled ? TraceFlags::SAMPLED : TraceFlags::DEFAULT,
        );
    }

    /**
     * Create a linked context from extracted span context.
     *
     * Use this when you want to link to the parent trace but create a new trace root.
     *
     * @param SpanContextInterface $parentContext The parent span context to link
     *
     * @return Context Context with parent span for linking
     */
    public function createLinkedContext(SpanContextInterface $parentContext): Context
    {
        return Context::getCurrent()->withContextValue(
            new \OpenTelemetry\API\Trace\NonRecordingSpan($parentContext),
        );
    }
}
