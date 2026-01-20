<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Outbox\Publisher;

use Micro\Component\Common\Domain\Outbox\OutboxEntryInterface;

/**
 * Contract for outbox message publishers.
 *
 * Implementations route messages to appropriate message brokers
 * based on message_type discrimination.
 *
 * @see ADR-006: Transactional Outbox Pattern
 * @see TASK-14.4: Background Publisher
 */
interface OutboxPublisherInterface
{
    /**
     * Publish an outbox entry to its destination.
     *
     * @param OutboxEntryInterface $entry The outbox entry to publish
     *
     * @throws OutboxPublishException If publishing fails
     */
    public function publish(OutboxEntryInterface $entry): void;

    /**
     * Check if publisher can handle this message type.
     *
     * @param string $messageType The message type discriminator
     *
     * @return bool True if this publisher handles the message type
     */
    public function supports(string $messageType): bool;
}
