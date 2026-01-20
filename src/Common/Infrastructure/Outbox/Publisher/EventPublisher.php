<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Outbox\Publisher;

use Broadway\Serializer\Serializable;
use JsonException;
use Micro\Component\Common\Domain\Outbox\OutboxEntryInterface;
use Micro\Component\Common\Domain\Outbox\OutboxMessageType;
use MicroModule\EventQueue\Domain\EventHandling\QueueEventInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Publisher for domain events stored in the outbox.
 *
 * Deserializes event payloads and publishes through QueueEventInterface
 * to maintain compatibility with existing event processing infrastructure.
 *
 * @see ADR-006: Transactional Outbox Pattern
 * @see TASK-14.4: Background Publisher
 */
final class EventPublisher implements OutboxPublisherInterface
{
    /**
     * Registry of event classes for deserialization.
     *
     * @var array<string, class-string<Serializable>>
     */
    private array $eventClassMap = [];

    public function __construct(
        private readonly QueueEventInterface $queueEventProducer,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * Register an event class for deserialization.
     *
     * @param string $eventType The event type identifier
     * @param class-string<Serializable> $eventClass The event class FQCN
     */
    public function registerEventClass(string $eventType, string $eventClass): void
    {
        $this->eventClassMap[$eventType] = $eventClass;
    }

    /**
     * Register multiple event classes at once.
     *
     * @param array<string, class-string<Serializable>> $classMap Map of event type to class name
     */
    public function registerEventClasses(array $classMap): void
    {
        foreach ($classMap as $eventType => $eventClass) {
            $this->registerEventClass($eventType, $eventClass);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function publish(OutboxEntryInterface $entry): void
    {
        $eventType = $entry->getEventType();
        $payloadJson = $entry->getEventPayload();

        try {
            /** @var array<string, mixed> $payload */
            $payload = json_decode($payloadJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw OutboxPublishException::deserializationFailed(
                $eventType,
                'Invalid JSON payload: ' . $e->getMessage(),
            );
        }

        // Extract the inner event payload from Broadway DomainMessage envelope
        // Structure: {uuid, payload: {class, payload: {actual_event_data}}, metadata, playhead, recorded_on}
        $eventPayload = $this->extractEventPayload($payload, $eventType);

        // Resolve event class
        $eventClass = $this->resolveEventClass($eventType);

        // Deserialize the event using Broadway Serializable interface
        try {
            /** @var Serializable $event */
            $event = $eventClass::deserialize($eventPayload);
        } catch (\Throwable $e) {
            throw OutboxPublishException::deserializationFailed(
                $eventType,
                $e->getMessage(),
            );
        }

        // Publish to queue
        $this->queueEventProducer->publishEventToQueue($event);

        $this->logger->debug('Event published from outbox', [
            'message_id' => $entry->getId(),
            'event_type' => $eventType,
            'event_class' => $eventClass,
            'topic' => $entry->getTopic(),
            'aggregate_id' => $entry->getAggregateId(),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function supports(string $messageType): bool
    {
        return $messageType === OutboxMessageType::EVENT->value;
    }

    /**
     * Resolve the event class for deserialization.
     *
     * @return class-string<Serializable>
     *
     * @throws OutboxPublishException If event class cannot be resolved
     */
    private function resolveEventClass(string $eventType): string
    {
        // Try registered map first
        if (isset($this->eventClassMap[$eventType])) {
            return $this->eventClassMap[$eventType];
        }

        // Try to use event type as class name directly (FQCN in event_type)
        if (class_exists($eventType) && is_subclass_of($eventType, Serializable::class)) {
            return $eventType;
        }

        throw OutboxPublishException::eventClassNotFound($eventType);
    }

    /**
     * Extract the inner event payload from Broadway DomainMessage envelope.
     *
     * Broadway serializes DomainMessage as:
     * {
     *   "uuid": "aggregate-uuid",
     *   "payload": {
     *     "class": "Fully\\Qualified\\EventClass",
     *     "payload": { actual event data }
     *   },
     *   "metadata": {...},
     *   "playhead": int,
     *   "recorded_on": "timestamp"
     * }
     *
     * This method extracts the inner "payload.payload" containing the actual event data.
     *
     * @param array<string, mixed> $envelope The full Broadway DomainMessage envelope
     * @param string $eventType The expected event type for error messages
     *
     * @return array<string, mixed> The inner event payload
     *
     * @throws OutboxPublishException If the envelope structure is invalid
     */
    private function extractEventPayload(array $envelope, string $eventType): array
    {
        // Check if this is a Broadway envelope (has 'payload' with nested 'payload')
        if (!isset($envelope['payload']) || !is_array($envelope['payload'])) {
            // Not a Broadway envelope, return as-is (direct event data)
            return $envelope;
        }

        $outerPayload = $envelope['payload'];

        // Check for nested payload structure (Broadway format)
        if (isset($outerPayload['payload']) && is_array($outerPayload['payload'])) {
            return $outerPayload['payload'];
        }

        // Outer payload doesn't have nested structure, might be direct event data
        // This handles cases where the event was serialized without Broadway wrapper
        return $outerPayload;
    }
}
