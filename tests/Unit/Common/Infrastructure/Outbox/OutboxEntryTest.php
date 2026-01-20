<?php

declare(strict_types=1);

namespace Tests\Unit\Common\Infrastructure\Outbox;

use DateTimeImmutable;
use Micro\Component\Common\Domain\Outbox\OutboxEntryInterface;
use Micro\Component\Common\Domain\Outbox\OutboxMessageType;
use Micro\Component\Common\Infrastructure\Outbox\OutboxEntry;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\UnitTestCase;

/**
 * Unit tests for OutboxEntry value object.
 *
 * Tests factory methods, state transitions, retry logic,
 * and serialization for the transactional outbox pattern.
 *
 * @see docs/tasks/phase-14-transactional-outbox/TASK-14.1-database-core-components.md
 */
#[CoversClass(OutboxEntry::class)]
final class OutboxEntryTest extends UnitTestCase
{
    // ========================================================================
    // Factory Method Tests: createForEvent()
    // ========================================================================

    #[Test]
    public function createForEvent_WithValidParameters_ReturnsOutboxEntry(): void
    {
        // Arrange
        $aggregateType = 'Article';
        $aggregateId = 'article-123';
        $eventType = 'ArticleCreatedEvent';
        $eventPayload = '{"title":"Test Article"}';
        $topic = 'events.article';
        $routingKey = 'event.article.created';

        // Act
        $entry = OutboxEntry::createForEvent(
            $aggregateType,
            $aggregateId,
            $eventType,
            $eventPayload,
            $topic,
            $routingKey,
        );

        // Assert
        $this->assertInstanceOf(OutboxEntryInterface::class, $entry);
        $this->assertValidUuid($entry->getId());
        $this->assertSame(OutboxMessageType::EVENT, $entry->getMessageType());
        $this->assertSame($aggregateType, $entry->getAggregateType());
        $this->assertSame($aggregateId, $entry->getAggregateId());
        $this->assertSame($eventType, $entry->getEventType());
        $this->assertSame($eventPayload, $entry->getEventPayload());
        $this->assertSame($topic, $entry->getTopic());
        $this->assertSame($routingKey, $entry->getRoutingKey());
        $this->assertNull($entry->getPublishedAt());
        $this->assertSame(0, $entry->getRetryCount());
        $this->assertNull($entry->getLastError());
        $this->assertNull($entry->getNextRetryAt());
        $this->assertSame(0, $entry->getSequenceNumber());
    }

    #[Test]
    public function createForEvent_WithCustomSequenceNumber_UsesProvidedValue(): void
    {
        // Arrange
        $sequenceNumber = 42;

        // Act
        $entry = OutboxEntry::createForEvent(
            'Article',
            'article-123',
            'ArticleCreatedEvent',
            '{}',
            'events.article',
            'event.article.created',
            $sequenceNumber,
        );

        // Assert
        $this->assertSame($sequenceNumber, $entry->getSequenceNumber());
    }

    #[Test]
    public function createForEvent_GeneratesUniqueIds(): void
    {
        // Act
        $entry1 = OutboxEntry::createForEvent('Article', 'id-1', 'Event', '{}', 'topic', 'key');
        $entry2 = OutboxEntry::createForEvent('Article', 'id-2', 'Event', '{}', 'topic', 'key');

        // Assert
        $this->assertNotSame($entry1->getId(), $entry2->getId());
    }

    // ========================================================================
    // Factory Method Tests: createForTask()
    // ========================================================================

    #[Test]
    public function createForTask_WithValidParameters_ReturnsOutboxEntry(): void
    {
        // Arrange
        $aggregateType = 'Article';
        $aggregateId = 'article-123';
        $commandType = 'PublishArticleCommand';
        $commandPayload = '{"id":"article-123"}';
        $topic = 'tasks.article';
        $routingKey = 'task.article.publish';

        // Act
        $entry = OutboxEntry::createForTask(
            $aggregateType,
            $aggregateId,
            $commandType,
            $commandPayload,
            $topic,
            $routingKey,
        );

        // Assert
        $this->assertInstanceOf(OutboxEntryInterface::class, $entry);
        $this->assertValidUuid($entry->getId());
        $this->assertSame(OutboxMessageType::TASK, $entry->getMessageType());
        $this->assertSame($aggregateType, $entry->getAggregateType());
        $this->assertSame($aggregateId, $entry->getAggregateId());
        $this->assertSame($commandType, $entry->getEventType());
        $this->assertSame($commandPayload, $entry->getEventPayload());
        $this->assertSame($topic, $entry->getTopic());
        $this->assertSame($routingKey, $entry->getRoutingKey());
        $this->assertNull($entry->getPublishedAt());
        $this->assertSame(0, $entry->getRetryCount());
    }

