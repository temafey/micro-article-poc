<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Observability\Service;

use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\TracerInterface;

/**
 * Factory for creating OpenTelemetry Tracer instances.
 *
 * Provides a centralized way to obtain tracers configured with service metadata.
 * Uses the global TracerProvider configured via environment variables.
 *
 * @see https://opentelemetry.io/docs/instrumentation/php/
 */
final readonly class TracerFactory implements TracerFactoryInterface
{
    public function __construct(
        private string $serviceName = 'article-microservice',
        private string $serviceVersion = '1.0.0',
    ) {
    }

    /**
     * Get a tracer instance for instrumenting application code.
     *
     * The returned tracer is configured with the service name and version,
     * allowing proper identification of spans in the tracing backend.
     */
    public function getTracer(): TracerInterface
    {
        return Globals::tracerProvider()->getTracer(
            $this->serviceName,
            $this->serviceVersion
        );
    }
}
