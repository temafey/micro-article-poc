<?php

declare(strict_types=1);

namespace Tests\Integration\Common\Infrastructure\Outbox;

use DateTimeImmutable;
use Micro\Component\Common\Domain\Outbox\OutboxMessageType;
use Micro\Component\Common\Domain\Outbox\OutboxPersistenceException;
use Micro\Component\Common\Domain\Outbox\OutboxRepositoryInterface;
use Micro\Component\Common\Infrastructure\Outbox\DbalOutboxRepository;
use Micro\Component\Common\Infrastructure\Outbox\OutboxEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid;
use Tests\Integration\IntegrationTestCase;

/**
 * Integration tests for DbalOutboxRepository.
 *
 * Tests the DBAL-based outbox repository with a real PostgreSQL database.
 * Verifies CRUD operations, query methods, and transaction handling.
 *
 * @see DbalOutboxRepository
 * @see ADR-006: Transactional Outbox Pattern
 * @see TASK-14.1: Database & Core Components
 */
#[CoversClass(DbalOutboxRepository::class)]
#[Group('integration')]
#[Group('repository')]
#[Group('outbox')]
final class DbalOutboxRepositoryIntegrationTest extends IntegrationTestCase
{
    private const TABLE_NAME = 'outbox';

    /**
     * Disable auto-transactions to allow manual transaction testing.
     */
    protected bool $useTransaction = false;

    private OutboxRepositoryInterface $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->getService(OutboxRepositoryInterface::class);

