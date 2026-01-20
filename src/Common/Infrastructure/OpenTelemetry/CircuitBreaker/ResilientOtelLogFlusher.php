<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\OpenTelemetry\CircuitBreaker;

use Ackintosh\Ganesha;
use OpenTelemetry\API\Logs\LoggerProviderInterface;
use OpenTelemetry\SDK\Logs\LoggerProviderInterface as SdkLoggerProviderInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

/**
 * Resilient OpenTelemetry log flusher with circuit breaker protection.
 *
 * Wraps OTEL flush operations with a circuit breaker to prevent request
 * blocking when the OTEL collector is unavailable. Failed flushes are
 * logged for observability.
 *
 * Circuit breaker states:
 * - CLOSED: Normal operation, flushes are attempted
 * - OPEN: Collector unavailable, flushes are skipped (fast fail)
 * - HALF-OPEN: Testing if collector recovered
 *
 * Part of TASK-15: OpenTelemetry Circuit Breaker Resilience
 */
final readonly class ResilientOtelLogFlusher
{
    private const SERVICE_NAME = 'otel-collector';

    public function __construct(
        private LoggerProviderInterface $loggerProvider,
        private Ganesha $circuitBreaker,
        private ?LoggerInterface $logger = null,
        private bool $enabled = true,
    ) {
    }

    /**
     * Flush logs after HTTP request completes.
     *
     * This is registered as a kernel.terminate event listener.
     */
    public function onKernelTerminate(TerminateEvent $event): void
    {
        $this->flush();
    }

    /**
     * Flush logs after console command completes.
     *
     * This is registered as a console.terminate event listener.
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        $this->flush();
    }

    /**
     * Force flush all pending log records with circuit breaker protection.
     *
     * When the circuit is OPEN, the flush is skipped to prevent blocking.
     * The circuit breaker tracks success/failure to determine when to
     * transition between states.
     */
    private function flush(): void
    {
        if (!$this->enabled) {
            return;
        }

        if (!$this->loggerProvider instanceof SdkLoggerProviderInterface) {
            return;
        }

        $logger = $this->logger ?? new NullLogger();

        // Check circuit breaker state
        if (!$this->circuitBreaker->isAvailable(self::SERVICE_NAME)) {
            $logger->debug('OTEL flush skipped: circuit breaker OPEN', [
                'service' => self::SERVICE_NAME,
            ]);
            return;
        }

        try {
            $this->loggerProvider->forceFlush();
            $this->circuitBreaker->success(self::SERVICE_NAME);
        } catch (\Throwable $e) {
            $this->circuitBreaker->failure(self::SERVICE_NAME);
            $logger->warning('OTEL flush failed: {message}', [
                'message' => $e->getMessage(),
                'service' => self::SERVICE_NAME,
                'exception_class' => $e::class,
            ]);
        }
    }
}
