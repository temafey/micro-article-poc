<?php

declare(strict_types=1);

namespace Micro\Tests\Unit\Common\Infrastructure\Outbox\Metrics;

use Micro\Component\Common\Domain\Outbox\OutboxMessageType;
use Micro\Component\Common\Infrastructure\Outbox\Metrics\NullOutboxMetrics;
use Micro\Component\Common\Infrastructure\Outbox\Metrics\OutboxMetricsInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for NullOutboxMetrics.
 *
 * Verifies that the no-op implementation can be called without errors.
 *
 * @see NullOutboxMetrics
 */
#[CoversClass(NullOutboxMetrics::class)]
final class NullOutboxMetricsTest extends TestCase
{
    private NullOutboxMetrics $metrics;

    protected function setUp(): void
    {
        parent::setUp();
        $this->metrics = new NullOutboxMetrics();
    }

    // =========================================================================
    // Interface Implementation Tests
    // =========================================================================

    #[Test]
    public function implementsOutboxMetricsInterface(): void
    {
        self::assertInstanceOf(OutboxMetricsInterface::class, $this->metrics);
    }

    // =========================================================================
    // recordMessageEnqueued Tests
    // =========================================================================

    #[Test]
    public function recordMessageEnqueuedWithEventType(): void
    {
        // Should not throw - just verify no-op behavior
        $this->metrics->recordMessageEnqueued(OutboxMessageType::EVENT, 'Article');

        // If we reach here, the test passes (no exception)
        self::assertTrue(true);
    }

    #[Test]
    public function recordMessageEnqueuedWithTaskType(): void
    {
        $this->metrics->recordMessageEnqueued(OutboxMessageType::TASK, 'User');

        self::assertTrue(true);
    }

    #[Test]
    public function recordMessageEnqueuedWithEmptyAggregateType(): void
    {
        $this->metrics->recordMessageEnqueued(OutboxMessageType::EVENT, '');

        self::assertTrue(true);
    }

    // =========================================================================
    // recordMessagePublished Tests
    // =========================================================================

    #[Test]
    public function recordMessagePublishedWithEventType(): void
    {
        $this->metrics->recordMessagePublished(OutboxMessageType::EVENT, 0.123);

        self::assertTrue(true);
    }

    #[Test]
    public function recordMessagePublishedWithTaskType(): void
    {
        $this->metrics->recordMessagePublished(OutboxMessageType::TASK, 1.5);

        self::assertTrue(true);
    }

    #[Test]
    public function recordMessagePublishedWithZeroDuration(): void
    {
        $this->metrics->recordMessagePublished(OutboxMessageType::EVENT, 0.0);

        self::assertTrue(true);
    }

    #[Test]
    public function recordMessagePublishedWithNegativeDuration(): void
    {
        // Edge case - should handle gracefully
        $this->metrics->recordMessagePublished(OutboxMessageType::EVENT, -1.0);

        self::assertTrue(true);
    }

    // =========================================================================
    // recordPublishFailure Tests
    // =========================================================================

    #[Test]
    public function recordPublishFailureWithConnectionError(): void
    {
        $this->metrics->recordPublishFailure(OutboxMessageType::EVENT, 'connection');

        self::assertTrue(true);
    }

    #[Test]
    public function recordPublishFailureWithTimeoutError(): void
    {
        $this->metrics->recordPublishFailure(OutboxMessageType::TASK, 'timeout');

        self::assertTrue(true);
    }

    #[Test]
    public function recordPublishFailureWithSerializationError(): void
    {
        $this->metrics->recordPublishFailure(OutboxMessageType::EVENT, 'serialization');

        self::assertTrue(true);
    }

    #[Test]
    public function recordPublishFailureWithEmptyErrorType(): void
    {
        $this->metrics->recordPublishFailure(OutboxMessageType::EVENT, '');

        self::assertTrue(true);
    }

    // =========================================================================
    // recordRetryAttempt Tests
    // =========================================================================

