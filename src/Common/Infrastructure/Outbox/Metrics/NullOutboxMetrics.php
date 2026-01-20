<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Outbox\Metrics;

use Micro\Component\Common\Domain\Outbox\OutboxMessageType;

/**
 * No-op implementation of outbox metrics.
 *
 * Used when metrics collection is disabled or for testing.
 * All methods are empty implementations that do nothing.
 *
 * @see OutboxMetricsInterface
 * @see TASK-14.5: Monitoring & Cleanup
 */
final class NullOutboxMetrics implements OutboxMetricsInterface
{
    /**
     * {@inheritDoc}
     */
    public function recordMessageEnqueued(OutboxMessageType $type, string $aggregateType): void
    {
        // No-op
    }

    /**
     * {@inheritDoc}
     */
    public function recordMessagePublished(OutboxMessageType $type, float $durationSeconds): void
    {
        // No-op
    }

    /**
     * {@inheritDoc}
     */
    public function recordPublishFailure(OutboxMessageType $type, string $errorType): void
    {
        // No-op
    }

    /**
     * {@inheritDoc}
     */
    public function recordRetryAttempt(OutboxMessageType $type, int $retryCount): void
    {
        // No-op
    }

    /**
     * {@inheritDoc}
     */
    public function setPendingCount(OutboxMessageType $type, int $count): void
    {
        // No-op
    }

    /**
     * {@inheritDoc}
     */
    public function recordCleanup(int $deletedCount, float $durationSeconds): void
    {
        // No-op
    }
}
