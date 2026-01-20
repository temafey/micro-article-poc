<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\OpenTelemetry\CircuitBreaker;

use Ackintosh\Ganesha;
use OpenTelemetry\SDK\Common\Future\CancellationInterface;
use OpenTelemetry\SDK\Common\Future\CompletedFuture;
use OpenTelemetry\SDK\Common\Future\FutureInterface;
use OpenTelemetry\SDK\Trace\SpanDataInterface;
use OpenTelemetry\SDK\Trace\SpanExporterInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Resilient span exporter with circuit breaker protection.
 *
 * Wraps an inner span exporter with a circuit breaker to prevent
 * request blocking when the OTEL collector is unavailable.
 *
 * Circuit breaker states:
 * - CLOSED: Normal operation, exports are attempted
 * - OPEN: Collector unavailable, exports are skipped (fast fail)
 * - HALF-OPEN: Testing if collector recovered
 *
 * Part of TASK-15: OpenTelemetry Circuit Breaker Resilience
 */
final class ResilientSpanExporter implements SpanExporterInterface
{
    private const SERVICE_NAME = 'otel-collector-traces';

    public function __construct(
        private readonly SpanExporterInterface $innerExporter,
        private readonly Ganesha $circuitBreaker,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Export spans with circuit breaker protection.
     *
     * When the circuit is OPEN, spans are dropped immediately without
     * attempting to connect to the collector, preventing request blocking.
     *
     * @param iterable<SpanDataInterface> $batch
     */
    public function export(iterable $batch, ?CancellationInterface $cancellation = null): FutureInterface
    {
        $logger = $this->logger ?? new NullLogger();
        $spanCount = is_countable($batch) ? count($batch) : iterator_count($batch);

        // Check circuit breaker state
        if (!$this->circuitBreaker->isAvailable(self::SERVICE_NAME)) {
            $logger->debug('OTEL span export skipped: circuit breaker OPEN', [
                'service' => self::SERVICE_NAME,
                'span_count' => $spanCount,
            ]);
            return new CompletedFuture(false);
        }

        try {
            $future = $this->innerExporter->export($batch, $cancellation);

            // For synchronous evaluation
            $result = $future->await();

            if ($result) {
                $this->circuitBreaker->success(self::SERVICE_NAME);
            } else {
                $this->circuitBreaker->failure(self::SERVICE_NAME);
                $logger->warning('OTEL span export failed', [
                    'service' => self::SERVICE_NAME,
                    'span_count' => $spanCount,
                ]);
            }

            return new CompletedFuture($result);
        } catch (\Throwable $e) {
            $this->circuitBreaker->failure(self::SERVICE_NAME);
            $logger->warning('OTEL span export exception: {message}', [
                'message' => $e->getMessage(),
                'service' => self::SERVICE_NAME,
                'span_count' => $spanCount,
                'exception_class' => $e::class,
            ]);
            return new CompletedFuture(false);
        }
    }

    /**
     * Shutdown the inner exporter.
     */
    public function shutdown(?CancellationInterface $cancellation = null): bool
    {
        $logger = $this->logger ?? new NullLogger();

        try {
            return $this->innerExporter->shutdown($cancellation);
        } catch (\Throwable $e) {
            $logger->warning('OTEL span exporter shutdown failed: {message}', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Force flush with circuit breaker protection.
     *
     * Skips flush when circuit is OPEN to prevent blocking.
     */
    public function forceFlush(?CancellationInterface $cancellation = null): bool
    {
        $logger = $this->logger ?? new NullLogger();

        if (!$this->circuitBreaker->isAvailable(self::SERVICE_NAME)) {
            $logger->debug('OTEL span exporter flush skipped: circuit breaker OPEN');
            return true; // Skip flush when circuit is open
        }

        try {
            return $this->innerExporter->forceFlush($cancellation);
        } catch (\Throwable $e) {
            $this->circuitBreaker->failure(self::SERVICE_NAME);
            $logger->warning('OTEL span exporter flush failed: {message}', [
                'message' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
