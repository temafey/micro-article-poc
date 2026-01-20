<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Observability\Traits;

use Micro\Component\Common\Infrastructure\Observability\Service\TracerFactory;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\ScopeInterface;

/**
 * Trait for adding OpenTelemetry tracing to Broadway Sagas.
 *
 * Provides methods to create and manage spans for saga workflow operations.
 * Sagas using this trait should inject TracerFactory and call trace methods
 * around their event handling logic.
 *
 * Span naming convention:
 * - saga.{SagaName}.{EventName} (e.g., saga.ArticleCreationWorkflow.ArticleCreatedEvent)
 *
 * Usage:
 * ```php
 * use TracedSagaTrait;
 *
 * public function handleArticleCreatedEvent(State $state, ArticleCreatedEvent $event): State
 * {
 *     return $this->traceEventHandler($event, function() use ($state, $event) {
 *         // your saga logic here
 *         return $state;
 *     });
 * }
 * ```
 */
trait TracedSagaTrait
{
    protected ?TracerFactory $tracerFactory = null;

    /**
     * Set the tracer factory for tracing operations.
     * Call this method via setter injection or in constructor.
     */
    public function setTracerFactory(TracerFactory $tracerFactory): void
    {
        $this->tracerFactory = $tracerFactory;
    }

    /**
     * Trace a saga event handler execution.
     *
     * @template T
     * @param object $event The domain event being handled
     * @param callable(): T $handler The event handler logic
     * @return T The result from the handler
     * @throws \Throwable Re-throws any exception after recording it
     */
    protected function traceEventHandler(object $event, callable $handler): mixed
    {
        if ($this->tracerFactory === null) {
            return $handler();
        }

        $sagaName = $this->getSagaShortName();
        $eventName = (new \ReflectionClass($event))->getShortName();
        $tracer = $this->tracerFactory->getTracer();

        $span = $tracer->spanBuilder("saga.{$sagaName}.{$eventName}")
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->setAttribute('saga.name', $sagaName)
            ->setAttribute('saga.class', static::class)
            ->setAttribute('event.name', $eventName)
            ->setAttribute('event.class', $event::class)
            ->startSpan();

        $scope = $span->activate();

        try {
            $result = $handler();
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
     * Start a custom span for a saga operation.
     *
     * @return array{span: SpanInterface, scope: ScopeInterface}
     */
    protected function startSagaSpan(string $operation, array $attributes = []): array
    {
        if ($this->tracerFactory === null) {
            throw new \RuntimeException('TracerFactory not set. Call setTracerFactory() first.');
        }

        $sagaName = $this->getSagaShortName();
        $tracer = $this->tracerFactory->getTracer();

        $spanBuilder = $tracer->spanBuilder("saga.{$sagaName}.{$operation}")
            ->setSpanKind(SpanKind::KIND_INTERNAL)
            ->setAttribute('saga.name', $sagaName)
            ->setAttribute('saga.class', static::class)
            ->setAttribute('saga.operation', $operation);

        foreach ($attributes as $key => $value) {
            $spanBuilder->setAttribute($key, $value);
        }

        $span = $spanBuilder->startSpan();
        $scope = $span->activate();

        return ['span' => $span, 'scope' => $scope];
    }

    /**
     * End a saga span with success status.
     *
     * @param array{span: SpanInterface, scope: ScopeInterface} $spanData
     */
    protected function endSagaSpan(array $spanData): void
    {
        $spanData['span']->setStatus(StatusCode::STATUS_OK);
        $spanData['scope']->detach();
        $spanData['span']->end();
    }

    /**
     * End a saga span with error status.
     *
     * @param array{span: SpanInterface, scope: ScopeInterface} $spanData
     */
    protected function endSagaSpanWithError(array $spanData, \Throwable $exception): void
    {
        $spanData['span']->recordException($exception);
        $spanData['span']->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
        $spanData['scope']->detach();
        $spanData['span']->end();
    }

    /**
     * Get short class name for saga identification.
     */
    private function getSagaShortName(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}
