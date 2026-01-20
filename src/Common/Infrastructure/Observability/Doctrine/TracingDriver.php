<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Observability\Doctrine;

use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Micro\Component\Common\Infrastructure\Observability\Service\TracerFactory;
use SensitiveParameter;

/**
 * Tracing driver that wraps connections with TracingConnection.
 *
 * @see ADR-014: Observability Stack Modernization - Phase 3.2
 */
final class TracingDriver extends AbstractDriverMiddleware
{
    public function __construct(
        \Doctrine\DBAL\Driver $driver,
        private readonly TracerFactory $tracerFactory,
    ) {
        parent::__construct($driver);
    }

    public function connect(
        #[SensitiveParameter]
        array $params,
    ): DriverConnection {
        $tracer = $this->tracerFactory->getTracer();

        $span = $tracer->spanBuilder('db.connect')
            ->setAttribute('db.system', 'postgresql')
            ->setAttribute('db.name', $params['dbname'] ?? 'unknown')
            ->setAttribute('server.address', $params['host'] ?? 'unknown')
            ->setAttribute('server.port', $params['port'] ?? 5432)
            ->startSpan();

        $scope = $span->activate();

        try {
            $connection = parent::connect($params);
            $span->setStatus(\OpenTelemetry\API\Trace\StatusCode::STATUS_OK);

            return new TracingConnection(
                $connection,
                $this->tracerFactory,
                $params['dbname'] ?? 'unknown',
            );
        } catch (\Throwable $e) {
            $span->recordException($e);
            $span->setStatus(\OpenTelemetry\API\Trace\StatusCode::STATUS_ERROR, $e->getMessage());
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }
}
