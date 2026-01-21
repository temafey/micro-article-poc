<?php

declare(strict_types=1);

namespace Micro\Tests\Unit\Common\Infrastructure\Console;

use DateTimeImmutable;
use Micro\Component\Common\Domain\Outbox\OutboxEntryInterface;
use Micro\Component\Common\Domain\Outbox\OutboxMessageType;
use Micro\Component\Common\Domain\Outbox\OutboxRepositoryInterface;
use Micro\Component\Common\Infrastructure\Console\PublishOutboxCommand;
use Micro\Component\Common\Infrastructure\Outbox\Metrics\OutboxMetricsInterface;
use Micro\Component\Common\Infrastructure\Outbox\Publisher\OutboxPublisherInterface;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Unit tests for PublishOutboxCommand.
 *
 * @see PublishOutboxCommand
 */
#[CoversClass(PublishOutboxCommand::class)]
final class PublishOutboxCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private OutboxRepositoryInterface&MockInterface $outboxRepository;
    private OutboxPublisherInterface&MockInterface $publisher;
    private OutboxMetricsInterface&MockInterface $metrics;
    private LoggerInterface&MockInterface $logger;
    private PublishOutboxCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outboxRepository = Mockery::mock(OutboxRepositoryInterface::class);
        $this->publisher = Mockery::mock(OutboxPublisherInterface::class);

        // Metrics mock with lenient expectations for all tests
        $this->metrics = Mockery::mock(OutboxMetricsInterface::class);
        $this->metrics->shouldReceive('setPendingCount')->andReturnSelf()->byDefault();
        $this->metrics->shouldReceive('incrementProcessed')->andReturnSelf()->byDefault();
        $this->metrics->shouldReceive('incrementFailed')->andReturnSelf()->byDefault();
        $this->metrics->shouldReceive('recordMessagePublished')->andReturnNull()->byDefault();
        $this->metrics->shouldReceive('recordPublishFailure')->andReturnNull()->byDefault();
        $this->metrics->shouldReceive('recordRetryAttempt')->andReturnNull()->byDefault();

        // Logger mock with lenient expectations for all tests
        $this->logger = Mockery::mock(LoggerInterface::class);
        $this->logger->shouldReceive('info')->andReturnNull()->byDefault();
        $this->logger->shouldReceive('warning')->andReturnNull()->byDefault();
        $this->logger->shouldReceive('error')->andReturnNull()->byDefault();
        $this->logger->shouldReceive('critical')->andReturnNull()->byDefault();

        $this->command = new PublishOutboxCommand(
            $this->outboxRepository,
            $this->publisher,
            $this->metrics,
            $this->logger,
        );

        $this->commandTester = new CommandTester($this->command);

        // Setup default metrics expectations for all tests
        $this->setupDefaultMetrics();
    }

    private function setupDefaultMetrics(): void
    {
        $this->outboxRepository
            ->shouldReceive('getMetrics')
            ->andReturn([
                'total_events' => 0,
                'total_tasks' => 0,
                'total_failures' => 0,
            ])
            ->byDefault();
    }

    // =========================================================================
    // Command Configuration Tests
    // =========================================================================

    #[Test]
    public function commandHasCorrectName(): void
    {
        self::assertSame('app:outbox:publish', $this->command->getName());
    }

    #[Test]
    public function commandHasCorrectDescription(): void
    {
        self::assertSame(
            'Publish pending outbox messages to RabbitMQ',
            $this->command->getDescription()
        );
    }

    #[Test]
    public function commandHasRequiredOptions(): void
    {
        $definition = $this->command->getDefinition();

        self::assertTrue($definition->hasOption('batch-size'));
        self::assertTrue($definition->hasOption('poll-interval'));
        self::assertTrue($definition->hasOption('max-retries'));
        self::assertTrue($definition->hasOption('message-type'));
        self::assertTrue($definition->hasOption('run-once'));
        self::assertTrue($definition->hasOption('dry-run'));
        self::assertTrue($definition->hasOption('memory-limit'));
    }

    // =========================================================================
    // Execute with No Pending Messages Tests
    // =========================================================================

    #[Test]
    public function executeWithNoPendingMessagesReturnsSuccess(): void
    {
        $this->outboxRepository
            ->shouldReceive('findUnpublished')
            ->once()
            ->with(100)
            ->andReturn([]);

        $exitCode = $this->commandTester->execute([
            '--run-once' => true,
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('No pending messages', $this->commandTester->getDisplay());
    }

    #[Test]
    public function executeWithCustomBatchSize(): void
    {
        $this->outboxRepository
            ->shouldReceive('findUnpublished')
            ->once()
            ->with(50)
            ->andReturn([]);

        $this->commandTester->execute([
            '--run-once' => true,
            '--batch-size' => 50,
        ]);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    // =========================================================================
    // Successful Publishing Tests
    // =========================================================================

    #[Test]
    public function executePublishesMessagesSuccessfully(): void
    {
        $entry = $this->createMockEntry('msg-1', OutboxMessageType::EVENT, 0);

        $this->outboxRepository
            ->shouldReceive('findUnpublished')
            ->once()
            ->andReturn([$entry]);

        $this->publisher
            ->shouldReceive('publish')
            ->once()
            ->with($entry);

        $this->outboxRepository
            ->shouldReceive('markAsPublished')
            ->once()
            ->with(['msg-1'], Mockery::type(DateTimeImmutable::class));

        $exitCode = $this->commandTester->execute([
            '--run-once' => true,
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Messages processed: 1', $this->commandTester->getDisplay());
        self::assertStringContainsString('Messages failed: 0', $this->commandTester->getDisplay());
    }

    #[Test]
    public function executePublishesMultipleMessages(): void
    {
        $entry1 = $this->createMockEntry('msg-1', OutboxMessageType::EVENT, 0);
        $entry2 = $this->createMockEntry('msg-2', OutboxMessageType::TASK, 0);

        $this->outboxRepository
            ->shouldReceive('findUnpublished')
            ->once()
            ->andReturn([$entry1, $entry2]);

        $this->publisher
            ->shouldReceive('publish')
            ->twice();

        $this->outboxRepository
            ->shouldReceive('markAsPublished')
            ->twice();

        $exitCode = $this->commandTester->execute([
            '--run-once' => true,
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Messages processed: 2', $this->commandTester->getDisplay());
    }

    // =========================================================================
    // Dry Run Tests
    // =========================================================================

    #[Test]
    public function executeDryRunDoesNotPublish(): void
    {
        $entry = $this->createMockEntry('msg-1', OutboxMessageType::EVENT, 0);

        $this->outboxRepository
            ->shouldReceive('findUnpublished')
            ->once()
            ->andReturn([$entry]);

        // Publisher should NOT be called in dry-run
        $this->publisher->shouldNotReceive('publish');
        $this->outboxRepository->shouldNotReceive('markAsPublished');

        $exitCode = $this->commandTester->execute([
            '--run-once' => true,
            '--dry-run' => true,
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('DRY RUN MODE', $this->commandTester->getDisplay());
        self::assertStringContainsString('[DRY-RUN] Would publish', $this->commandTester->getDisplay());
    }

    // =========================================================================
    // Message Type Filter Tests
    // =========================================================================

    #[Test]
    public function executeWithEventTypeFilter(): void
    {
        $entry = $this->createMockEntry('msg-1', OutboxMessageType::EVENT, 0);

        $this->outboxRepository
            ->shouldReceive('findUnpublishedByType')
            ->once()
            ->with(OutboxMessageType::EVENT, 100)
            ->andReturn([$entry]);

        $this->publisher->shouldReceive('publish')->once();
        $this->outboxRepository->shouldReceive('markAsPublished')->once();

        $exitCode = $this->commandTester->execute([
            '--run-once' => true,
            '--message-type' => 'event',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    #[Test]
    public function executeWithTaskTypeFilter(): void
    {
        $entry = $this->createMockEntry('msg-1', OutboxMessageType::TASK, 0);

        $this->outboxRepository
            ->shouldReceive('findUnpublishedByType')
            ->once()
            ->with(OutboxMessageType::TASK, 100)
            ->andReturn([$entry]);

        $this->publisher->shouldReceive('publish')->once();
        $this->outboxRepository->shouldReceive('markAsPublished')->once();

        $exitCode = $this->commandTester->execute([
            '--run-once' => true,
            '--message-type' => 'task',
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    // =========================================================================
    // Failure Handling Tests
    // =========================================================================

    #[Test]
    public function executeHandlesPublishFailure(): void
    {
        $entry = $this->createMockEntry('msg-1', OutboxMessageType::EVENT, 0);

        $this->outboxRepository
            ->shouldReceive('findUnpublished')
            ->once()
            ->andReturn([$entry]);

        $this->publisher
            ->shouldReceive('publish')
            ->once()
            ->andThrow(new \RuntimeException('Connection failed'));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Failed to publish outbox message', Mockery::type('array'));

        $this->outboxRepository
            ->shouldReceive('markAsFailed')
            ->once()
            ->with('msg-1', 'Connection failed', Mockery::type(DateTimeImmutable::class));

        $exitCode = $this->commandTester->execute([
            '--run-once' => true,
        ]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Messages failed: 1', $this->commandTester->getDisplay());
    }

    #[Test]
    public function executeSkipsMessageExceedingMaxRetries(): void
    {
        // Message with retry count exceeding max
        $entry = $this->createMockEntry('msg-1', OutboxMessageType::EVENT, 6);

        $this->outboxRepository
            ->shouldReceive('findUnpublished')
            ->once()
            ->andReturn([$entry]);

        // Should not be published because retry_count > max_retries
        $this->publisher->shouldNotReceive('publish');

        $exitCode = $this->commandTester->execute([
            '--run-once' => true,
            '--max-retries' => 5,
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
    }

    #[Test]
    public function executeDoesNotMarkAsFailedWhenMaxRetriesExceeded(): void
    {
        $entry = $this->createMockEntry('msg-1', OutboxMessageType::EVENT, 5);

        $this->outboxRepository
            ->shouldReceive('findUnpublished')
            ->once()
            ->andReturn([$entry]);

        $this->publisher
            ->shouldReceive('publish')
            ->once()
            ->andThrow(new \RuntimeException('Failed'));

        $this->logger->shouldReceive('error')->once();

        // Should NOT call markAsFailed when retry_count >= max_retries
        $this->outboxRepository->shouldNotReceive('markAsFailed');

        $exitCode = $this->commandTester->execute([
            '--run-once' => true,
            '--max-retries' => 5,
        ]);

        self::assertSame(Command::FAILURE, $exitCode);
    }

    // =========================================================================
    // Critical Error Handling Tests
    // =========================================================================

    #[Test]
    public function executeHandlesCriticalRepositoryError(): void
    {
        $this->outboxRepository
            ->shouldReceive('findUnpublished')
            ->once()
            ->andThrow(new \RuntimeException('Database connection lost'));

        $this->logger
            ->shouldReceive('critical')
            ->once()
            ->with('Outbox publisher error', Mockery::type('array'));

        // Note: With --run-once, it should exit after the error
        // The implementation sleeps and then exits the loop
        $exitCode = $this->commandTester->execute([
            '--run-once' => true,
            '--poll-interval' => 0, // Minimize sleep
        ]);

        self::assertStringContainsString('Publisher error', $this->commandTester->getDisplay());
    }

    // =========================================================================
    // Invalid Message Type Tests
    // =========================================================================

    #[Test]
    public function executeWithInvalidMessageTypeLogsErrorAndContinues(): void
    {
        // When an invalid message type is provided, parseMessageType throws
        // InvalidArgumentException which is caught by the outer try/catch.
        // The error is logged via logger->critical and the command continues.
        $this->logger
            ->shouldReceive('critical')
            ->once()
            ->with('Outbox publisher error', Mockery::on(function (array $context): bool {
                return str_contains($context['error'], 'Invalid message type: invalid')
                    && isset($context['trace']);
            }));

        $exitCode = $this->commandTester->execute([
            '--run-once' => true,
            '--message-type' => 'invalid',
            '--poll-interval' => 0, // Minimize sleep
        ]);

        // Command catches the error, so it completes with SUCCESS (no failed messages processed)
        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Publisher error', $this->commandTester->getDisplay());
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    private function createMockEntry(
        string $id,
        OutboxMessageType $type,
        int $retryCount,
    ): OutboxEntryInterface&MockInterface {
        $entry = Mockery::mock(OutboxEntryInterface::class);

        $entry->shouldReceive('getId')->andReturn($id);
        $entry->shouldReceive('getMessageType')->andReturn($type);
        $entry->shouldReceive('getRetryCount')->andReturn($retryCount);
        $entry->shouldReceive('getEventType')->andReturn('TestEvent');
        $entry->shouldReceive('getCreatedAt')->andReturn(new DateTimeImmutable());
        $entry->shouldReceive('getTopic')->andReturn('test.topic');
        $entry->shouldReceive('getRoutingKey')->andReturn('test.routing.key');
        $entry->shouldReceive('getAggregateType')->andReturn('TestAggregate');
        $entry->shouldReceive('getAggregateId')->andReturn('aggregate-123');
        $entry->shouldReceive('getPayload')->andReturn('{}');

        return $entry;
    }
}
