<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Observability\Doctrine;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use Micro\Component\Common\Infrastructure\Observability\Service\TracerFactory;

/**
 * Doctrine DBAL Middleware for OpenTelemetry distributed tracing.
 *
 * Instruments all database operations with trace spans:
 * - SQL queries (prepare, query, exec)
 * - Transactions (begin, commit, rollback)
 * - Parameter binding
 *
 * @see ADR-014: Observability Stack Modernization - Phase 3.2
 */
final readonly class TracingDoctrineMiddleware implements Middleware
{
    public function __construct(
        private TracerFactory $tracerFactory,
    ) {
    }

    public function wrap(Driver $driver): Driver
    {
        return new TracingDriver($driver, $this->tracerFactory);
    }
}