    #[Test]
    public function createForTask_WithCustomSequenceNumber_UsesProvidedValue(): void
    {
        // Arrange
        $sequenceNumber = 99;

        // Act
        $entry = OutboxEntry::createForTask(
            'Article',
            'article-123',
            'PublishArticleCommand',
            '{}',
            'tasks.article',
            'task.article.publish',
            $sequenceNumber,
        );

        // Assert
        $this->assertSame($sequenceNumber, $entry->getSequenceNumber());
    }

    // ========================================================================
    // Factory Method Tests: fromArray()
    // ========================================================================

    #[Test]
    public function fromArray_WithEventData_ReconstructsEntry(): void
    {
        // Arrange
        $data = [
            'id' => 'entry-uuid-123',
            'message_type' => 'EVENT',
            'aggregate_type' => 'Article',
            'aggregate_id' => 'article-456',
            'event_type' => 'ArticlePublishedEvent',
            'event_payload' => '{"status":"published"}',
            'topic' => 'events.article',
            'routing_key' => 'event.article.published',
            'created_at' => '2024-01-15 10:30:00.000000',
            'published_at' => null,
            'retry_count' => 0,
            'last_error' => null,
            'next_retry_at' => null,
            'sequence_number' => 10,
        ];

        // Act
        $entry = OutboxEntry::fromArray($data);

        // Assert
        $this->assertSame('entry-uuid-123', $entry->getId());
        $this->assertSame(OutboxMessageType::EVENT, $entry->getMessageType());
        $this->assertSame('Article', $entry->getAggregateType());
        $this->assertSame('article-456', $entry->getAggregateId());
        $this->assertSame('ArticlePublishedEvent', $entry->getEventType());
        $this->assertSame('{"status":"published"}', $entry->getEventPayload());
        $this->assertSame('events.article', $entry->getTopic());
        $this->assertSame('event.article.published', $entry->getRoutingKey());
        $this->assertSame('2024-01-15 10:30:00.000000', $entry->getCreatedAt()->format('Y-m-d H:i:s.u'));
        $this->assertNull($entry->getPublishedAt());
        $this->assertSame(0, $entry->getRetryCount());
        $this->assertNull($entry->getLastError());
        $this->assertNull($entry->getNextRetryAt());
        $this->assertSame(10, $entry->getSequenceNumber());
    }

    #[Test]
    public function fromArray_WithPublishedEntry_ReconstructsWithPublishedAt(): void
    {
        // Arrange
        $data = [
            'id' => 'entry-uuid-456',
            'message_type' => 'EVENT',
            'aggregate_type' => 'Article',
            'aggregate_id' => 'article-789',
            'event_type' => 'ArticleCreatedEvent',
            'event_payload' => '{}',
            'topic' => 'events.article',
            'routing_key' => 'event.article.created',
            'created_at' => '2024-01-15 10:00:00.000000',
            'published_at' => '2024-01-15 10:00:05.123456',
            'retry_count' => 0,
            'last_error' => null,
            'next_retry_at' => null,
            'sequence_number' => 5,
        ];

        // Act
        $entry = OutboxEntry::fromArray($data);

        // Assert
        $this->assertNotNull($entry->getPublishedAt());
        $this->assertSame('2024-01-15 10:00:05.123456', $entry->getPublishedAt()->format('Y-m-d H:i:s.u'));
        $this->assertTrue($entry->isPublished());
    }

