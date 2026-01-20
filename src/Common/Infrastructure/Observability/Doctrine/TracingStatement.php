<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Observability\Doctrine;

use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result as ResultInterface;
use Doctrine\DBAL\Driver\Statement as StatementInterface;
use Doctrine\DBAL\ParameterType;
use Micro\Component\Common\Infrastructure\Observability\Service\TracerFactory;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;

/**
 * Tracing statement that instruments prepared statement execution with OpenTelemetry spans.
 *
 * Traces:
 * - bindValue() - Parameter binding (counts and types only, no values for security)
 * - execute() - Statement execution with timing
 *
 * @see ADR-014: Observability Stack Modernization - Phase 3.2
 */
final class TracingStatement extends AbstractStatementMiddleware
{
    /** @var array<int|string, ParameterType> */
    private array $paramTypes = [];

    private int $paramCount = 0;

    public function __construct(
        StatementInterface $statement,
        private readonly TracerFactory $tracerFactory,
        private readonly string $sql,
        private readonly string $dbName,
    ) {
        parent::__construct($statement);
    }

    public function bindValue(int|string $param, mixed $value, ParameterType $type): void
    {
        $this->paramTypes[$param] = $type;
        $this->paramCount++;

        parent::bindValue($param, $value, $type);
    }

    public function execute(): ResultInterface
    {
        $tracer = $this->tracerFactory->getTracer();
        $operation = $this->extractOperation($this->sql);

        $span = $tracer->spanBuilder("db.{$operation}")
            ->setSpanKind(SpanKind::KIND_CLIENT)
            ->setAttribute('db.system', 'postgresql')
            ->setAttribute('db.name', $this->dbName)
            ->setAttribute('db.operation', $operation)
            ->setAttribute('db.statement', $this->sanitizeSql($this->sql))
            ->setAttribute('db.statement.param_count', $this->paramCount)
            ->startSpan();

        // Add parameter types as attributes (no values for security)
        if (!empty($this->paramTypes)) {
            $typeNames = array_map(
                static fn(ParameterType $type): string => $type->name,
                $this->paramTypes,
            );
            $span->setAttribute('db.statement.param_types', implode(',', $typeNames));
        }

        $scope = $span->activate();

        try {
            $result = parent::execute();
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
     * Sanitize SQL by removing sensitive parameter values.
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