    #[Test]
    public function recordRetryAttemptFirstRetry(): void
    {
        $this->metrics->recordRetryAttempt(OutboxMessageType::EVENT, 1);

        self::assertTrue(true);
    }

    #[Test]
    public function recordRetryAttemptMaxRetries(): void
    {
        $this->metrics->recordRetryAttempt(OutboxMessageType::TASK, 5);

        self::assertTrue(true);
    }

    #[Test]
    public function recordRetryAttemptExceedingMaxRetries(): void
    {
        // Edge case - retry count exceeding expected max
        $this->metrics->recordRetryAttempt(OutboxMessageType::EVENT, 100);

        self::assertTrue(true);
    }

    #[Test]
    public function recordRetryAttemptZeroRetryCount(): void
    {
        $this->metrics->recordRetryAttempt(OutboxMessageType::EVENT, 0);

        self::assertTrue(true);
    }

    // =========================================================================
    // setPendingCount Tests
    // =========================================================================

    #[Test]
    public function setPendingCountWithEventType(): void
    {
        $this->metrics->setPendingCount(OutboxMessageType::EVENT, 100);

        self::assertTrue(true);
    }

    #[Test]
    public function setPendingCountWithTaskType(): void
    {
        $this->metrics->setPendingCount(OutboxMessageType::TASK, 50);

        self::assertTrue(true);
    }

    #[Test]
    public function setPendingCountZero(): void
    {
        $this->metrics->setPendingCount(OutboxMessageType::EVENT, 0);

        self::assertTrue(true);
    }

    #[Test]
    public function setPendingCountLargeValue(): void
    {
        $this->metrics->setPendingCount(OutboxMessageType::EVENT, 100000);

        self::assertTrue(true);
    }

    // =========================================================================
    // recordCleanup Tests
    // =========================================================================

    #[Test]
    public function recordCleanupWithDeletedMessages(): void
    {
        $this->metrics->recordCleanup(500, 2.5);

        self::assertTrue(true);
    }

    #[Test]
    public function recordCleanupNoDeletedMessages(): void
    {
        $this->metrics->recordCleanup(0, 0.1);

        self::assertTrue(true);
    }

    #[Test]
    public function recordCleanupLargeDeletedCount(): void
    {
        $this->metrics->recordCleanup(10000, 30.0);

        self::assertTrue(true);
    }

    #[Test]
    public function recordCleanupZeroDuration(): void
    {
        $this->metrics->recordCleanup(100, 0.0);

        self::assertTrue(true);
    }

    // =========================================================================
    // Idempotency Tests
    // =========================================================================

    #[Test]
    public function multipleCallsToSameMethodAreSafe(): void
    {
        // Call same method multiple times to ensure no state accumulation issues
        for ($i = 0; $i < 10; ++$i) {
            $this->metrics->recordMessageEnqueued(OutboxMessageType::EVENT, 'Article');
            $this->metrics->recordMessagePublished(OutboxMessageType::EVENT, 0.1);
            $this->metrics->recordPublishFailure(OutboxMessageType::EVENT, 'test');
            $this->metrics->recordRetryAttempt(OutboxMessageType::EVENT, $i);
            $this->metrics->setPendingCount(OutboxMessageType::EVENT, $i * 10);
            $this->metrics->recordCleanup($i * 100, 0.5);
        }

        self::assertTrue(true);
    }

    // =========================================================================
    // All Message Types Tests
    // =========================================================================

    #[Test]
    public function allMethodsWorkWithBothMessageTypes(): void
    {
        foreach (OutboxMessageType::cases() as $type) {
            $this->metrics->recordMessageEnqueued($type, 'TestAggregate');
            $this->metrics->recordMessagePublished($type, 0.5);
            $this->metrics->recordPublishFailure($type, 'test_error');
            $this->metrics->recordRetryAttempt($type, 3);
            $this->metrics->setPendingCount($type, 42);
        }

        self::assertTrue(true);
    }
}
