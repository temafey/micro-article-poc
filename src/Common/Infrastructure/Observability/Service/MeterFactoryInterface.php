<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Observability\Service;

use OpenTelemetry\API\Metrics\MeterInterface;

/**
 * Interface for meter factory implementations.
 *
 * Provides abstraction for creating OpenTelemetry Meter instances,
 * enabling dependency injection and testability.
 *
 * @see MeterFactory
 * @see ADR-006: Transactional Outbox Pattern
 */
interface MeterFactoryInterface
{
    /**
     * Get a meter instance for instrumenting application code.
     */
    public function getMeter(): MeterInterface;
}
