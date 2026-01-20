<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Observability\EventBus;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventBus;
use Broadway\EventHandling\EventListener;
use Micro\Component\Common\Infrastructure\Observability\Service\TracerFactory;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;

/**
 * OpenTelemetry tracing decorator for Broadway EventBus.
 *
 * Creates producer spans for each domain event being published,
 * providing visibility into event flow through the system.
 *
 * Span naming convention:
 * - event.publish.{EventName} (e.g., event.publish.ArticleCreatedEvent)
 *
 * @see https://opentelemetry.io/docs/concepts/semantic-conventions/
 */
final readonly class TracingEventBusDecorator implements EventBus
{
    public function __construct(
        private EventBus $innerBus,
        private TracerFactory $tracerFactory,
    ) {
    }

    /**
     * Publish domain events with tracing spans.
     *
     * Each event in the stream gets its own span with:
     * - Event name and class
     * - Aggregate ID
     * - Playhead (event version)
     * - Recorded timestamp
     */
    public function publish(DomainEventStream $domainMessages): void
    {
        $tracer = $this->tracerFactory->getTracer();

        /** @var DomainMessage $domainMessage */
        foreach ($domainMessages as $domainMessage) {
            $payload = $domainMessage->getPayload();
            $eventClass = $payload::class;
            $eventName = (new \ReflectionClass($payload))->getShortName();

            $span = $tracer->spanBuilder("event.publish.{$eventName}")
                ->setSpanKind(SpanKind::KIND_PRODUCER)
                ->setAttribute('messaging.system', 'broadway')
                ->setAttribute('messaging.operation', 'publish')
                ->setAttribute('event.name', $eventName)
                ->setAttribute('event.class', $eventClass)
                ->setAttribute('event.aggregate_id', (string) $domainMessage->getId())
                ->setAttribute('event.playhead', $domainMessage->getPlayhead())
                ->setAttribute('event.recorded_on', $domainMessage->getRecordedOn()->toString())
                ->startSpan();

            $span->setStatus(StatusCode::STATUS_OK);
            $span->end();
        }

        $this->innerBus->publish($domainMessages);
    }

    /**
     * Subscribe an event listener to the bus.
     *
     * Delegates to the inner bus without tracing as subscription
     * is a configuration-time operation, not a runtime operation.
     */
    public function subscribe(EventListener $eventListener): void
    {
        $this->innerBus->subscribe($eventListener);
    }
}
