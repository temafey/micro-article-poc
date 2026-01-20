<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Observability\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use OpenTelemetry\API\Trace\Span;

/**
 * Monolog processor that adds OpenTelemetry trace context to log records.
 *
 * Enriches log entries with trace_id and span_id, enabling log-trace
 * correlation in Grafana (Tempo + Loki integration).
 *
 * Usage in Loki:
 * - Filter by trace_id to see all logs for a specific trace
 * - Click trace_id to jump directly to Tempo trace view
 *
 * @see https://opentelemetry.io/docs/specs/otel/logs/
 */
final class TraceContextProcessor implements ProcessorInterface
{
    /**
     * Add trace context to log record.
     *
     * Extracts the current span context and adds:
     * - trace_id: 32-character hex string (W3C Trace Context)
     * - span_id: 16-character hex string
     * - trace_flags: Sampling decision (01 = sampled)
     */
    public function __invoke(LogRecord $record): LogRecord
    {
        $span = Span::getCurrent();
        $context = $span->getContext();

        // Skip if no valid trace context exists
        if (!$context->isValid()) {
            return $record;
        }

        return $record->with(extra: [
            ...$record->extra,
            'trace_id' => $context->getTraceId(),
            'span_id' => $context->getSpanId(),
            'trace_flags' => sprintf('%02x', $context->getTraceFlags()),
        ]);
    }
}