    #[Test]
    public function fromArray_WithFailedEntry_ReconstructsWithRetryInfo(): void
    {
        // Arrange
        $data = [
            'id' => 'entry-uuid-789',
            'message_type' => 'TASK',
            'aggregate_type' => 'Article',
            'aggregate_id' => 'article-111',
            'event_type' => 'PublishArticleCommand',
            'event_payload' => '{}',
            'topic' => 'tasks.article',
            'routing_key' => 'task.article.publish',
            'created_at' => '2024-01-15 09:00:00.000000',
            'published_at' => null,
            'retry_count' => 3,
            'last_error' => 'Connection timeout',
            'next_retry_at' => '2024-01-15 09:05:00.000000',
            'sequence_number' => 15,
        ];

        // Act
        $entry = OutboxEntry::fromArray($data);

        // Assert
        $this->assertSame(OutboxMessageType::TASK, $entry->getMessageType());
        $this->assertSame(3, $entry->getRetryCount());
        $this->assertSame('Connection timeout', $entry->getLastError());
        $this->assertNotNull($entry->getNextRetryAt());
        $this->assertSame('2024-01-15 09:05:00.000000', $entry->getNextRetryAt()->format('Y-m-d H:i:s.u'));
    }

    // ========================================================================
    // State Transition Tests: markAsPublished()
    // ========================================================================

    #[Test]
    public function markAsPublished_ReturnsNewInstanceWithPublishedAt(): void
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
        $publishedAt = new DateTimeImmutable('2024-01-15 12:00:00');

        // Act
        $publishedEntry = $entry->markAsPublished($publishedAt);

