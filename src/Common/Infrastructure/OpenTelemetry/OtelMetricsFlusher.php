<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\OpenTelemetry;

use OpenTelemetry\API\Globals;
use OpenTelemetry\SDK\Metrics\MeterProviderInterface as SdkMeterProviderInterface;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

/**
 * Flushes OpenTelemetry metrics at request/console termination.
 *
 * The OTEL SDK batches metric records for efficiency. This listener ensures
 * all pending metrics are exported before the process ends.
 *
 * This is especially important for CLI commands which run briefly and exit
 * before the periodic exporter can flush accumulated metrics.
 *
 * @see ADR-006: Transactional Outbox Pattern
 * @see TASK-14.5: Monitoring & Cleanup
 */
final class OtelMetricsFlusher
{
    /**
     * Flush metrics after HTTP request completes.
     */
    public function onKernelTerminate(TerminateEvent $event): void
    {
        $this->flush();
    }

    /**
     * Flush metrics after console command completes.
     */
    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        $this->flush();
    }

    /**
     * Force flush all pending metric records to the exporter.
     */
    private function flush(): void
    {
        $meterProvider = Globals::meterProvider();

        // The SDK MeterProvider has forceFlush(), but the API interface doesn't expose it.
        // We need to check if the provider is the SDK implementation.
        if ($meterProvider instanceof SdkMeterProviderInterface) {
            $meterProvider->forceFlush();
        }
    }
}
