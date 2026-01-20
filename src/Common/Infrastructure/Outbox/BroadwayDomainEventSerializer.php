<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Outbox;

use Broadway\Domain\DomainMessage;
use Broadway\Serializer\Serializer;
use Micro\Component\Common\Domain\Outbox\DomainEventSerializerInterface;

/**
 * Broadway-compatible domain event serializer.
 *
 * Uses the existing Broadway serializer infrastructure to ensure
 * payload compatibility with existing consumers.
 *
 * @see docs/tasks/phase-14-transactional-outbox/TASK-14.2-eventstore-decorator.md
 */
final readonly class BroadwayDomainEventSerializer implements DomainEventSerializerInterface
{
    private const string DEFAULT_TOPIC_PREFIX = 'events.';
    private const string DEFAULT_ROUTING_KEY_PREFIX = 'event.';

    public function __construct(
        private Serializer $payloadSerializer,
        private Serializer $metadataSerializer,
        private string $topicPrefix = self::DEFAULT_TOPIC_PREFIX,
        private string $routingKeyPrefix = self::DEFAULT_ROUTING_KEY_PREFIX,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function serialize(DomainMessage $message): array
    {
        return [
            'uuid' => $message->getId(),
            'playhead' => $message->getPlayhead(),
            'metadata' => $this->metadataSerializer->serialize($message->getMetadata()),
            'payload' => $this->payloadSerializer->serialize($message->getPayload()),
            'recorded_on' => $message->getRecordedOn()->toString(),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function extractEventType(DomainMessage $message): string
    {
        $payload = $message->getPayload();

        return $payload::class;
    }

    /**
     * {@inheritDoc}
     */
    public function determineTopicName(DomainMessage $message): string
    {
        $eventType = $this->extractEventType($message);

        // Extract domain from namespace: Article\Domain\Event\ArticleCreatedEvent -> article
        $parts = explode('\\', $eventType);
        $domain = strtolower($parts[0] ?? 'default');

        return $this->topicPrefix . $domain;
    }

    /**
     * {@inheritDoc}
     */
    public function determineRoutingKey(DomainMessage $message): string
    {
        $eventType = $this->extractEventType($message);

        // Extract short event name: Article\Domain\Event\ArticleCreatedEvent -> article_created_event
        $parts = explode('\\', $eventType);
        $eventName = end($parts);

        // Convert CamelCase to snake_case
        $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $eventName) ?? $eventName);

        return $this->routingKeyPrefix . $snakeCase;
    }
}
