<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Observability\Middleware;

use League\Tactician\Middleware;
use Micro\Component\Common\Infrastructure\Observability\Service\TracerFactory;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;

/**
 * OpenTelemetry tracing middleware for Tactician Command/Query Bus.
 *
 * Creates child spans for each command/query execution, providing
 * distributed tracing visibility for CQRS operations.
 *
 * Span naming convention:
 * - Commands: command.{ClassName} (e.g., command.ArticleCreateCommand)
 * - Queries: query.{ClassName} (e.g., query.FetchOneArticleQuery)
 *
 * @see https://opentelemetry.io/docs/concepts/semantic-conventions/
 */
final readonly class OpenTelemetryCommandMiddleware implements Middleware
{
    public function __construct(
        private TracerFactory $tracerFactory,
    ) {
    }

    /**
     * @param object $command The command or query object being executed
     * @param callable $next The next middleware in the chain
     * @return mixed The result from the command handler
     * @throws \Throwable Re-throws any exception after recording it
     */
    public function execute($command, callable $next): mixed
    {
        $commandClass = $command::class;
        $commandName = (new \ReflectionClass($command))->getShortName();
        $tracer = $this->tracerFactory->getTracer();

        // Determine if this is a command or query based on naming convention
        $operationType = str_contains($commandClass, 'Query') ? 'query' : 'command';

        $span = $tracer->spanBuilder("{$operationType}.{$commandName}")
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->setAttribute('messaging.system', 'tactician')
            ->setAttribute('messaging.operation', 'process')
            ->setAttribute("{$operationType}.name", $commandName)
            ->setAttribute("{$operationType}.class", $commandClass)
            ->startSpan();

        $scope = $span->activate();

        try {
            $result = $next($command);
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
}
