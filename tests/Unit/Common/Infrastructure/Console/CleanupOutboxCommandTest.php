<?php

declare(strict_types=1);

namespace Micro\Tests\Unit\Common\Infrastructure\Console;

use DateTimeImmutable;
use Micro\Component\Common\Domain\Outbox\OutboxRepositoryInterface;
use Micro\Component\Common\Infrastructure\Console\CleanupOutboxCommand;
use Micro\Component\Common\Infrastructure\Outbox\Metrics\OutboxMetricsInterface;
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
 * Unit tests for CleanupOutboxCommand.
 *
 * @see CleanupOutboxCommand
 */
#[CoversClass(CleanupOutboxCommand::class)]
final class CleanupOutboxCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private OutboxRepositoryInterface&MockInterface $outboxRepository;
    private OutboxMetricsInterface&MockInterface $metrics;
    private LoggerInterface&MockInterface $logger;
    private CleanupOutboxCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->outboxRepository = Mockery::mock(OutboxRepositoryInterface::class);
        $this->metrics = Mockery::mock(OutboxMetricsInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->command = new CleanupOutboxCommand(
            $this->outboxRepository,
            $this->metrics,
            $this->logger,
        );

        $this->commandTester = new CommandTester($this->command);
    }

    // =========================================================================
    // Command Configuration Tests
    // =========================================================================

    #[Test]
    public function commandHasCorrectName(): void
    {
        self::assertSame('app:outbox:cleanup', $this->command->getName());
    }

    #[Test]
    public function commandHasCorrectDescription(): void
    {
        self::assertSame(
            'Clean up old published outbox messages',
            $this->command->getDescription()
        );
    }

    #[Test]
    public function commandHasRequiredOptions(): void
    {
        $definition = $this->command->getDefinition();

        self::assertTrue($definition->hasOption('retention'));
        self::assertTrue($definition->hasOption('batch-size'));
        self::assertTrue($definition->hasOption('dry-run'));
        self::assertTrue($definition->hasOption('include-failed'));
    }

    // =========================================================================
    // Dry Run Tests
    // =========================================================================

    #[Test]
    public function executeDryRunShowsCountWithoutDeleting(): void
    {
        $this->outboxRepository
            ->shouldReceive('countPublishedBefore')
            ->once()
            ->with(Mockery::type(DateTimeImmutable::class))
            ->andReturn(150);

        // Should NOT call delete methods in dry-run
        $this->outboxRepository->shouldNotReceive('deletePublishedBefore');
        $this->outboxRepository->shouldNotReceive('deleteFailedExceedingRetries');
        $this->metrics->shouldNotReceive('recordCleanup');

        $exitCode = $this->commandTester->execute([
            '--dry-run' => true,
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('DRY RUN MODE', $this->commandTester->getDisplay());
        self::assertStringContainsString('Would delete 150 records', $this->commandTester->getDisplay());
    }

    #[Test]
    public function executeDryRunWithIncludeFailedShowsCombinedCount(): void
    {
        $this->outboxRepository
            ->shouldReceive('countPublishedBefore')
            ->once()
            ->andReturn(100);

        $this->outboxRepository
            ->shouldReceive('countFailedExceedingRetries')
            ->once()
            ->with(5)
            ->andReturn(25);

        $exitCode = $this->commandTester->execute([
            '--dry-run' => true,
            '--include-failed' => true,
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Would delete 125 records', $this->commandTester->getDisplay());
        self::assertStringContainsString('Failed messages exceeding retries: 25', $this->commandTester->getDisplay());
    }

    // =========================================================================
    // Successful Cleanup Tests
    // =========================================================================

    #[Test]
    public function executeDeletesPublishedMessagesSuccessfully(): void
    {
        // First batch returns less than batch size (500 < 1000), so loop stops
        $this->outboxRepository
            ->shouldReceive('deletePublishedBefore')
            ->once()
            ->with(Mockery::type(DateTimeImmutable::class), 1000)
            ->andReturn(500);

        $this->metrics
            ->shouldReceive('recordCleanup')
            ->once()
            ->with(500, Mockery::type('float'));

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Outbox cleanup completed', Mockery::type('array'));

        $exitCode = $this->commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Records deleted: 500', $this->commandTester->getDisplay());
        self::assertStringContainsString('Cleanup completed successfully', $this->commandTester->getDisplay());
    }

    #[Test]
    public function executeDeletesInMultipleBatches(): void
    {
        // Multiple batches until less than batch size is returned
        $this->outboxRepository
            ->shouldReceive('deletePublishedBefore')
            ->once()
            ->andReturn(1000); // Full batch

        $this->outboxRepository
            ->shouldReceive('deletePublishedBefore')
            ->once()
            ->andReturn(1000); // Full batch

        $this->outboxRepository
            ->shouldReceive('deletePublishedBefore')
            ->once()
            ->andReturn(500); // Partial batch - stops

        $this->metrics
            ->shouldReceive('recordCleanup')
            ->once()
            ->with(2500, Mockery::type('float'));

        $this->logger->shouldReceive('info')->once();

        $exitCode = $this->commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Records deleted: 2500', $this->commandTester->getDisplay());
    }

    #[Test]
    public function executeWithCustomRetentionPeriod(): void
    {
        $this->outboxRepository
            ->shouldReceive('deletePublishedBefore')
            ->once()
            ->with(
                Mockery::on(function (DateTimeImmutable $date): bool {
                    // Verify the cutoff date is approximately 30 days ago
                    $expected = new DateTimeImmutable('-30 days');
                    $diff = abs($date->getTimestamp() - $expected->getTimestamp());
                    return $diff < 60; // Allow 1 minute tolerance
                }),
                1000
            )
            ->andReturn(0);

        $this->metrics->shouldReceive('recordCleanup')->once();
        $this->logger->shouldReceive('info')->once();

        $exitCode = $this->commandTester->execute([
            '--retention' => 30,
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Retention period: 30 days', $this->commandTester->getDisplay());
    }

    #[Test]
    public function executeWithCustomBatchSize(): void
    {
        $this->outboxRepository
            ->shouldReceive('deletePublishedBefore')
            ->once()
            ->with(Mockery::type(DateTimeImmutable::class), 500)
            ->andReturn(0);

        $this->metrics->shouldReceive('recordCleanup')->once();
        $this->logger->shouldReceive('info')->once();

        $exitCode = $this->commandTester->execute([
            '--batch-size' => 500,
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Batch size: 500', $this->commandTester->getDisplay());
    }

    // =========================================================================
    // Include Failed Messages Tests
    // =========================================================================

    #[Test]
    public function executeWithIncludeFailedDeletesFailedMessages(): void
    {
        // Published messages cleanup - returns less than batch size, so single call
        $this->outboxRepository
            ->shouldReceive('deletePublishedBefore')
            ->once()
            ->with(Mockery::type(DateTimeImmutable::class), 1000)
            ->andReturn(100);

        // Failed messages cleanup - returns less than batch size, so single call
        $this->outboxRepository
            ->shouldReceive('deleteFailedExceedingRetries')
            ->once()
            ->with(5, 1000)
            ->andReturn(50);

        $this->metrics
            ->shouldReceive('recordCleanup')
            ->once()
            ->with(150, Mockery::type('float'));

        $this->logger->shouldReceive('info')->once();

        $exitCode = $this->commandTester->execute([
            '--include-failed' => true,
        ]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Records deleted: 150', $this->commandTester->getDisplay());
        self::assertStringContainsString('Failed messages deleted: 50', $this->commandTester->getDisplay());
    }

    // =========================================================================
    // Error Handling Tests
    // =========================================================================

    #[Test]
    public function executeHandlesRepositoryError(): void
    {
        $this->outboxRepository
            ->shouldReceive('deletePublishedBefore')
            ->once()
            ->andThrow(new \RuntimeException('Database error'));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Outbox cleanup failed', Mockery::on(function (array $context): bool {
                return $context['error'] === 'Database error'
                    && isset($context['trace'])
                    && $context['deleted_before_failure'] === 0;
            }));

        // Metrics should NOT be recorded on failure
        $this->metrics->shouldNotReceive('recordCleanup');

        $exitCode = $this->commandTester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Cleanup failed', $this->commandTester->getDisplay());
        self::assertStringContainsString('Database error', $this->commandTester->getDisplay());
    }

    #[Test]
    public function executeLogsPartialDeletionOnError(): void
    {
        // First batch succeeds
        $this->outboxRepository
            ->shouldReceive('deletePublishedBefore')
            ->once()
            ->andReturn(1000);

        // Second batch fails
        $this->outboxRepository
            ->shouldReceive('deletePublishedBefore')
            ->once()
            ->andThrow(new \RuntimeException('Connection lost'));

        $this->logger
            ->shouldReceive('error')
            ->once()
            ->with('Outbox cleanup failed', Mockery::on(function (array $context): bool {
                return $context['deleted_before_failure'] === 1000;
            }));

        $exitCode = $this->commandTester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Records deleted before failure: 1000', $this->commandTester->getDisplay());
    }

    // =========================================================================
    // Logging Tests
    // =========================================================================

    #[Test]
    public function executeLogsSuccessfulCleanupWithCorrectContext(): void
    {
        $this->outboxRepository
            ->shouldReceive('deletePublishedBefore')
            ->andReturn(250, 0);

        $this->metrics->shouldReceive('recordCleanup')->once();

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Outbox cleanup completed', Mockery::on(function (array $context): bool {
                return $context['deleted_count'] === 250
                    && $context['retention_days'] === 7
                    && isset($context['cutoff_date'])
                    && isset($context['duration_seconds'])
                    && $context['included_failed'] === false;
            }));

        $this->commandTester->execute([]);
    }

    #[Test]
    public function executeLogsIncludeFailedFlagInContext(): void
    {
        $this->outboxRepository
            ->shouldReceive('deletePublishedBefore')
            ->andReturn(0);

        $this->outboxRepository
            ->shouldReceive('deleteFailedExceedingRetries')
            ->andReturn(0);

        $this->metrics->shouldReceive('recordCleanup')->once();

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Outbox cleanup completed', Mockery::on(function (array $context): bool {
                return $context['included_failed'] === true;
            }));

        $this->commandTester->execute([
            '--include-failed' => true,
        ]);
    }

    // =========================================================================
    // Metrics Recording Tests
    // =========================================================================

    #[Test]
    public function executeRecordsMetricsWithCorrectValues(): void
    {
        $this->outboxRepository
            ->shouldReceive('deletePublishedBefore')
            ->andReturn(300, 0);

        $this->metrics
            ->shouldReceive('recordCleanup')
            ->once()
            ->with(
                300,
                Mockery::on(function (float $duration): bool {
                    return $duration >= 0 && $duration < 60; // Reasonable duration
                })
            );

        $this->logger->shouldReceive('info')->once();

        $this->commandTester->execute([]);
    }

    // =========================================================================
    // Zero Records Tests
    // =========================================================================

    #[Test]
    public function executeWithNoRecordsToDeleteReturnsSuccess(): void
    {
        $this->outboxRepository
            ->shouldReceive('deletePublishedBefore')
            ->once()
            ->andReturn(0);

        $this->metrics
            ->shouldReceive('recordCleanup')
            ->once()
            ->with(0, Mockery::type('float'));

        $this->logger->shouldReceive('info')->once();

        $exitCode = $this->commandTester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Records deleted: 0', $this->commandTester->getDisplay());
    }
}
