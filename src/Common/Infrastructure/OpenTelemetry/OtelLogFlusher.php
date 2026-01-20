<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\OpenTelemetry;

use OpenTelemetry\API\Logs\LoggerProviderInterface;
use OpenTelemetry\SDK\Logs\LoggerProviderInterface as SdkLoggerProviderInterface;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

/**
 * Flushes OpenTelemetry logs at request/console termination.
 *
 * The OTEL SDK batches log records for efficiency. This listener ensures
 * all pending logs are exported before the process ends.
 *
 * Part of TASK-035: OpenTelemetry Integration
 */
final readonly class OtelLogFlusher
{
    public function __construct(
        private LoggerProviderInterface $loggerProvider,
    ) {
    }

    /**
     * Flush logs after HTTP request completes.
     */
    public function onKernelTerminate(TerminateEvent $event): void
    {
        $this->flush();
    }

    /**
     * Flush logs after console command completes.
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        $this->flush();
    }

    /**
     * Force flush all pending log records to the exporter.
     */
    private function flush(): void
    {
        // The SDK LoggerProvider has forceFlush(), but the API interface doesn't expose it.
        // We need to check if the provider is the SDK implementation.
        if ($this->loggerProvider instanceof SdkLoggerProviderInterface) {
            $this->loggerProvider->forceFlush();
        }
    }
}
