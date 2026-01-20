<?php

declare(strict_types=1);

namespace Tests\Unit\Common\Infrastructure\Outbox;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Result;
use Exception;
use Micro\Component\Common\Domain\Outbox\OutboxEntryInterface;
use Micro\Component\Common\Domain\Outbox\OutboxMessageType;
use Micro\Component\Common\Domain\Outbox\OutboxPersistenceException;
use Micro\Component\Common\Infrastructure\Outbox\DbalOutboxRepository;
use Micro\Component\Common\Infrastructure\Outbox\OutboxEntry;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Test DBAL exception that implements the DBAL Exception interface.
 */
class TestDbalException extends Exception implements DBALException
{
}

/**
 * Unit tests for DbalOutboxRepository.
 *
 * Tests database operations for the transactional outbox pattern
 * including save, find, mark, and cleanup operations.
 *
 * @see docs/tasks/phase-14-transactional-outbox/TASK-14.1-database-core-components.md
 */
#[CoversClass(DbalOutboxRepository::class)]
final class DbalOutboxRepositoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private DbalOutboxRepository $repository;
    private Connection&Mockery\MockInterface $connectionMock;

    protected function setUp(): void
    {
        $this->connectionMock = Mockery::mock(Connection::class);
        $this->repository = new DbalOutboxRepository($this->connectionMock);
    }

    // ========================================================================
    // save() Tests
    // ========================================================================

    #[Test]
    public function save_WithValidEntry_InsertsIntoDatabase(): void
    {
        // Arrange
        $entry = OutboxEntry::createForEvent(
            'Article',
            'article-123',
            'ArticleCreatedEvent',
            '{"title":"Test"}',
            'events.article',
            'event.article.created',
        );

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('fetchOne')
            ->once()
            ->andReturn('1');

        $this->connectionMock
            ->shouldReceive('executeQuery')
            ->once()
            ->with("SELECT nextval('outbox_sequence_seq')")
            ->andReturn($resultMock);

        $this->connectionMock
            ->shouldReceive('insert')
            ->once()
            ->with('outbox', Mockery::type('array'))
            ->andReturn(1);

        // Act
        $this->repository->save($entry);

        // Assert - no exception means success
        $this->assertTrue(true);
    }

    #[Test]
    public function save_WhenDbalExceptionOccurs_ThrowsOutboxPersistenceException(): void
    {
        // Arrange
        $entry = OutboxEntry::createForEvent(
            'Article',
            'article-123',
            'ArticleCreatedEvent',
            '{}',
            'events.article',
            'event.article.created',
        );

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('fetchOne')
            ->once()
            ->andReturn('1');

        $this->connectionMock
            ->shouldReceive('executeQuery')
            ->once()
            ->andReturn($resultMock);

        $this->connectionMock
            ->shouldReceive('insert')
            ->once()
            ->andThrow(new TestDbalException('Database error'));

        // Assert
        $this->expectException(OutboxPersistenceException::class);

        // Act
        $this->repository->save($entry);
    }

    // ========================================================================
    // saveAll() Tests
    // ========================================================================

    #[Test]
    public function saveAll_WithEmptyArray_DoesNothing(): void
    {
        // Arrange
        $this->connectionMock->shouldNotReceive('insert');
        $this->connectionMock->shouldNotReceive('executeQuery');

        // Act
        $this->repository->saveAll([]);

        // Assert - no exception means success
        $this->assertTrue(true);
    }

    #[Test]
    public function saveAll_WithMultipleEntries_InsertsAll(): void
    {
        // Arrange
        $entries = [
            OutboxEntry::createForEvent('Article', 'article-1', 'Event1', '{}', 'topic', 'key'),
            OutboxEntry::createForEvent('Article', 'article-2', 'Event2', '{}', 'topic', 'key'),
            OutboxEntry::createForTask('Article', 'article-3', 'Task1', '{}', 'topic', 'key'),
        ];

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('fetchOne')
            ->times(3)
            ->andReturn('1', '2', '3');

        $this->connectionMock
            ->shouldReceive('executeQuery')
            ->times(3)
            ->andReturn($resultMock);

        $this->connectionMock
            ->shouldReceive('insert')
            ->times(3)
            ->with('outbox', Mockery::type('array'))
            ->andReturn(1);

        // Act
        $this->repository->saveAll($entries);

        // Assert - no exception means success
        $this->assertTrue(true);
    }

    #[Test]
    public function saveAll_WhenDbalExceptionOccurs_ThrowsOutboxPersistenceException(): void
    {
        // Arrange
        $entries = [
            OutboxEntry::createForEvent('Article', 'article-1', 'Event1', '{}', 'topic', 'key'),
        ];

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('fetchOne')
            ->once()
            ->andReturn('1');

        $this->connectionMock
            ->shouldReceive('executeQuery')
            ->once()
            ->andReturn($resultMock);

        $this->connectionMock
            ->shouldReceive('insert')
            ->once()
            ->andThrow(new TestDbalException('Database error'));

        // Assert
        $this->expectException(OutboxPersistenceException::class);

        // Act
        $this->repository->saveAll($entries);
    }

    // ========================================================================
    // findUnpublished() Tests
    // ========================================================================

    #[Test]
    public function findUnpublished_WithResults_ReturnsArrayOfEntries(): void
    {
        // Arrange
        $rows = [
            [
                'id' => 'entry-1',
                'message_type' => 'EVENT',
                'aggregate_type' => 'Article',
                'aggregate_id' => 'article-123',
                'event_type' => 'ArticleCreatedEvent',
                'event_payload' => '{}',
                'topic' => 'events.article',
                'routing_key' => 'event.article.created',
                'created_at' => '2024-01-15 10:00:00.000000',
                'published_at' => null,
                'retry_count' => 0,
                'last_error' => null,
                'next_retry_at' => null,
                'sequence_number' => 1,
            ],
            [
                'id' => 'entry-2',
                'message_type' => 'TASK',
                'aggregate_type' => 'Article',
                'aggregate_id' => 'article-456',
                'event_type' => 'PublishCommand',
                'event_payload' => '{}',
                'topic' => 'tasks.article',
                'routing_key' => 'task.article.publish',
                'created_at' => '2024-01-15 10:01:00.000000',
                'published_at' => null,
                'retry_count' => 0,
                'last_error' => null,
                'next_retry_at' => null,
                'sequence_number' => 2,
            ],
        ];

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('fetchAllAssociative')
            ->once()
            ->andReturn($rows);

        $this->connectionMock
            ->shouldReceive('executeQuery')
            ->once()
            ->with(
                Mockery::on(fn ($sql) => str_contains($sql, 'SELECT * FROM outbox') && str_contains($sql, 'published_at IS NULL')),
                Mockery::type('array')
            )
            ->andReturn($resultMock);

        // Act
        $result = $this->repository->findUnpublished(10);

        // Assert
        $this->assertCount(2, $result);
        $this->assertInstanceOf(OutboxEntryInterface::class, $result[0]);
        $this->assertInstanceOf(OutboxEntryInterface::class, $result[1]);
        $this->assertSame('entry-1', $result[0]->getId());
        $this->assertSame('entry-2', $result[1]->getId());
    }

    #[Test]
    public function findUnpublished_WithNoResults_ReturnsEmptyArray(): void
    {
        // Arrange
        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('fetchAllAssociative')
            ->once()
            ->andReturn([]);

        $this->connectionMock
            ->shouldReceive('executeQuery')
            ->once()
            ->andReturn($resultMock);

        // Act
        $result = $this->repository->findUnpublished(10);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    // ========================================================================
    // findUnpublishedByType() Tests
    // ========================================================================

    #[Test]
    public function findUnpublishedByType_WithEventType_ReturnsOnlyEvents(): void
    {
        // Arrange
        $rows = [
            [
                'id' => 'entry-1',
                'message_type' => 'EVENT',
                'aggregate_type' => 'Article',
                'aggregate_id' => 'article-123',
                'event_type' => 'ArticleCreatedEvent',
                'event_payload' => '{}',
                'topic' => 'events.article',
                'routing_key' => 'event.article.created',
                'created_at' => '2024-01-15 10:00:00.000000',
                'published_at' => null,
                'retry_count' => 0,
                'last_error' => null,
                'next_retry_at' => null,
                'sequence_number' => 1,
            ],
        ];

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('fetchAllAssociative')
            ->once()
            ->andReturn($rows);

        $this->connectionMock
            ->shouldReceive('executeQuery')
            ->once()
            ->with(
                Mockery::on(fn ($sql) => str_contains($sql, 'message_type = :type')),
                Mockery::on(fn ($params) => $params['type'] === 'EVENT')
            )
            ->andReturn($resultMock);

        // Act
        $result = $this->repository->findUnpublishedByType(OutboxMessageType::EVENT, 10);

        // Assert
        $this->assertCount(1, $result);
        $this->assertSame(OutboxMessageType::EVENT, $result[0]->getMessageType());
    }

    // ========================================================================
    // markAsPublished() Tests
    // ========================================================================

    #[Test]
    public function markAsPublished_WithEmptyIds_ReturnsZero(): void
    {
        // Arrange
        $this->connectionMock->shouldNotReceive('executeStatement');

        // Act
        $result = $this->repository->markAsPublished([], new DateTimeImmutable());

        // Assert
        $this->assertSame(0, $result);
    }

    #[Test]
    public function markAsPublished_WithValidIds_UpdatesAndReturnsCount(): void
    {
        // Arrange
        $ids = ['entry-1', 'entry-2', 'entry-3'];
        $publishedAt = new DateTimeImmutable('2024-01-15 12:00:00');

        $this->connectionMock
            ->shouldReceive('executeStatement')
            ->once()
            ->with(
                Mockery::on(fn ($sql) => str_contains($sql, 'UPDATE outbox') && str_contains($sql, 'SET published_at = ?')),
                Mockery::type('array')
            )
            ->andReturn(3);

        // Act
        $result = $this->repository->markAsPublished($ids, $publishedAt);

        // Assert
        $this->assertSame(3, $result);
    }

    // ========================================================================
    // markAsFailed() Tests
    // ========================================================================

    #[Test]
    public function markAsFailed_WithValidId_UpdatesEntry(): void
    {
        // Arrange
        $id = 'entry-123';
        $error = 'Connection timeout';
        $nextRetryAt = new DateTimeImmutable('2024-01-15 12:05:00');

        $this->connectionMock
            ->shouldReceive('executeStatement')
            ->once()
            ->with(
                Mockery::on(fn ($sql) => str_contains($sql, 'UPDATE outbox') && str_contains($sql, 'retry_count = retry_count + 1')),
                Mockery::on(fn ($params) => $params['id'] === $id && $params['error'] === $error)
            )
            ->andReturn(1);

        // Act
        $this->repository->markAsFailed($id, $error, $nextRetryAt);

        // Assert - no exception means success
        $this->assertTrue(true);
    }

    #[Test]
    public function markAsFailed_WithNonExistentId_ThrowsException(): void
    {
        // Arrange
        $id = 'non-existent';
        $nextRetryAt = new DateTimeImmutable();

        $this->connectionMock
            ->shouldReceive('executeStatement')
            ->once()
            ->andReturn(0);

        // Assert
        $this->expectException(OutboxPersistenceException::class);

        // Act
        $this->repository->markAsFailed($id, 'Error', $nextRetryAt);
    }

    #[Test]
    public function markAsFailed_WhenDbalExceptionOccurs_ThrowsOutboxPersistenceException(): void
    {
        // Arrange
        $id = 'entry-123';
        $nextRetryAt = new DateTimeImmutable();

        $this->connectionMock
            ->shouldReceive('executeStatement')
            ->once()
            ->andThrow(new TestDbalException('Database error'));

        // Assert
        $this->expectException(OutboxPersistenceException::class);

        // Act
        $this->repository->markAsFailed($id, 'Error', $nextRetryAt);
    }

    #[Test]
    public function markAsFailed_TruncatesLongErrorMessages(): void
    {
        // Arrange
        $id = 'entry-123';
        $longError = str_repeat('Error message ', 500); // > 4000 chars
        $nextRetryAt = new DateTimeImmutable();

        $this->connectionMock
            ->shouldReceive('executeStatement')
            ->once()
            ->with(
                Mockery::type('string'),
                Mockery::on(function ($params) {
                    return strlen($params['error']) <= 4000;
                })
            )
            ->andReturn(1);

        // Act
        $this->repository->markAsFailed($id, $longError, $nextRetryAt);

        // Assert - no exception means success
        $this->assertTrue(true);
    }

    // ========================================================================
    // deletePublishedOlderThan() Tests
    // ========================================================================

    #[Test]
    public function deletePublishedOlderThan_ReturnsDeletedCount(): void
    {
        // Arrange
        $olderThan = new DateTimeImmutable('-7 days');

        $this->connectionMock
            ->shouldReceive('executeStatement')
            ->once()
            ->with(
                Mockery::on(fn ($sql) => str_contains($sql, 'DELETE FROM outbox') && str_contains($sql, 'published_at IS NOT NULL')),
                Mockery::type('array')
            )
            ->andReturn(42);

        // Act
        $result = $this->repository->deletePublishedOlderThan($olderThan);

        // Assert
        $this->assertSame(42, $result);
    }

    // ========================================================================
    // countPublishedBefore() Tests
    // ========================================================================

    #[Test]
    public function countPublishedBefore_ReturnsCount(): void
    {
        // Arrange
        $before = new DateTimeImmutable('-7 days');

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('fetchOne')
            ->once()
            ->andReturn('123');

        $this->connectionMock
            ->shouldReceive('executeQuery')
            ->once()
            ->with(
                Mockery::on(fn ($sql) => str_contains($sql, 'SELECT COUNT(*)') && str_contains($sql, 'published_at IS NOT NULL')),
                Mockery::type('array')
            )
            ->andReturn($resultMock);

        // Act
        $result = $this->repository->countPublishedBefore($before);

        // Assert
        $this->assertSame(123, $result);
    }

    // ========================================================================
    // deletePublishedBefore() Tests
    // ========================================================================

    #[Test]
    public function deletePublishedBefore_WithLimit_ReturnsDeletedCount(): void
    {
        // Arrange
        $before = new DateTimeImmutable('-7 days');
        $limit = 1000;

        $this->connectionMock
            ->shouldReceive('executeStatement')
            ->once()
            ->with(
                Mockery::on(fn ($sql) => str_contains($sql, 'DELETE FROM outbox') && str_contains($sql, 'LIMIT')),
                Mockery::on(fn ($params) => $params['limit'] === $limit)
            )
            ->andReturn(500);

        // Act
        $result = $this->repository->deletePublishedBefore($before, $limit);

        // Assert
        $this->assertSame(500, $result);
    }

    // ========================================================================
    // countFailedExceedingRetries() Tests
    // ========================================================================

    #[Test]
    public function countFailedExceedingRetries_ReturnsCount(): void
    {
        // Arrange
        $maxRetries = 5;

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('fetchOne')
            ->once()
            ->andReturn('15');

        $this->connectionMock
            ->shouldReceive('executeQuery')
            ->once()
            ->with(
                Mockery::on(fn ($sql) => str_contains($sql, 'retry_count > :max_retries')),
                Mockery::on(fn ($params) => $params['max_retries'] === $maxRetries)
            )
            ->andReturn($resultMock);

        // Act
        $result = $this->repository->countFailedExceedingRetries($maxRetries);

        // Assert
        $this->assertSame(15, $result);
    }

    // ========================================================================
    // deleteFailedExceedingRetries() Tests
    // ========================================================================

    #[Test]
    public function deleteFailedExceedingRetries_ReturnsDeletedCount(): void
    {
        // Arrange
        $maxRetries = 5;
        $limit = 100;

        $this->connectionMock
            ->shouldReceive('executeStatement')
            ->once()
            ->with(
                Mockery::on(fn ($sql) => str_contains($sql, 'retry_count > :max_retries')),
                Mockery::on(fn ($params) => $params['max_retries'] === $maxRetries && $params['limit'] === $limit)
            )
            ->andReturn(10);

        // Act
        $result = $this->repository->deleteFailedExceedingRetries($maxRetries, $limit);

        // Assert
        $this->assertSame(10, $result);
    }

    // ========================================================================
    // findById() Tests
    // ========================================================================

    #[Test]
    public function findById_WithExistingId_ReturnsEntry(): void
    {
        // Arrange
        $id = 'entry-123';
        $row = [
            'id' => $id,
            'message_type' => 'EVENT',
            'aggregate_type' => 'Article',
            'aggregate_id' => 'article-456',
            'event_type' => 'ArticleCreatedEvent',
            'event_payload' => '{}',
            'topic' => 'events.article',
            'routing_key' => 'event.article.created',
            'created_at' => '2024-01-15 10:00:00.000000',
            'published_at' => null,
            'retry_count' => 0,
            'last_error' => null,
            'next_retry_at' => null,
            'sequence_number' => 1,
        ];

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('fetchAssociative')
            ->once()
            ->andReturn($row);

        $this->connectionMock
            ->shouldReceive('executeQuery')
            ->once()
            ->with(
                Mockery::on(fn ($sql) => str_contains($sql, 'WHERE id = :id')),
                Mockery::on(fn ($params) => $params['id'] === $id)
            )
            ->andReturn($resultMock);

        // Act
        $result = $this->repository->findById($id);

        // Assert
        $this->assertInstanceOf(OutboxEntryInterface::class, $result);
        $this->assertSame($id, $result->getId());
    }

    #[Test]
    public function findById_WithNonExistentId_ReturnsNull(): void
    {
        // Arrange
        $id = 'non-existent';

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('fetchAssociative')
            ->once()
            ->andReturn(false);

        $this->connectionMock
            ->shouldReceive('executeQuery')
            ->once()
            ->andReturn($resultMock);

        // Act
        $result = $this->repository->findById($id);

        // Assert
        $this->assertNull($result);
    }

    // ========================================================================
    // getMetrics() Tests
    // ========================================================================

    #[Test]
    public function getMetrics_ReturnsCorrectStructure(): void
    {
        // Arrange
        $metricsRow = [
            'total_pending' => '10',
            'total_events' => '7',
            'total_tasks' => '3',
            'failed_count' => '2',
            'oldest_pending_seconds' => '300',
        ];

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('fetchAssociative')
            ->once()
            ->andReturn($metricsRow);

        $this->connectionMock
            ->shouldReceive('executeQuery')
            ->once()
            ->with(Mockery::on(fn ($sql) => str_contains($sql, 'COUNT(*) FILTER')))
            ->andReturn($resultMock);

        // Act
        $result = $this->repository->getMetrics();

        // Assert
        $this->assertArrayHasKey('total_pending', $result);
        $this->assertArrayHasKey('total_events', $result);
        $this->assertArrayHasKey('total_tasks', $result);
        $this->assertArrayHasKey('failed_count', $result);
        $this->assertArrayHasKey('oldest_pending_seconds', $result);
        $this->assertSame(10, $result['total_pending']);
        $this->assertSame(7, $result['total_events']);
        $this->assertSame(3, $result['total_tasks']);
        $this->assertSame(2, $result['failed_count']);
        $this->assertSame(300, $result['oldest_pending_seconds']);
    }

    #[Test]
    public function getMetrics_WithNullOldest_ReturnsNullForOldestPending(): void
    {
        // Arrange
        $metricsRow = [
            'total_pending' => '0',
            'total_events' => '0',
            'total_tasks' => '0',
            'failed_count' => '0',
            'oldest_pending_seconds' => null,
        ];

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('fetchAssociative')
            ->once()
            ->andReturn($metricsRow);

        $this->connectionMock
            ->shouldReceive('executeQuery')
            ->once()
            ->andReturn($resultMock);

        // Act
        $result = $this->repository->getMetrics();

        // Assert
        $this->assertNull($result['oldest_pending_seconds']);
    }

    // ========================================================================
    // countByStatus() Tests
    // ========================================================================

    #[Test]
    public function countByStatus_ReturnsCorrectStructure(): void
    {
        // Arrange
        $statusRow = [
            'pending' => '10',
            'published' => '50',
            'failed' => '5',
        ];

        $resultMock = Mockery::mock(Result::class);
        $resultMock->shouldReceive('fetchAssociative')
            ->once()
            ->andReturn($statusRow);

        $this->connectionMock
            ->shouldReceive('executeQuery')
            ->once()
            ->with(Mockery::on(fn ($sql) => str_contains($sql, 'COUNT(*) FILTER')))
            ->andReturn($resultMock);

        // Act
        $result = $this->repository->countByStatus();

        // Assert
        $this->assertArrayHasKey('pending', $result);
        $this->assertArrayHasKey('published', $result);
        $this->assertArrayHasKey('failed', $result);
        $this->assertSame(10, $result['pending']);
        $this->assertSame(50, $result['published']);
        $this->assertSame(5, $result['failed']);
    }

    // ========================================================================
    // Transaction Management Tests
    // ========================================================================

    #[Test]
    public function beginTransaction_CallsConnectionBeginTransaction(): void
    {
        // Arrange
        $this->connectionMock
            ->shouldReceive('beginTransaction')
            ->once();

        // Act
        $this->repository->beginTransaction();

        // Assert - no exception means success
        $this->assertTrue(true);
    }

    #[Test]
    public function commit_CallsConnectionCommit(): void
    {
        // Arrange
        $this->connectionMock
            ->shouldReceive('commit')
            ->once();

        // Act
        $this->repository->commit();

        // Assert - no exception means success
        $this->assertTrue(true);
    }

    #[Test]
    public function rollback_CallsConnectionRollback(): void
    {
        // Arrange
        $this->connectionMock
            ->shouldReceive('rollBack')
            ->once();

        // Act
        $this->repository->rollback();

        // Assert - no exception means success
        $this->assertTrue(true);
    }

    #[Test]
    public function isTransactionActive_ReturnsConnectionState(): void
    {
        // Arrange
        $this->connectionMock
            ->shouldReceive('isTransactionActive')
            ->once()
            ->andReturn(true);

        // Act
        $result = $this->repository->isTransactionActive();

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function getConnection_ReturnsTheConnection(): void
    {
        // Act
        $result = $this->repository->getConnection();

        // Assert
        $this->assertSame($this->connectionMock, $result);
    }
}
