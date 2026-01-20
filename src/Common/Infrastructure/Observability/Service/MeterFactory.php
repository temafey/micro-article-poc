<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Observability\Service;

use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Metrics\MeterInterface;

/**
 * Factory for creating OpenTelemetry Meter instances.
 *
 * Provides a centralized way to obtain meters configured with service metadata.
 * Uses the global MeterProvider configured via environment variables.
 *
 * @see https://opentelemetry.io/docs/instrumentation/php/
 * @see ADR-006: Transactional Outbox Pattern
 * @see TASK-14.5: Monitoring & Cleanup
 */
final readonly class MeterFactory implements MeterFactoryInterface
{
    public function __construct(
        private string $serviceName = 'article-microservice',
        private string $serviceVersion = '1.0.0',
    ) {
    }

    /**
     * Get a meter instance for instrumenting application code.
     *
     * The returned meter is configured with the service name and version,
     * allowing proper identification of metrics in the monitoring backend.
     */
    public function getMeter(): MeterInterface
    {
        return Globals::meterProvider()->getMeter(
            $this->serviceName,
            $this->serviceVersion
        );
    }
}