        // Clear outbox table before each test for isolation
        $this->executeStatement('DELETE FROM ' . self::TABLE_NAME);
    }

    // =========================================================================
    // save() Tests
    // =========================================================================

    #[Test]
    public function saveShouldPersistEventEntry(): void
    {
        // Arrange
        $entry = $this->createEventEntry();

        // Act
        $this->repository->save($entry);

        // Assert
        $this->assertDatabaseHas(self::TABLE_NAME, [
            'id' => $entry->getId(),
            'message_type' => OutboxMessageType::EVENT->value,
            'aggregate_type' => $entry->getAggregateType(),
            'aggregate_id' => $entry->getAggregateId(),
        ]);
    }

    #[Test]
    public function saveShouldPersistTaskEntry(): void
    {
        // Arrange
        $entry = $this->createTaskEntry();

        // Act
        $this->repository->save($entry);

        // Assert
        $this->assertDatabaseHas(self::TABLE_NAME, [
            'id' => $entry->getId(),
            'message_type' => OutboxMessageType::TASK->value,
        ]);
    }

    #[Test]
    public function saveShouldAssignSequenceNumber(): void
    {
        // Arrange
        $entry = $this->createEventEntry();

        // Act
        $this->repository->save($entry);

        // Assert
        $rows = $this->executeQuery(
            'SELECT sequence_number FROM ' . self::TABLE_NAME . ' WHERE id = :id',
            ['id' => $entry->getId()]
        );

        self::assertNotEmpty($rows);
        self::assertGreaterThan(0, $rows[0]['sequence_number']);
    }

    #[Test]
    public function saveShouldPreserveEntryPayload(): void
    {
        // Arrange
        $payload = json_encode(['title' => 'Test Article', 'content' => 'Test Content']);
        $entry = OutboxEntry::createForEvent(
            aggregateType: 'Article',
            aggregateId: Uuid::uuid4()->toString(),
            eventType: 'ArticleCreated',
            eventPayload: $payload,
            topic: 'events.article',
            routingKey: 'event.article.created',
        );

        // Act
        $this->repository->save($entry);

        // Assert
        $rows = $this->executeQuery(
            'SELECT event_payload FROM ' . self::TABLE_NAME . ' WHERE id = :id',
            ['id' => $entry->getId()]
        );

        // Compare as decoded JSON since PostgreSQL may normalize whitespace
        self::assertSame(
            json_decode($payload, true),
            json_decode($rows[0]['event_payload'], true)
        );
    }

    // =========================================================================
    // saveAll() Tests
    // =========================================================================

    #[Test]
    public function saveAllShouldPersistMultipleEntries(): void
    {
        // Arrange
        $entries = [
            $this->createEventEntry(),
            $this->createEventEntry(),
            $this->createEventEntry(),
        ];

        // Act
        $this->repository->saveAll($entries);

        // Assert
        foreach ($entries as $entry) {
            $this->assertDatabaseHas(self::TABLE_NAME, ['id' => $entry->getId()]);
        }
    }

    #[Test]
    public function saveAllShouldAssignIncrementingSequenceNumbers(): void
    {
        // Arrange
        $entries = [
            $this->createEventEntry(),
            $this->createEventEntry(),
        ];

        // Act
        $this->repository->saveAll($entries);

        // Assert
        $rows = $this->executeQuery(
            'SELECT id, sequence_number FROM ' . self::TABLE_NAME . ' ORDER BY sequence_number ASC'
        );

        self::assertCount(2, $rows);
        self::assertLessThan($rows[1]['sequence_number'], $rows[0]['sequence_number']);
    }

    #[Test]
    public function saveAllWithEmptyArrayShouldDoNothing(): void
    {
        // Act
        $this->repository->saveAll([]);

        // Assert
        $count = $this->executeQuery('SELECT COUNT(*) as cnt FROM ' . self::TABLE_NAME);
        self::assertSame(0, (int) $count[0]['cnt']);
    }

    // =========================================================================
    // findById() Tests
    // =========================================================================

    #[Test]
    public function findByIdShouldReturnExistingEntry(): void
    {
        // Arrange
        $entry = $this->createEventEntry();
        $this->repository->save($entry);

        // Act
        $found = $this->repository->findById($entry->getId());

        // Assert
        self::assertNotNull($found);
        self::assertSame($entry->getId(), $found->getId());
        self::assertSame($entry->getAggregateType(), $found->getAggregateType());
        // Compare decoded JSON to avoid formatting differences (PostgreSQL JSONB normalizes format)
        self::assertEquals(
            json_decode($entry->getEventPayload(), true),
            json_decode($found->getEventPayload(), true)
        );
    }

    #[Test]
    public function findByIdShouldReturnNullForNonExistentEntry(): void
    {
        // Act
        $found = $this->repository->findById(Uuid::uuid4()->toString());

        // Assert
        self::assertNull($found);
    }

    // =========================================================================
    // findUnpublished() Tests
    // =========================================================================

    #[Test]
    public function findUnpublishedShouldReturnPendingEntries(): void
    {
        // Arrange
        $entry1 = $this->createEventEntry();
        $entry2 = $this->createEventEntry();
        $this->repository->saveAll([$entry1, $entry2]);

        // Act
        $unpublished = $this->repository->findUnpublished(10);

        // Assert
        self::assertCount(2, $unpublished);
    }

    #[Test]
    public function findUnpublishedShouldExcludePublishedEntries(): void
    {
        // Arrange
        $entry = $this->createEventEntry();
        $this->repository->save($entry);
        $this->repository->markAsPublished([$entry->getId()], new DateTimeImmutable());

        // Act
        $unpublished = $this->repository->findUnpublished(10);

        // Assert
        self::assertEmpty($unpublished);
    }

    #[Test]
    public function findUnpublishedShouldRespectLimit(): void
    {
        // Arrange
        $entries = [
            $this->createEventEntry(),
            $this->createEventEntry(),
            $this->createEventEntry(),
        ];
        $this->repository->saveAll($entries);

        // Act
        $unpublished = $this->repository->findUnpublished(2);

        // Assert
        self::assertCount(2, $unpublished);
    }

    #[Test]
    public function findUnpublishedShouldOrderBySequenceNumber(): void
    {
        // Arrange
        $entry1 = $this->createEventEntry();
        $entry2 = $this->createEventEntry();
        $this->repository->save($entry1);
        $this->repository->save($entry2);

        // Act
        $unpublished = $this->repository->findUnpublished(10);

        // Assert
        self::assertCount(2, $unpublished);
        self::assertLessThan(
            $unpublished[1]->getSequenceNumber(),
            $unpublished[0]->getSequenceNumber()
        );
    }

    #[Test]
    public function findUnpublishedShouldExcludeEntriesWithFutureRetryTime(): void
    {
        // Arrange
        $entry = $this->createEventEntry();
        $this->repository->save($entry);

        // Mark as failed with future retry time
        $futureRetryAt = (new DateTimeImmutable())->modify('+1 hour');
        $this->repository->markAsFailed($entry->getId(), 'Test error', $futureRetryAt);

        // Act
        $unpublished = $this->repository->findUnpublished(10);

        // Assert
        self::assertEmpty($unpublished);
    }

    #[Test]
    public function findUnpublishedShouldIncludeEntriesWithPastRetryTime(): void
    {
        // Arrange
        $entry = $this->createEventEntry();
        $this->repository->save($entry);

        // Mark as failed with past retry time
        $pastRetryAt = (new DateTimeImmutable())->modify('-1 minute');
        $this->repository->markAsFailed($entry->getId(), 'Test error', $pastRetryAt);

        // Act
        $unpublished = $this->repository->findUnpublished(10);

        // Assert
        self::assertCount(1, $unpublished);
    }

    // =========================================================================
    // findUnpublishedByType() Tests
    // =========================================================================

    #[Test]
    public function findUnpublishedByTypeShouldFilterByEventType(): void
    {
        // Arrange
        $eventEntry = $this->createEventEntry();
        $taskEntry = $this->createTaskEntry();
        $this->repository->saveAll([$eventEntry, $taskEntry]);

        // Act
        $events = $this->repository->findUnpublishedByType(OutboxMessageType::EVENT, 10);

        // Assert
        self::assertCount(1, $events);
        self::assertSame(OutboxMessageType::EVENT, $events[0]->getMessageType());
    }

    #[Test]
    public function findUnpublishedByTypeShouldFilterByTaskType(): void
    {
        // Arrange
        $eventEntry = $this->createEventEntry();
        $taskEntry = $this->createTaskEntry();
        $this->repository->saveAll([$eventEntry, $taskEntry]);

        // Act
        $tasks = $this->repository->findUnpublishedByType(OutboxMessageType::TASK, 10);

        // Assert
        self::assertCount(1, $tasks);
        self::assertSame(OutboxMessageType::TASK, $tasks[0]->getMessageType());
    }

    // =========================================================================
    // markAsPublished() Tests
    // =========================================================================

    #[Test]
    public function markAsPublishedShouldUpdatePublishedAt(): void
    {
        // Arrange
        $entry = $this->createEventEntry();
        $this->repository->save($entry);
        $publishedAt = new DateTimeImmutable();

        // Act
        $updated = $this->repository->markAsPublished([$entry->getId()], $publishedAt);

        // Assert
        self::assertSame(1, $updated);

        $found = $this->repository->findById($entry->getId());
        self::assertNotNull($found->getPublishedAt());
    }

    #[Test]
    public function markAsPublishedShouldClearErrorFields(): void
    {
        // Arrange
        $entry = $this->createEventEntry();
        $this->repository->save($entry);
        $this->repository->markAsFailed($entry->getId(), 'Test error', new DateTimeImmutable());
        $publishedAt = new DateTimeImmutable();

        // Act
        $this->repository->markAsPublished([$entry->getId()], $publishedAt);

        // Assert
        $found = $this->repository->findById($entry->getId());
        self::assertNull($found->getLastError());
        self::assertNull($found->getNextRetryAt());
    }

    #[Test]
    public function markAsPublishedShouldHandleMultipleIds(): void
    {
        // Arrange
        $entries = [
            $this->createEventEntry(),
            $this->createEventEntry(),
            $this->createEventEntry(),
        ];
        $this->repository->saveAll($entries);
        $publishedAt = new DateTimeImmutable();

        // Act
        $ids = array_map(fn ($e) => $e->getId(), $entries);
        $updated = $this->repository->markAsPublished($ids, $publishedAt);

        // Assert
        self::assertSame(3, $updated);
    }

    #[Test]
    public function markAsPublishedWithEmptyIdsShouldReturnZero(): void
    {
        // Act
        $updated = $this->repository->markAsPublished([], new DateTimeImmutable());

        // Assert
        self::assertSame(0, $updated);
    }

    #[Test]
    public function markAsPublishedShouldNotUpdateAlreadyPublishedEntries(): void
    {
        // Arrange
        $entry = $this->createEventEntry();
        $this->repository->save($entry);
        $this->repository->markAsPublished([$entry->getId()], new DateTimeImmutable());

        // Act - try to publish again
        $updated = $this->repository->markAsPublished([$entry->getId()], new DateTimeImmutable());

        // Assert
        self::assertSame(0, $updated);
    }

    // =========================================================================
    // markAsFailed() Tests
    // =========================================================================

    #[Test]
    public function markAsFailedShouldIncrementRetryCount(): void
    {
        // Arrange
        $entry = $this->createEventEntry();
        $this->repository->save($entry);
        $nextRetryAt = (new DateTimeImmutable())->modify('+5 minutes');

        // Act
        $this->repository->markAsFailed($entry->getId(), 'Connection error', $nextRetryAt);

        // Assert
        $found = $this->repository->findById($entry->getId());
        self::assertSame(1, $found->getRetryCount());
    }

    #[Test]
    public function markAsFailedShouldStoreErrorMessage(): void
    {
        // Arrange
        $entry = $this->createEventEntry();
        $this->repository->save($entry);
        $errorMessage = 'Connection refused: timeout after 30s';
        $nextRetryAt = (new DateTimeImmutable())->modify('+5 minutes');

        // Act
        $this->repository->markAsFailed($entry->getId(), $errorMessage, $nextRetryAt);

        // Assert
        $found = $this->repository->findById($entry->getId());
        self::assertSame($errorMessage, $found->getLastError());
    }

    #[Test]
    public function markAsFailedShouldSetNextRetryAt(): void
    {
        // Arrange
        $entry = $this->createEventEntry();
        $this->repository->save($entry);
        $nextRetryAt = (new DateTimeImmutable())->modify('+10 minutes');

        // Act
        $this->repository->markAsFailed($entry->getId(), 'Error', $nextRetryAt);

        // Assert
        $found = $this->repository->findById($entry->getId());
        self::assertNotNull($found->getNextRetryAt());
    }

    #[Test]
    public function markAsFailedShouldThrowForNonExistentEntry(): void
    {
        // Arrange
        $nonExistentId = Uuid::uuid4()->toString();
        $nextRetryAt = (new DateTimeImmutable())->modify('+5 minutes');

        // Assert
        $this->expectException(OutboxPersistenceException::class);

        // Act
        $this->repository->markAsFailed($nonExistentId, 'Error', $nextRetryAt);
    }

    #[Test]
    public function markAsFailedMultipleTimesShouldAccumulateRetryCount(): void
    {
        // Arrange
        $entry = $this->createEventEntry();
        $this->repository->save($entry);
        $nextRetryAt = new DateTimeImmutable();

        // Act
        $this->repository->markAsFailed($entry->getId(), 'Error 1', $nextRetryAt);
        $this->repository->markAsFailed($entry->getId(), 'Error 2', $nextRetryAt);
        $this->repository->markAsFailed($entry->getId(), 'Error 3', $nextRetryAt);

        // Assert
        $found = $this->repository->findById($entry->getId());
        self::assertSame(3, $found->getRetryCount());
        self::assertSame('Error 3', $found->getLastError());
    }

    // =========================================================================
    // deletePublishedOlderThan() Tests
    // =========================================================================

    #[Test]
    public function deletePublishedOlderThanShouldRemoveOldPublishedEntries(): void
    {
        // Arrange
        $entry = $this->createEventEntry();
        $this->repository->save($entry);
        $publishedAt = (new DateTimeImmutable())->modify('-2 days');
        $this->repository->markAsPublished([$entry->getId()], $publishedAt);

        // Manually update published_at to simulate old entry
        $this->executeStatement(
            'UPDATE ' . self::TABLE_NAME . ' SET published_at = :published_at WHERE id = :id',
            ['id' => $entry->getId(), 'published_at' => $publishedAt->format('Y-m-d H:i:s.u')]
        );

        $cutoff = (new DateTimeImmutable())->modify('-1 day');

        // Act
        $deleted = $this->repository->deletePublishedOlderThan($cutoff);

        // Assert
        self::assertSame(1, $deleted);
        $this->assertDatabaseMissing(self::TABLE_NAME, ['id' => $entry->getId()]);
    }

    #[Test]
    public function deletePublishedOlderThanShouldNotDeleteUnpublishedEntries(): void
    {
        // Arrange
        $entry = $this->createEventEntry();
        $this->repository->save($entry);
        $cutoff = new DateTimeImmutable();

        // Act
        $deleted = $this->repository->deletePublishedOlderThan($cutoff);

        // Assert
        self::assertSame(0, $deleted);
        $this->assertDatabaseHas(self::TABLE_NAME, ['id' => $entry->getId()]);
    }

    // =========================================================================
    // deletePublishedBefore() Tests
    // =========================================================================

    #[Test]
    public function deletePublishedBeforeShouldRespectLimit(): void
    {
        // Arrange - create 5 entries and publish them with old dates
        $entries = [];
        for ($i = 0; $i < 5; ++$i) {
            $entry = $this->createEventEntry();
            $this->repository->save($entry);
            $entries[] = $entry;
        }

        // Publish all entries
        $ids = array_map(fn ($e) => $e->getId(), $entries);
        $this->repository->markAsPublished($ids, new DateTimeImmutable());

        // Update published_at to old date
        $oldDate = (new DateTimeImmutable())->modify('-10 days');
        $this->executeStatement(
            'UPDATE ' . self::TABLE_NAME . ' SET published_at = :published_at',
            ['published_at' => $oldDate->format('Y-m-d H:i:s.u')]
        );

        $cutoff = (new DateTimeImmutable())->modify('-1 day');

        // Act
        $deleted = $this->repository->deletePublishedBefore($cutoff, 2);

        // Assert
        self::assertSame(2, $deleted);

        // Verify 3 entries remain
        $remaining = $this->executeQuery('SELECT COUNT(*) as cnt FROM ' . self::TABLE_NAME);
        self::assertSame(3, (int) $remaining[0]['cnt']);
    }

    // =========================================================================
    // countPublishedBefore() Tests
    // =========================================================================

    #[Test]
    public function countPublishedBeforeShouldReturnCorrectCount(): void
    {
        // Arrange
        $entry1 = $this->createEventEntry();
        $entry2 = $this->createEventEntry();
        $this->repository->saveAll([$entry1, $entry2]);

        // Publish both
        $ids = [$entry1->getId(), $entry2->getId()];
        $this->repository->markAsPublished($ids, new DateTimeImmutable());

        // Update to old date
        $oldDate = (new DateTimeImmutable())->modify('-5 days');
        $this->executeStatement(
            'UPDATE ' . self::TABLE_NAME . ' SET published_at = :published_at',
            ['published_at' => $oldDate->format('Y-m-d H:i:s.u')]
        );

        $cutoff = (new DateTimeImmutable())->modify('-1 day');

        // Act
        $count = $this->repository->countPublishedBefore($cutoff);

        // Assert
        self::assertSame(2, $count);
    }

    // =========================================================================
    // countFailedExceedingRetries() Tests
    // =========================================================================

    #[Test]
    public function countFailedExceedingRetriesShouldReturnCorrectCount(): void
    {
        // Arrange
        $entry = $this->createEventEntry();
        $this->repository->save($entry);

        // Simulate multiple failures
        for ($i = 0; $i < 6; ++$i) {
            $this->repository->markAsFailed($entry->getId(), 'Error ' . $i, new DateTimeImmutable());
        }

        // Act
        $count = $this->repository->countFailedExceedingRetries(5);

        // Assert
        self::assertSame(1, $count);
    }

    #[Test]
    public function countFailedExceedingRetriesShouldExcludeEntriesBelowThreshold(): void
    {
        // Arrange
        $entry = $this->createEventEntry();
        $this->repository->save($entry);

        // Simulate 3 failures
        for ($i = 0; $i < 3; ++$i) {
            $this->repository->markAsFailed($entry->getId(), 'Error ' . $i, new DateTimeImmutable());
        }

        // Act
        $count = $this->repository->countFailedExceedingRetries(5);

        // Assert
        self::assertSame(0, $count);
    }

    // =========================================================================
    // deleteFailedExceedingRetries() Tests
    // =========================================================================

    #[Test]
    public function deleteFailedExceedingRetriesShouldRemoveDeadLetterEntries(): void
    {
        // Arrange
        $entry = $this->createEventEntry();
        $this->repository->save($entry);

        // Simulate failures exceeding max retries
        for ($i = 0; $i < 6; ++$i) {
            $this->repository->markAsFailed($entry->getId(), 'Error ' . $i, new DateTimeImmutable());
        }

        // Act
        $deleted = $this->repository->deleteFailedExceedingRetries(5, 10);

        // Assert
        self::assertSame(1, $deleted);
        $this->assertDatabaseMissing(self::TABLE_NAME, ['id' => $entry->getId()]);
    }

    // =========================================================================
    // getMetrics() Tests
    // =========================================================================

    #[Test]
    public function getMetricsShouldReturnCorrectCounts(): void
    {
        // Arrange
        $eventEntry = $this->createEventEntry();
        $taskEntry = $this->createTaskEntry();
        $this->repository->saveAll([$eventEntry, $taskEntry]);

        // Act
        $metrics = $this->repository->getMetrics();

        // Assert
        self::assertSame(2, $metrics['total_pending']);
        self::assertSame(1, $metrics['total_events']);
        self::assertSame(1, $metrics['total_tasks']);
        self::assertSame(0, $metrics['failed_count']);
    }

    #[Test]
    public function getMetricsShouldCountFailedEntries(): void
    {
        // Arrange
        $entry = $this->createEventEntry();
        $this->repository->save($entry);
        $this->repository->markAsFailed($entry->getId(), 'Error', new DateTimeImmutable());

        // Act
        $metrics = $this->repository->getMetrics();

        // Assert
        self::assertSame(1, $metrics['failed_count']);
    }

    #[Test]
    public function getMetricsShouldReturnOldestPendingAge(): void
    {
        // Arrange
        $entry = $this->createEventEntry();
        $this->repository->save($entry);

        // Act
        $metrics = $this->repository->getMetrics();

        // Assert
        self::assertNotNull($metrics['oldest_pending_seconds']);
        self::assertGreaterThanOrEqual(0, $metrics['oldest_pending_seconds']);
    }

    // =========================================================================
    // countByStatus() Tests
    // =========================================================================

    #[Test]
    public function countByStatusShouldReturnCorrectCounts(): void
    {
        // Arrange
        $entry1 = $this->createEventEntry();
        $entry2 = $this->createEventEntry();
        $entry3 = $this->createEventEntry();
        $this->repository->saveAll([$entry1, $entry2, $entry3]);

        // Publish one
        $this->repository->markAsPublished([$entry1->getId()], new DateTimeImmutable());

        // Fail one
        $this->repository->markAsFailed($entry2->getId(), 'Error', new DateTimeImmutable());

        // Act
        $counts = $this->repository->countByStatus();

        // Assert
        self::assertSame(1, $counts['pending']);
        self::assertSame(1, $counts['published']);
        self::assertSame(1, $counts['failed']);
    }

    // =========================================================================
    // Transaction Tests
    // =========================================================================

    #[Test]
    public function transactionMethodsShouldManageTransaction(): void
    {
        // Get the DbalOutboxRepository instance to access transaction methods
        $dbalRepo = $this->repository;

        // Act & Assert
        self::assertFalse($dbalRepo->isTransactionActive());

        $dbalRepo->beginTransaction();
        self::assertTrue($dbalRepo->isTransactionActive());

        $entry = $this->createEventEntry();
        $dbalRepo->save($entry);

        $dbalRepo->rollback();
        self::assertFalse($dbalRepo->isTransactionActive());
    }

    #[Test]
    public function commitShouldPersistChanges(): void
    {
        // Get repository
        $dbalRepo = $this->repository;

        // Act
        $dbalRepo->beginTransaction();
        $entry = $this->createEventEntry();
        $dbalRepo->save($entry);
        $dbalRepo->commit();

        // Assert
        $this->assertDatabaseHas(self::TABLE_NAME, ['id' => $entry->getId()]);

        // Cleanup
        $this->executeStatement('DELETE FROM ' . self::TABLE_NAME . ' WHERE id = :id', ['id' => $entry->getId()]);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    private function createEventEntry(?string $aggregateId = null): OutboxEntry
    {
        return OutboxEntry::createForEvent(
            aggregateType: 'Article',
            aggregateId: $aggregateId ?? Uuid::uuid4()->toString(),
            eventType: 'ArticleCreated',
            eventPayload: json_encode(['title' => 'Test', 'content' => 'Content']),
            topic: 'events.article',
            routingKey: 'event.article.created',
        );
    }

    private function createTaskEntry(?string $aggregateId = null): OutboxEntry
    {
        return OutboxEntry::createForTask(
            aggregateType: 'Email',
            aggregateId: $aggregateId ?? Uuid::uuid4()->toString(),
            commandType: 'SendWelcomeEmail',
            commandPayload: json_encode(['email' => 'test@example.com']),
            topic: 'tasks.email',
            routingKey: 'task.email.send',
        );
    }
}
