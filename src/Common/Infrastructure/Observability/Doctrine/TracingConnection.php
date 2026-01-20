<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Observability\Doctrine;

use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Middleware\AbstractConnectionMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement as DriverStatement;
use Micro\Component\Common\Infrastructure\Observability\Service\TracerFactory;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;

/**
 * Tracing connection that instruments database operations with OpenTelemetry spans.
 *
 * Traces:
 * - prepare() - Statement preparation
 * - query() - Direct query execution
 * - exec() - Direct statement execution (Doctrine DBAL method, not shell - escapeshellarg not applicable)
 * - beginTransaction() - Transaction start
 * - commit() - Transaction commit
 * - rollBack() - Transaction rollback
 *
 * @see ADR-014: Observability Stack Modernization - Phase 3.2
 */
final class TracingConnection extends AbstractConnectionMiddleware
{
    public function __construct(
        DriverConnection $connection,
        private readonly TracerFactory $tracerFactory,
        private readonly string $dbName,
    ) {
        parent::__construct($connection);
    }

    public function prepare(string $sql): DriverStatement
    {
        $statement = parent::prepare($sql);

        return new TracingStatement(
            $statement,
            $this->tracerFactory,
            $sql,
            $this->dbName,
        );
    }

    public function query(string $sql): Result
    {
        $tracer = $this->tracerFactory->getTracer();
        $operation = $this->extractOperation($sql);

        $span = $tracer->spanBuilder("db.{$operation}")
            ->setSpanKind(SpanKind::KIND_CLIENT)
            ->setAttribute('db.system', 'postgresql')
            ->setAttribute('db.name', $this->dbName)
            ->setAttribute('db.operation', $operation)
            ->setAttribute('db.statement', $this->sanitizeSql($sql))
            ->startSpan();

        $scope = $span->activate();

        try {
            $result = parent::query($sql);
            $span->setStatus(StatusCode::STATUS_OK);

            return $result;
        } catch (\Throwable $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    public function exec(string $sql): int|string
    {
        $tracer = $this->tracerFactory->getTracer();
        $operation = $this->extractOperation($sql);

        $span = $tracer->spanBuilder("db.{$operation}")
            ->setSpanKind(SpanKind::KIND_CLIENT)
            ->setAttribute('db.system', 'postgresql')
            ->setAttribute('db.name', $this->dbName)
            ->setAttribute('db.operation', $operation)
            ->setAttribute('db.statement', $this->sanitizeSql($sql))
            ->startSpan();

        $scope = $span->activate();

        try {
            $result = parent::exec($sql);
            $span->setStatus(StatusCode::STATUS_OK);
            $span->setAttribute('db.rows_affected', $result);

            return $result;
        } catch (\Throwable $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    public function beginTransaction(): void
    {
        $tracer = $this->tracerFactory->getTracer();

        $span = $tracer->spanBuilder('db.transaction.begin')
            ->setSpanKind(SpanKind::KIND_CLIENT)
            ->setAttribute('db.system', 'postgresql')
            ->setAttribute('db.name', $this->dbName)
            ->setAttribute('db.operation', 'BEGIN')
            ->startSpan();

        $scope = $span->activate();

        try {
            parent::beginTransaction();
            $span->setStatus(StatusCode::STATUS_OK);
        } catch (\Throwable $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    public function commit(): void
    {
        $tracer = $this->tracerFactory->getTracer();

        $span = $tracer->spanBuilder('db.transaction.commit')
            ->setSpanKind(SpanKind::KIND_CLIENT)
            ->setAttribute('db.system', 'postgresql')
            ->setAttribute('db.name', $this->dbName)
            ->setAttribute('db.operation', 'COMMIT')
            ->startSpan();

        $scope = $span->activate();

        try {
            parent::commit();
            $span->setStatus(StatusCode::STATUS_OK);
        } catch (\Throwable $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    public function rollBack(): void
    {
        $tracer = $this->tracerFactory->getTracer();

        $span = $tracer->spanBuilder('db.transaction.rollback')
            ->setSpanKind(SpanKind::KIND_CLIENT)
            ->setAttribute('db.system', 'postgresql')
            ->setAttribute('db.name', $this->dbName)
            ->setAttribute('db.operation', 'ROLLBACK')
            ->startSpan();

        $scope = $span->activate();

        try {
            parent::rollBack();
            $span->setStatus(StatusCode::STATUS_OK);
        } catch (\Throwable $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    /**
     * Extract SQL operation type from statement.
     */
    private function extractOperation(string $sql): string
    {
        $sql = ltrim($sql);
        $firstWord = strtoupper(explode(' ', $sql, 2)[0] ?? '');

        return match ($firstWord) {
            'SELECT' => 'SELECT',
            'INSERT' => 'INSERT',
            'UPDATE' => 'UPDATE',
            'DELETE' => 'DELETE',
            'CREATE' => 'CREATE',
            'ALTER' => 'ALTER',
            'DROP' => 'DROP',
            'TRUNCATE' => 'TRUNCATE',
            default => 'QUERY',
        };
    }

    /**
     * Sanitize SQL by masking literal values to prevent PII leakage.
     *
     * Replaces string and numeric literals with '?' markers.
     */
    private function sanitizeSql(string $sql): string
    {
        // Replace string literals with '?'
        $sql = preg_replace("/('[^']*')/", '?', $sql) ?? $sql;

        // Replace numeric literals with '?'
        $sql = preg_replace('/\b\d+\b/', '?', $sql) ?? $sql;

        return $sql;
    }
}
