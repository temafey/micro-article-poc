<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Observability\Traits;

use Micro\Component\Common\Infrastructure\Observability\Service\TracerFactory;
use OpenTelemetry\API\Trace\SpanInterface;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\ScopeInterface;

/**
 * Trait for adding OpenTelemetry tracing to Broadway Projectors.
 *
 * Provides methods to create and manage spans for projector event handling,
 * enabling visibility into read model updates triggered by domain events.
 * Projectors using this trait should inject TracerFactory and call trace methods
 * around their apply logic.
 *
 * Span naming convention:
 * - projector.{ProjectorName}.{EventName} (e.g., projector.ArticleProjector.ArticleCreatedEvent)
 *
 * Usage:
 * ```php
 * use TracedProjectorTrait;
 *
 * protected function applyArticleCreatedEvent(ArticleCreatedEvent $event): void
 * {
 *     $this->traceProjection($event, function() use ($event) {
 *         // your projector logic here
 *         $this->repository->save($readModel);
 *     });
 * }
 * ```
 */
trait TracedProjectorTrait
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
     * Trace a projector event application.
     *
     * @template T
     * @param object $event The domain event being projected
     * @param callable(): T $projection The projection logic
     * @return T The result from the projection
     * @throws \Throwable Re-throws any exception after recording it
     */
    protected function traceProjection(object $event, callable $projection): mixed
    {
        if ($this->tracerFactory === null) {
            return $projection();
        }

        $projectorName = $this->getProjectorShortName();
        $eventName = (new \ReflectionClass($event))->getShortName();
        $tracer = $this->tracerFactory->getTracer();

        $span = $tracer->spanBuilder("projector.{$projectorName}.{$eventName}")
            ->setSpanKind(SpanKind::KIND_CONSUMER)
            ->setAttribute('projector.name', $projectorName)
            ->setAttribute('projector.class', static::class)
            ->setAttribute('event.name', $eventName)
            ->setAttribute('event.class', $event::class)
            ->setAttribute('messaging.system', 'broadway')
            ->setAttribute('messaging.operation', 'process')
            ->startSpan();

        $scope = $span->activate();

        try {
            $result = $projection();
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
     * Start a custom span for a projector operation.
     *
     * @return array{span: SpanInterface, scope: ScopeInterface}
     */
    protected function startProjectorSpan(string $operation, array $attributes = []): array
    {
        if ($this->tracerFactory === null) {
            throw new \RuntimeException('TracerFactory not set. Call setTracerFactory() first.');
        }

        $projectorName = $this->getProjectorShortName();
        $tracer = $this->tracerFactory->getTracer();

        $spanBuilder = $tracer->spanBuilder("projector.{$projectorName}.{$operation}")
            ->setSpanKind(SpanKind::KIND_CONSUMER)
            ->setAttribute('projector.name', $projectorName)
            ->setAttribute('projector.class', static::class)
            ->setAttribute('projector.operation', $operation);

        foreach ($attributes as $key => $value) {
            $spanBuilder->setAttribute($key, $value);
        }

        $span = $spanBuilder->startSpan();
        $scope = $span->activate();

        return ['span' => $span, 'scope' => $scope];
    }

    /**
     * End a projector span with success status.
     *
     * @param array{span: SpanInterface, scope: ScopeInterface} $spanData
     */
    protected function endProjectorSpan(array $spanData): void
    {
        $spanData['span']->setStatus(StatusCode::STATUS_OK);
        $spanData['scope']->detach();
        $spanData['span']->end();
    }

    /**
     * End a projector span with error status.
     *
     * @param array{span: SpanInterface, scope: ScopeInterface} $spanData
     */
    protected function endProjectorSpanWithError(array $spanData, \Throwable $exception): void
    {
        $spanData['span']->recordException($exception);
        $spanData['span']->setStatus(StatusCode::STATUS_ERROR, $exception->getMessage());
        $spanData['scope']->detach();
        $spanData['span']->end();
    }

    /**
     * Get short class name for projector identification.
     */
    private function getProjectorShortName(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }
}
