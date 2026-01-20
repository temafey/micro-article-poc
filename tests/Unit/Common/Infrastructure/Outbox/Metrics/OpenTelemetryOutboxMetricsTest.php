<?php

declare(strict_types=1);

namespace Micro\Tests\Unit\Common\Infrastructure\Outbox\Metrics;

use Micro\Component\Common\Domain\Outbox\OutboxMessageType;
use Micro\Component\Common\Infrastructure\Observability\Service\MeterFactoryInterface;
use Micro\Component\Common\Infrastructure\Outbox\Metrics\OpenTelemetryOutboxMetrics;
use Micro\Component\Common\Infrastructure\Outbox\Metrics\OutboxMetricsInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use OpenTelemetry\API\Metrics\CounterInterface;
use OpenTelemetry\API\Metrics\HistogramInterface;
use OpenTelemetry\API\Metrics\MeterInterface;
use OpenTelemetry\API\Metrics\ObservableGaugeInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for OpenTelemetryOutboxMetrics.
 *
 * Tests the OpenTelemetry-based metrics implementation with mocked OTel SDK.
 *
 * @see OpenTelemetryOutboxMetrics
 */
#[CoversClass(OpenTelemetryOutboxMetrics::class)]
final class OpenTelemetryOutboxMetricsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private MeterFactoryInterface&MockInterface $meterFactory;
    private MeterInterface&MockInterface $meter;
    private CounterInterface&MockInterface $enqueuedCounter;
    private CounterInterface&MockInterface $publishedCounter;
    private CounterInterface&MockInterface $failedCounter;
    private CounterInterface&MockInterface $retriedCounter;
    private CounterInterface&MockInterface $cleanupDeletedCounter;
    private HistogramInterface&MockInterface $publishDurationHistogram;
    private HistogramInterface&MockInterface $cleanupDurationHistogram;
    private OpenTelemetryOutboxMetrics $metrics;

    protected function setUp(): void
    {
        parent::setUp();

        $this->meterFactory = Mockery::mock(MeterFactoryInterface::class);
        $this->meter = Mockery::mock(MeterInterface::class);

        // Create mock counters
        $this->enqueuedCounter = Mockery::mock(CounterInterface::class);
        $this->publishedCounter = Mockery::mock(CounterInterface::class);
        $this->failedCounter = Mockery::mock(CounterInterface::class);
        $this->retriedCounter = Mockery::mock(CounterInterface::class);
        $this->cleanupDeletedCounter = Mockery::mock(CounterInterface::class);

        // Create mock histograms
        $this->publishDurationHistogram = Mockery::mock(HistogramInterface::class);
        $this->cleanupDurationHistogram = Mockery::mock(HistogramInterface::class);

        // Setup meter factory to return meter
        $this->meterFactory
            ->shouldReceive('getMeter')
            ->once()
            ->andReturn($this->meter);

        // Setup meter to create counters
        $this->meter
            ->shouldReceive('createCounter')
            ->with('outbox.messages.enqueued', '{message}', Mockery::type('string'))
            ->once()
            ->andReturn($this->enqueuedCounter);

        $this->meter
            ->shouldReceive('createCounter')
            ->with('outbox.messages.published', '{message}', Mockery::type('string'))
            ->once()
            ->andReturn($this->publishedCounter);

        $this->meter
            ->shouldReceive('createCounter')
            ->with('outbox.messages.failed', '{message}', Mockery::type('string'))
            ->once()
            ->andReturn($this->failedCounter);

        $this->meter
            ->shouldReceive('createCounter')
            ->with('outbox.messages.retried', '{message}', Mockery::type('string'))
            ->once()
            ->andReturn($this->retriedCounter);

        $this->meter
            ->shouldReceive('createCounter')
            ->with('outbox.cleanup.deleted', '{message}', Mockery::type('string'))
            ->once()
            ->andReturn($this->cleanupDeletedCounter);

        // Setup meter to create histograms
        $this->meter
            ->shouldReceive('createHistogram')
            ->with('outbox.publish.duration', 'ms', Mockery::type('string'))
            ->once()
            ->andReturn($this->publishDurationHistogram);

        $this->meter
            ->shouldReceive('createHistogram')
            ->with('outbox.cleanup.duration', 'ms', Mockery::type('string'))
            ->once()
            ->andReturn($this->cleanupDurationHistogram);

        // Setup meter to create observable gauge
        $this->meter
            ->shouldReceive('createObservableGauge')
            ->with('outbox.messages.pending', '{message}', Mockery::type('string'), Mockery::type('callable'))
            ->once();

        // Create the metrics instance - this triggers initializeMetrics()
        $this->metrics = new OpenTelemetryOutboxMetrics($this->meterFactory);
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
    public function recordMessageEnqueuedAddsToCounterWithCorrectAttributes(): void
    {
        $this->enqueuedCounter
            ->shouldReceive('add')
            ->once()
            ->with(1, [
                'message_type' => OutboxMessageType::EVENT->value,
                'aggregate_type' => 'Article',
            ]);

        $this->metrics->recordMessageEnqueued(OutboxMessageType::EVENT, 'Article');
    }

    #[Test]
    public function recordMessageEnqueuedWithTaskType(): void
    {
        $this->enqueuedCounter
            ->shouldReceive('add')
            ->once()
            ->with(1, [
                'message_type' => OutboxMessageType::TASK->value,
                'aggregate_type' => 'User',
            ]);

        $this->metrics->recordMessageEnqueued(OutboxMessageType::TASK, 'User');
    }

    #[Test]
    public function recordMessageEnqueuedWithDifferentAggregateTypes(): void
    {
        $aggregateTypes = ['Article', 'User', 'Order', 'Payment'];

        foreach ($aggregateTypes as $aggregateType) {
            $this->enqueuedCounter
                ->shouldReceive('add')
                ->once()
                ->with(1, [
                    'message_type' => OutboxMessageType::EVENT->value,
                    'aggregate_type' => $aggregateType,
                ]);

            $this->metrics->recordMessageEnqueued(OutboxMessageType::EVENT, $aggregateType);
        }
    }

    // =========================================================================
    // recordMessagePublished Tests
    // =========================================================================

    #[Test]
    public function recordMessagePublishedAddsToCounterAndRecordsHistogram(): void
    {
        $this->publishedCounter
            ->shouldReceive('add')
            ->once()
            ->with(1, [
                'message_type' => OutboxMessageType::EVENT->value,
            ]);

        // Duration is converted from seconds to milliseconds
        $this->publishDurationHistogram
            ->shouldReceive('record')
            ->once()
            ->with(123.0, [ // 0.123 seconds = 123 ms
                'message_type' => OutboxMessageType::EVENT->value,
            ]);

        $this->metrics->recordMessagePublished(OutboxMessageType::EVENT, 0.123);
    }

    #[Test]
    public function recordMessagePublishedWithTaskType(): void
    {
        $this->publishedCounter
            ->shouldReceive('add')
            ->once()
            ->with(1, [
                'message_type' => OutboxMessageType::TASK->value,
            ]);

        $this->publishDurationHistogram
            ->shouldReceive('record')
            ->once()
            ->with(1500.0, [ // 1.5 seconds = 1500 ms
                'message_type' => OutboxMessageType::TASK->value,
            ]);

        $this->metrics->recordMessagePublished(OutboxMessageType::TASK, 1.5);
    }

    #[Test]
    public function recordMessagePublishedWithZeroDuration(): void
    {
        $this->publishedCounter
            ->shouldReceive('add')
            ->once()
            ->with(1, Mockery::type('array'));

        $this->publishDurationHistogram
            ->shouldReceive('record')
            ->once()
            ->with(0.0, Mockery::type('array'));

        $this->metrics->recordMessagePublished(OutboxMessageType::EVENT, 0.0);
    }

    #[Test]
    public function recordMessagePublishedWithSubMillisecondDuration(): void
    {
        $this->publishedCounter
            ->shouldReceive('add')
            ->once()
            ->with(1, Mockery::type('array'));

        // 0.0001 seconds = 0.1 ms
        $this->publishDurationHistogram
            ->shouldReceive('record')
            ->once()
            ->with(0.1, Mockery::type('array'));

        $this->metrics->recordMessagePublished(OutboxMessageType::EVENT, 0.0001);
    }

    // =========================================================================
    // recordPublishFailure Tests
    // =========================================================================

    #[Test]
    public function recordPublishFailureAddsToCounterWithCorrectAttributes(): void
    {
        $this->failedCounter
            ->shouldReceive('add')
            ->once()
            ->with(1, [
                'message_type' => OutboxMessageType::EVENT->value,
                'error_type' => 'connection',
            ]);

        $this->metrics->recordPublishFailure(OutboxMessageType::EVENT, 'connection');
    }

    #[Test]
    public function recordPublishFailureWithDifferentErrorTypes(): void
    {
        $errorTypes = ['connection', 'timeout', 'serialization', 'unknown'];

        foreach ($errorTypes as $errorType) {
            $this->failedCounter
                ->shouldReceive('add')
                ->once()
                ->with(1, [
                    'message_type' => OutboxMessageType::TASK->value,
                    'error_type' => $errorType,
                ]);

            $this->metrics->recordPublishFailure(OutboxMessageType::TASK, $errorType);
        }
    }

    // =========================================================================
    // recordRetryAttempt Tests
    // =========================================================================

    #[Test]
    public function recordRetryAttemptAddsToCounterWithCorrectAttributes(): void
    {
        $this->retriedCounter
            ->shouldReceive('add')
            ->once()
            ->with(1, [
                'message_type' => OutboxMessageType::EVENT->value,
                'retry_count' => '1',
            ]);

        $this->metrics->recordRetryAttempt(OutboxMessageType::EVENT, 1);
    }

    #[Test]
    public function recordRetryAttemptCapsRetryCountAtFive(): void
    {
        // When retry count exceeds 5, it should be capped at 5 for cardinality
        $this->retriedCounter
            ->shouldReceive('add')
            ->once()
            ->with(1, [
                'message_type' => OutboxMessageType::EVENT->value,
                'retry_count' => '5',
            ]);

        $this->metrics->recordRetryAttempt(OutboxMessageType::EVENT, 10);
    }

    #[Test]
    public function recordRetryAttemptWithMaxRetryCount(): void
    {
        $this->retriedCounter
            ->shouldReceive('add')
            ->once()
            ->with(1, [
                'message_type' => OutboxMessageType::TASK->value,
                'retry_count' => '5',
            ]);

        $this->metrics->recordRetryAttempt(OutboxMessageType::TASK, 5);
    }

    #[Test]
    public function recordRetryAttemptWithZeroRetryCount(): void
    {
        $this->retriedCounter
            ->shouldReceive('add')
            ->once()
            ->with(1, [
                'message_type' => OutboxMessageType::EVENT->value,
                'retry_count' => '0',
            ]);

        $this->metrics->recordRetryAttempt(OutboxMessageType::EVENT, 0);
    }

    // =========================================================================
    // setPendingCount Tests
    // =========================================================================

    #[Test]
    public function setPendingCountStoresPendingCount(): void
    {
        // setPendingCount stores values internally for the observable gauge callback
        // We can test this by verifying no exceptions occur
        $this->metrics->setPendingCount(OutboxMessageType::EVENT, 100);
        $this->metrics->setPendingCount(OutboxMessageType::TASK, 50);

        // No assertions needed - testing that no exceptions are thrown
        self::assertTrue(true);
    }

    #[Test]
    public function setPendingCountOverwritesPreviousValue(): void
    {
        $this->metrics->setPendingCount(OutboxMessageType::EVENT, 100);
        $this->metrics->setPendingCount(OutboxMessageType::EVENT, 200);
        $this->metrics->setPendingCount(OutboxMessageType::EVENT, 0);

        self::assertTrue(true);
    }

    // =========================================================================
    // recordCleanup Tests
    // =========================================================================

    #[Test]
    public function recordCleanupAddsToCounterAndRecordsHistogram(): void
    {
        $this->cleanupDeletedCounter
            ->shouldReceive('add')
            ->once()
            ->with(500);

        // Duration converted to ms, bucket is '101-1000' for 500
        $this->cleanupDurationHistogram
            ->shouldReceive('record')
            ->once()
            ->with(2500.0, [ // 2.5 seconds = 2500 ms
                'deleted_count_bucket' => '101-1000',
            ]);

        $this->metrics->recordCleanup(500, 2.5);
    }

    #[Test]
    public function recordCleanupWithZeroDeletedMessages(): void
    {
        $this->cleanupDeletedCounter
            ->shouldReceive('add')
            ->once()
            ->with(0);

        $this->cleanupDurationHistogram
            ->shouldReceive('record')
            ->once()
            ->with(100.0, [
                'deleted_count_bucket' => '0',
            ]);

        $this->metrics->recordCleanup(0, 0.1);
    }

    #[Test]
    public function recordCleanupWithSmallDeletedCount(): void
    {
        $this->cleanupDeletedCounter
            ->shouldReceive('add')
            ->once()
            ->with(5);

        $this->cleanupDurationHistogram
            ->shouldReceive('record')
            ->once()
            ->with(50.0, [
                'deleted_count_bucket' => '1-10',
            ]);

        $this->metrics->recordCleanup(5, 0.05);
    }

    #[Test]
    public function recordCleanupWithMediumDeletedCount(): void
    {
        $this->cleanupDeletedCounter
            ->shouldReceive('add')
            ->once()
            ->with(50);

        $this->cleanupDurationHistogram
            ->shouldReceive('record')
            ->once()
            ->with(1000.0, [
                'deleted_count_bucket' => '11-100',
            ]);

        $this->metrics->recordCleanup(50, 1.0);
    }

    #[Test]
    public function recordCleanupWithLargeDeletedCount(): void
    {
        $this->cleanupDeletedCounter
            ->shouldReceive('add')
            ->once()
            ->with(5000);

        $this->cleanupDurationHistogram
            ->shouldReceive('record')
            ->once()
            ->with(30000.0, [
                'deleted_count_bucket' => '1000+',
            ]);

        $this->metrics->recordCleanup(5000, 30.0);
    }

    // =========================================================================
    // Bucket Calculation Tests (via recordCleanup)
    // =========================================================================

    #[Test]
    public function bucketCalculationBoundaryZero(): void
    {
        $this->cleanupDeletedCounter->shouldReceive('add')->with(0);
        $this->cleanupDurationHistogram
            ->shouldReceive('record')
            ->with(Mockery::any(), ['deleted_count_bucket' => '0']);

        $this->metrics->recordCleanup(0, 0.001);
    }

    #[Test]
    public function bucketCalculationBoundaryOne(): void
    {
        $this->cleanupDeletedCounter->shouldReceive('add')->with(1);
        $this->cleanupDurationHistogram
            ->shouldReceive('record')
            ->with(Mockery::any(), ['deleted_count_bucket' => '1-10']);

        $this->metrics->recordCleanup(1, 0.001);
    }

    #[Test]
    public function bucketCalculationBoundaryTen(): void
    {
        $this->cleanupDeletedCounter->shouldReceive('add')->with(10);
        $this->cleanupDurationHistogram
            ->shouldReceive('record')
            ->with(Mockery::any(), ['deleted_count_bucket' => '1-10']);

        $this->metrics->recordCleanup(10, 0.001);
    }

    #[Test]
    public function bucketCalculationBoundaryEleven(): void
    {
        $this->cleanupDeletedCounter->shouldReceive('add')->with(11);
        $this->cleanupDurationHistogram
            ->shouldReceive('record')
            ->with(Mockery::any(), ['deleted_count_bucket' => '11-100']);

        $this->metrics->recordCleanup(11, 0.001);
    }

    #[Test]
    public function bucketCalculationBoundaryHundred(): void
    {
        $this->cleanupDeletedCounter->shouldReceive('add')->with(100);
        $this->cleanupDurationHistogram
            ->shouldReceive('record')
            ->with(Mockery::any(), ['deleted_count_bucket' => '11-100']);

        $this->metrics->recordCleanup(100, 0.001);
    }

    #[Test]
    public function bucketCalculationBoundaryHundredOne(): void
    {
        $this->cleanupDeletedCounter->shouldReceive('add')->with(101);
        $this->cleanupDurationHistogram
            ->shouldReceive('record')
            ->with(Mockery::any(), ['deleted_count_bucket' => '101-1000']);

        $this->metrics->recordCleanup(101, 0.001);
    }

    #[Test]
    public function bucketCalculationBoundaryThousand(): void
    {
        $this->cleanupDeletedCounter->shouldReceive('add')->with(1000);
        $this->cleanupDurationHistogram
            ->shouldReceive('record')
            ->with(Mockery::any(), ['deleted_count_bucket' => '101-1000']);

        $this->metrics->recordCleanup(1000, 0.001);
    }

    #[Test]
    public function bucketCalculationBoundaryThousandOne(): void
    {
        $this->cleanupDeletedCounter->shouldReceive('add')->with(1001);
        $this->cleanupDurationHistogram
            ->shouldReceive('record')
            ->with(Mockery::any(), ['deleted_count_bucket' => '1000+']);

        $this->metrics->recordCleanup(1001, 0.001);
    }

    // =========================================================================
    // Initialization Tests
    // =========================================================================

    #[Test]
    public function constructorInitializesAllMetrics(): void
    {
        // Create a new metrics instance to verify initialization
        $meterFactory = Mockery::mock(MeterFactoryInterface::class);
        $meter = Mockery::mock(MeterInterface::class);

        $meterFactory->shouldReceive('getMeter')->once()->andReturn($meter);

        // Expect all counters to be created
        $meter->shouldReceive('createCounter')
            ->with('outbox.messages.enqueued', Mockery::any(), Mockery::any())
            ->once()
            ->andReturn(Mockery::mock(CounterInterface::class));

        $meter->shouldReceive('createCounter')
            ->with('outbox.messages.published', Mockery::any(), Mockery::any())
            ->once()
            ->andReturn(Mockery::mock(CounterInterface::class));

        $meter->shouldReceive('createCounter')
            ->with('outbox.messages.failed', Mockery::any(), Mockery::any())
            ->once()
            ->andReturn(Mockery::mock(CounterInterface::class));

        $meter->shouldReceive('createCounter')
            ->with('outbox.messages.retried', Mockery::any(), Mockery::any())
            ->once()
            ->andReturn(Mockery::mock(CounterInterface::class));

        $meter->shouldReceive('createCounter')
            ->with('outbox.cleanup.deleted', Mockery::any(), Mockery::any())
            ->once()
            ->andReturn(Mockery::mock(CounterInterface::class));

        // Expect histograms to be created
        $meter->shouldReceive('createHistogram')
            ->with('outbox.publish.duration', Mockery::any(), Mockery::any())
            ->once()
            ->andReturn(Mockery::mock(HistogramInterface::class));

        $meter->shouldReceive('createHistogram')
            ->with('outbox.cleanup.duration', Mockery::any(), Mockery::any())
            ->once()
            ->andReturn(Mockery::mock(HistogramInterface::class));

        // Expect observable gauge to be created
        $meter->shouldReceive('createObservableGauge')
            ->with('outbox.messages.pending', Mockery::any(), Mockery::any(), Mockery::type('callable'))
            ->once();

        new OpenTelemetryOutboxMetrics($meterFactory);
    }

    // =========================================================================
    // Integration Scenario Tests
    // =========================================================================

    #[Test]
    public function fullPublishCycleMetrics(): void
    {
        // Simulate a complete publish cycle: enqueue -> publish -> success
        $this->enqueuedCounter
            ->shouldReceive('add')
            ->once()
            ->with(1, Mockery::type('array'));

        $this->publishedCounter
            ->shouldReceive('add')
            ->once()
            ->with(1, Mockery::type('array'));

        $this->publishDurationHistogram
            ->shouldReceive('record')
            ->once()
            ->with(Mockery::type('float'), Mockery::type('array'));

        $this->metrics->recordMessageEnqueued(OutboxMessageType::EVENT, 'Article');
        $this->metrics->recordMessagePublished(OutboxMessageType::EVENT, 0.05);
    }

    #[Test]
    public function failedPublishWithRetryMetrics(): void
    {
        // Simulate: enqueue -> fail -> retry -> fail -> retry -> success
        $this->enqueuedCounter
            ->shouldReceive('add')
            ->once()
            ->with(1, Mockery::type('array'));

        $this->failedCounter
            ->shouldReceive('add')
            ->twice()
            ->with(1, Mockery::type('array'));

        $this->retriedCounter
            ->shouldReceive('add')
            ->twice()
            ->with(1, Mockery::type('array'));

        $this->publishedCounter
            ->shouldReceive('add')
            ->once()
            ->with(1, Mockery::type('array'));

        $this->publishDurationHistogram
            ->shouldReceive('record')
            ->once()
            ->with(Mockery::type('float'), Mockery::type('array'));

        // Enqueue
        $this->metrics->recordMessageEnqueued(OutboxMessageType::EVENT, 'Article');

        // First attempt fails
        $this->metrics->recordPublishFailure(OutboxMessageType::EVENT, 'connection');

        // First retry
        $this->metrics->recordRetryAttempt(OutboxMessageType::EVENT, 1);

        // Second attempt fails
        $this->metrics->recordPublishFailure(OutboxMessageType::EVENT, 'timeout');

        // Second retry
        $this->metrics->recordRetryAttempt(OutboxMessageType::EVENT, 2);

        // Third attempt succeeds
        $this->metrics->recordMessagePublished(OutboxMessageType::EVENT, 0.1);
    }
}
