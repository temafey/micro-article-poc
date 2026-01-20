<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Observability\Service;

use OpenTelemetry\API\Trace\TracerInterface;

/**
 * Interface for creating OpenTelemetry Tracer instances.
 *
 * Provides a centralized way to obtain tracers configured with service metadata.
 */
interface TracerFactoryInterface
{
    /**
     * Get a tracer instance for instrumenting application code.
     */
    public function getTracer(): TracerInterface;
}