        // Assert
        $this->assertNotSame($entry, $publishedEntry);
        $this->assertSame($entry->getId(), $publishedEntry->getId());
        $this->assertNull($entry->getPublishedAt());
        $this->assertSame($publishedAt, $publishedEntry->getPublishedAt());
        $this->assertTrue($publishedEntry->isPublished());
    }

    #[Test]
    public function markAsPublished_ClearsErrorAndNextRetry(): void
    {
        // Arrange - Create a failed entry first
        $data = [
            'id' => 'entry-123',
            'message_type' => 'EVENT',
            'aggregate_type' => 'Article',
            'aggregate_id' => 'article-456',
            'event_type' => 'ArticleCreatedEvent',
            'event_payload' => '{}',
            'topic' => 'events.article',
            'routing_key' => 'event.article.created',
            'created_at' => '2024-01-15 10:00:00.000000',
            'published_at' => null,
            'retry_count' => 2,
            'last_error' => 'Previous error',
            'next_retry_at' => '2024-01-15 10:05:00.000000',
            'sequence_number' => 1,
        ];
        $failedEntry = OutboxEntry::fromArray($data);
        $publishedAt = new DateTimeImmutable();

        // Act
        $publishedEntry = $failedEntry->markAsPublished($publishedAt);

        // Assert
        $this->assertNull($publishedEntry->getLastError());
        $this->assertNull($publishedEntry->getNextRetryAt());
        $this->assertSame(2, $publishedEntry->getRetryCount()); // Retry count preserved
    }

    #[Test]
    public function markAsPublished_PreservesAllOtherFields(): void
    {
        // Arrange
        $entry = OutboxEntry::createForEvent(
            'Article',
            'article-123',
            'ArticleCreatedEvent',
            '{"title":"Test"}',
            'events.article',
            'event.article.created',
            42,
        );
        $publishedAt = new DateTimeImmutable();

        // Act
        $publishedEntry = $entry->markAsPublished($publishedAt);

        // Assert
        $this->assertSame($entry->getId(), $publishedEntry->getId());
        $this->assertSame($entry->getMessageType(), $publishedEntry->getMessageType());
        $this->assertSame($entry->getAggregateType(), $publishedEntry->getAggregateType());
        $this->assertSame($entry->getAggregateId(), $publishedEntry->getAggregateId());
        $this->assertSame($entry->getEventType(), $publishedEntry->getEventType());
        $this->assertSame($entry->getEventPayload(), $publishedEntry->getEventPayload());
        $this->assertSame($entry->getTopic(), $publishedEntry->getTopic());
        $this->assertSame($entry->getRoutingKey(), $publishedEntry->getRoutingKey());
        $this->assertSame($entry->getSequenceNumber(), $publishedEntry->getSequenceNumber());
    }

    // ========================================================================
    // State Transition Tests: markAsFailed()
    // ========================================================================

    #[Test]
    public function markAsFailed_IncrementsRetryCount(): void
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
        $nextRetryAt = new DateTimeImmutable('+5 minutes');

        // Act
        $failedEntry = $entry->markAsFailed('Connection failed', $nextRetryAt);

        // Assert
        $this->assertSame(0, $entry->getRetryCount());
        $this->assertSame(1, $failedEntry->getRetryCount());
    }

    #[Test]
    public function markAsFailed_SetsErrorAndNextRetryAt(): void
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
        $error = 'RabbitMQ connection timeout';
        $nextRetryAt = new DateTimeImmutable('2024-01-15 12:05:00');

        // Act
        $failedEntry = $entry->markAsFailed($error, $nextRetryAt);

        // Assert
        $this->assertSame($error, $failedEntry->getLastError());
        $this->assertSame($nextRetryAt, $failedEntry->getNextRetryAt());
    }

    #[Test]
    public function markAsFailed_ReturnsNewImmutableInstance(): void
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
        $nextRetryAt = new DateTimeImmutable();

        // Act
        $failedEntry = $entry->markAsFailed('Error', $nextRetryAt);

        // Assert
        $this->assertNotSame($entry, $failedEntry);
        $this->assertNull($entry->getLastError());
        $this->assertSame('Error', $failedEntry->getLastError());
    }

    #[Test]
    public function markAsFailed_KeepsPublishedAtNull(): void
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
        $nextRetryAt = new DateTimeImmutable();

        // Act
        $failedEntry = $entry->markAsFailed('Error', $nextRetryAt);

        // Assert
        $this->assertNull($failedEntry->getPublishedAt());
        $this->assertFalse($failedEntry->isPublished());
    }

    // ========================================================================
    // Utility Method Tests: isPublished()
    // ========================================================================

    #[Test]
    public function isPublished_WithNullPublishedAt_ReturnsFalse(): void
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

        // Act & Assert
        $this->assertFalse($entry->isPublished());
    }

    #[Test]
    public function isPublished_WithPublishedAt_ReturnsTrue(): void
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
        $publishedEntry = $entry->markAsPublished(new DateTimeImmutable());

        // Act & Assert
        $this->assertTrue($publishedEntry->isPublished());
    }

    // ========================================================================
    // Utility Method Tests: isEligibleForRetry()
    // ========================================================================

    #[Test]
    public function isEligibleForRetry_NewEntry_ReturnsTrue(): void
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

        // Act & Assert
        $this->assertTrue($entry->isEligibleForRetry());
    }

    #[Test]
    public function isEligibleForRetry_PublishedEntry_ReturnsFalse(): void
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
        $publishedEntry = $entry->markAsPublished(new DateTimeImmutable());

        // Act & Assert
        $this->assertFalse($publishedEntry->isEligibleForRetry());
    }

    #[Test]
    public function isEligibleForRetry_ExceededMaxRetries_ReturnsFalse(): void
    {
        // Arrange - Entry with 10 retries (MAX_RETRY_COUNT)
        $data = [
            'id' => 'entry-123',
            'message_type' => 'EVENT',
            'aggregate_type' => 'Article',
            'aggregate_id' => 'article-456',
            'event_type' => 'ArticleCreatedEvent',
            'event_payload' => '{}',
            'topic' => 'events.article',
            'routing_key' => 'event.article.created',
            'created_at' => '2024-01-15 10:00:00.000000',
            'published_at' => null,
            'retry_count' => 10,
            'last_error' => 'Persistent failure',
            'next_retry_at' => null,
            'sequence_number' => 1,
        ];
        $entry = OutboxEntry::fromArray($data);

        // Act & Assert
        $this->assertFalse($entry->isEligibleForRetry());
    }

    #[Test]
    public function isEligibleForRetry_FutureNextRetryAt_ReturnsFalse(): void
    {
        // Arrange
        $data = [
            'id' => 'entry-123',
            'message_type' => 'EVENT',
            'aggregate_type' => 'Article',
            'aggregate_id' => 'article-456',
            'event_type' => 'ArticleCreatedEvent',
            'event_payload' => '{}',
            'topic' => 'events.article',
            'routing_key' => 'event.article.created',
            'created_at' => '2024-01-15 10:00:00.000000',
            'published_at' => null,
            'retry_count' => 2,
            'last_error' => 'Temporary failure',
            'next_retry_at' => (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s.u'),
            'sequence_number' => 1,
        ];
        $entry = OutboxEntry::fromArray($data);

        // Act & Assert
        $this->assertFalse($entry->isEligibleForRetry());
    }

    #[Test]
    public function isEligibleForRetry_PastNextRetryAt_ReturnsTrue(): void
    {
        // Arrange
        $data = [
            'id' => 'entry-123',
            'message_type' => 'EVENT',
            'aggregate_type' => 'Article',
            'aggregate_id' => 'article-456',
            'event_type' => 'ArticleCreatedEvent',
            'event_payload' => '{}',
            'topic' => 'events.article',
            'routing_key' => 'event.article.created',
            'created_at' => '2024-01-15 10:00:00.000000',
            'published_at' => null,
            'retry_count' => 2,
            'last_error' => 'Temporary failure',
            'next_retry_at' => (new DateTimeImmutable('-1 minute'))->format('Y-m-d H:i:s.u'),
            'sequence_number' => 1,
        ];
        $entry = OutboxEntry::fromArray($data);

        // Act & Assert
        $this->assertTrue($entry->isEligibleForRetry());
    }

    // ========================================================================
    // Utility Method Tests: calculateNextRetryDelay()
    // ========================================================================

    #[Test]
    #[DataProvider('retryDelayDataProvider')]
    public function calculateNextRetryDelay_ReturnsExponentialBackoff(int $retryCount, int $expectedDelay): void
    {
        // Act
        $delay = OutboxEntry::calculateNextRetryDelay($retryCount);

        // Assert
        $this->assertSame($expectedDelay, $delay);
    }

    /**
     * @return array<string, array{int, int}>
     */
    public static function retryDelayDataProvider(): array
    {
        return [
            'retry 0 = 1 second' => [0, 1],
            'retry 1 = 2 seconds' => [1, 2],
            'retry 2 = 4 seconds' => [2, 4],
            'retry 3 = 8 seconds' => [3, 8],
            'retry 4 = 16 seconds' => [4, 16],
            'retry 5 = 32 seconds' => [5, 32],
            'retry 6 = 64 seconds' => [6, 64],
            'retry 7 = 128 seconds' => [7, 128],
            'retry 8 = 256 seconds' => [8, 256],
            'retry 9 = 300 seconds (capped)' => [9, 300],
            'retry 10 = 300 seconds (capped)' => [10, 300],
            'retry 15 = 300 seconds (capped)' => [15, 300],
        ];
    }

    // ========================================================================
    // Serialization Tests: toArray()
    // ========================================================================

    #[Test]
    public function toArray_ReturnsCorrectStructure(): void
    {
        // Arrange
        $entry = OutboxEntry::createForEvent(
            'Article',
            'article-123',
            'ArticleCreatedEvent',
            '{"title":"Test"}',
            'events.article',
            'event.article.created',
            42,
        );

        // Act
        $array = $entry->toArray();

        // Assert
        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('message_type', $array);
        $this->assertArrayHasKey('aggregate_type', $array);
        $this->assertArrayHasKey('aggregate_id', $array);
        $this->assertArrayHasKey('event_type', $array);
        $this->assertArrayHasKey('event_payload', $array);
        $this->assertArrayHasKey('topic', $array);
        $this->assertArrayHasKey('routing_key', $array);
        $this->assertArrayHasKey('created_at', $array);
        $this->assertArrayHasKey('published_at', $array);
        $this->assertArrayHasKey('retry_count', $array);
        $this->assertArrayHasKey('last_error', $array);
        $this->assertArrayHasKey('next_retry_at', $array);
        $this->assertArrayHasKey('sequence_number', $array);
    }

    #[Test]
    public function toArray_ContainsCorrectValues(): void
    {
        // Arrange
        $entry = OutboxEntry::createForTask(
            'Article',
            'article-123',
            'PublishArticleCommand',
            '{"id":"article-123"}',
            'tasks.article',
            'task.article.publish',
            99,
        );

        // Act
        $array = $entry->toArray();

        // Assert
        $this->assertSame('TASK', $array['message_type']);
        $this->assertSame('Article', $array['aggregate_type']);
        $this->assertSame('article-123', $array['aggregate_id']);
        $this->assertSame('PublishArticleCommand', $array['event_type']);
        $this->assertSame('{"id":"article-123"}', $array['event_payload']);
        $this->assertSame('tasks.article', $array['topic']);
        $this->assertSame('task.article.publish', $array['routing_key']);
        $this->assertSame(0, $array['retry_count']);
        $this->assertNull($array['published_at']);
        $this->assertNull($array['last_error']);
        $this->assertNull($array['next_retry_at']);
        $this->assertSame(99, $array['sequence_number']);
    }

    #[Test]
    public function toArray_FormatsDateTimesCorrectly(): void
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
        $publishedAt = new DateTimeImmutable('2024-01-15 12:30:45.123456');
        $publishedEntry = $entry->markAsPublished($publishedAt);

        // Act
        $array = $publishedEntry->toArray();

        // Assert
        $this->assertSame('2024-01-15 12:30:45.123456', $array['published_at']);
        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\.\d{6}$/',
            $array['created_at']
        );
    }

    #[Test]
    public function toArray_RoundTrips_WithFromArray(): void
    {
        // Arrange
        $originalEntry = OutboxEntry::createForEvent(
            'Article',
            'article-123',
            'ArticleCreatedEvent',
            '{"title":"Test"}',
            'events.article',
            'event.article.created',
            42,
        );

        // Act
        $array = $originalEntry->toArray();
        $reconstitutedEntry = OutboxEntry::fromArray($array);

        // Assert
        $this->assertSame($originalEntry->getId(), $reconstitutedEntry->getId());
        $this->assertSame($originalEntry->getMessageType(), $reconstitutedEntry->getMessageType());
        $this->assertSame($originalEntry->getAggregateType(), $reconstitutedEntry->getAggregateType());
        $this->assertSame($originalEntry->getAggregateId(), $reconstitutedEntry->getAggregateId());
        $this->assertSame($originalEntry->getEventType(), $reconstitutedEntry->getEventType());
        $this->assertSame($originalEntry->getEventPayload(), $reconstitutedEntry->getEventPayload());
        $this->assertSame($originalEntry->getTopic(), $reconstitutedEntry->getTopic());
        $this->assertSame($originalEntry->getRoutingKey(), $reconstitutedEntry->getRoutingKey());
        $this->assertSame($originalEntry->getRetryCount(), $reconstitutedEntry->getRetryCount());
        $this->assertSame($originalEntry->getSequenceNumber(), $reconstitutedEntry->getSequenceNumber());
    }

    // ========================================================================
    // Edge Case Tests
    // ========================================================================

    #[Test]
    public function multipleFailures_AccumulatesRetryCount(): void
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
        $nextRetry = new DateTimeImmutable('-1 minute');

        // Act - Simulate 3 consecutive failures
        $failed1 = $entry->markAsFailed('Error 1', $nextRetry);
        $failed2 = $failed1->markAsFailed('Error 2', $nextRetry);
        $failed3 = $failed2->markAsFailed('Error 3', $nextRetry);

        // Assert
        $this->assertSame(0, $entry->getRetryCount());
        $this->assertSame(1, $failed1->getRetryCount());
        $this->assertSame(2, $failed2->getRetryCount());
        $this->assertSame(3, $failed3->getRetryCount());
        $this->assertSame('Error 3', $failed3->getLastError());
    }

    #[Test]
    public function emptyPayload_IsAccepted(): void
    {
        // Act
        $entry = OutboxEntry::createForEvent(
            'Article',
            'article-123',
            'ArticleCreatedEvent',
            '',
            'events.article',
            'event.article.created',
        );

        // Assert
        $this->assertSame('', $entry->getEventPayload());
    }

    #[Test]
    public function largePayload_IsAccepted(): void
    {
        // Arrange
        $largePayload = str_repeat('{"data":"test"},', 10000);

        // Act
        $entry = OutboxEntry::createForEvent(
            'Article',
            'article-123',
            'ArticleCreatedEvent',
            $largePayload,
            'events.article',
            'event.article.created',
        );

        // Assert
        $this->assertSame($largePayload, $entry->getEventPayload());
    }
}
