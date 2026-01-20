<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Infrastructure\Subscriber;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Micro\Article\Domain\Event\ArticleArchivedEvent;
use Micro\Article\Domain\Event\ArticleCreatedEvent;
use Micro\Article\Domain\Event\ArticleDeletedEvent;
use Micro\Article\Domain\Event\ArticlePublishedEvent;
use Micro\Article\Domain\Event\ArticleUnpublishedEvent;
use Micro\Article\Domain\Event\ArticleUpdatedEvent;
use Micro\Article\Domain\ValueObject\ArchivedAt;
use Micro\Article\Domain\ValueObject\Article;
use Micro\Article\Domain\ValueObject\PublishedAt;
use Micro\Article\Domain\ValueObject\Status;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\UpdatedAt;
use MicroModule\Base\Domain\ValueObject\Uuid;

/**
 * DataProvider for ArticleCacheInvalidationSubscriber tests.
 */
final class ArticleCacheInvalidationSubscriberDataProvider
{
    private const string UUID_1 = '550e8400-e29b-41d4-a716-446655440000';
    private const string UUID_2 = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
    private const string PROCESS_UUID = 'a1234567-e89b-12d3-a456-426614174000';

    /**
     * Data for ArticleCreatedEvent handling scenarios.
     */
    public static function articleCreatedEventScenarios(): \Generator
    {
        yield 'article created event - standard' => [
            'event' => self::createArticleCreatedEvent(self::UUID_1),
            'expectedTags' => ['article.list'],
            'uuid' => self::UUID_1,
        ];

        yield 'article created event - different uuid' => [
            'event' => self::createArticleCreatedEvent(self::UUID_2),
            'expectedTags' => ['article.list'],
            'uuid' => self::UUID_2,
        ];
    }

    /**
     * Data for ArticleUpdatedEvent handling scenarios.
     */
    public static function articleUpdatedEventScenarios(): \Generator
    {
        yield 'article updated event - standard' => [
            'event' => self::createArticleUpdatedEvent(self::UUID_1),
            'expectedItemTags' => ['article.' . self::UUID_1],
            'expectedListTags' => ['article.list'],
            'uuid' => self::UUID_1,
        ];

        yield 'article updated event - different uuid' => [
            'event' => self::createArticleUpdatedEvent(self::UUID_2),
            'expectedItemTags' => ['article.' . self::UUID_2],
            'expectedListTags' => ['article.list'],
            'uuid' => self::UUID_2,
        ];
    }

    /**
     * Data for ArticlePublishedEvent handling scenarios.
     */
    public static function articlePublishedEventScenarios(): \Generator
    {
        yield 'article published event - standard' => [
            'event' => self::createArticlePublishedEvent(self::UUID_1),
            'expectedItemTags' => ['article.' . self::UUID_1],
            'expectedStatusTags' => [['article.status.published'], ['article.status.draft']],
            'expectedListTags' => ['article.list'],
            'uuid' => self::UUID_1,
        ];
    }

    /**
     * Data for ArticleUnpublishedEvent handling scenarios.
     *
     * Note: The subscriber invalidates status.published and status.unpublished caches,
     * but the event uses 'draft' as the valid Status enum value (since 'unpublished' is not a valid enum).
     */
    public static function articleUnpublishedEventScenarios(): \Generator
    {
        yield 'article unpublished event - standard' => [
            'event' => self::createArticleUnpublishedEvent(self::UUID_1),
            'expectedItemTags' => ['article.' . self::UUID_1],
            'expectedStatusTags' => [['article.status.published'], ['article.status.unpublished']],
            'expectedListTags' => ['article.list'],
            'uuid' => self::UUID_1,
        ];
    }

    /**
     * Data for ArticleArchivedEvent handling scenarios.
     */
    public static function articleArchivedEventScenarios(): \Generator
    {
        yield 'article archived event - standard' => [
            'event' => self::createArticleArchivedEvent(self::UUID_1),
            'expectedItemTags' => ['article.' . self::UUID_1],
            'expectedStatusTags' => [['article.status.archived']],
            'expectedListTags' => ['article.list'],
            'uuid' => self::UUID_1,
        ];
    }

    /**
     * Data for ArticleDeletedEvent handling scenarios.
     */
    public static function articleDeletedEventScenarios(): \Generator
    {
        yield 'article deleted event - standard' => [
            'event' => self::createArticleDeletedEvent(self::UUID_1),
            'expectedItemTags' => ['article.' . self::UUID_1],
            'expectedListTags' => ['article.list'],
            'uuid' => self::UUID_1,
        ];
    }

    /**
     * Data for unhandled event scenarios.
     */
    public static function unhandledEventScenarios(): \Generator
    {
        yield 'unhandled event - stdClass' => [
            'event' => new \stdClass(),
        ];
    }

    /**
     * Create a DomainMessage wrapper for an event.
     *
     * @param object $event       The payload event
     * @param string $aggregateId The aggregate ID
     */
    public static function createDomainMessage(object $event, string $aggregateId = self::UUID_1): DomainMessage
    {
        return DomainMessage::recordNow($aggregateId, 0, new Metadata([]), $event);
    }

    /**
     * Create a Article value object for events.
     */
    private static function createArticleValueObject(): Article
    {
        return Article::fromNative([
            'title' => 'Test Article Title',
            'slug' => 'test-article-title',
            'short_description' => 'Short description for test article.',
            'description' => 'Full description with at least fifty characters for validation testing purposes.',
            'status' => 'draft',
            'created_at' => new \DateTimeImmutable()
                ->format('Y-m-d H:i:s'),
            'updated_at' => new \DateTimeImmutable()
                ->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Create ArticleCreatedEvent with proper Article value object.
     */
    private static function createArticleCreatedEvent(string $uuid): ArticleCreatedEvent
    {
        return new ArticleCreatedEvent(
            ProcessUuid::fromNative(self::PROCESS_UUID),
            Uuid::fromNative($uuid),
            self::createArticleValueObject()
        );
    }

    /**
     * Create ArticleUpdatedEvent with proper Article value object.
     */
    private static function createArticleUpdatedEvent(string $uuid): ArticleUpdatedEvent
    {
        return new ArticleUpdatedEvent(
            ProcessUuid::fromNative(self::PROCESS_UUID),
            Uuid::fromNative($uuid),
            self::createArticleValueObject()
        );
    }

    /**
     * Create ArticlePublishedEvent with proper value objects.
     */
    private static function createArticlePublishedEvent(string $uuid): ArticlePublishedEvent
    {
        return new ArticlePublishedEvent(
            ProcessUuid::fromNative(self::PROCESS_UUID),
            Uuid::fromNative($uuid),
            Status::fromNative('published'),
            PublishedAt::fromNative(new \DateTimeImmutable()),
            UpdatedAt::fromNative(new \DateTimeImmutable())
        );
    }

    /**
     * Create ArticleUnpublishedEvent with proper value objects.
     *
     * Uses 'draft' as the Status value since 'unpublished' is not a valid enum value.
     * The unpublished event represents transitioning from published back to draft.
     */
    private static function createArticleUnpublishedEvent(string $uuid): ArticleUnpublishedEvent
    {
        return new ArticleUnpublishedEvent(
            ProcessUuid::fromNative(self::PROCESS_UUID),
            Uuid::fromNative($uuid),
            Status::fromNative('draft'),
            UpdatedAt::fromNative(new \DateTimeImmutable())
        );
    }

    /**
     * Create ArticleArchivedEvent with proper value objects.
     */
    private static function createArticleArchivedEvent(string $uuid): ArticleArchivedEvent
    {
        return new ArticleArchivedEvent(
            ProcessUuid::fromNative(self::PROCESS_UUID),
            Uuid::fromNative($uuid),
            Status::fromNative('archived'),
            ArchivedAt::fromNative(new \DateTimeImmutable()),
            UpdatedAt::fromNative(new \DateTimeImmutable())
        );
    }

    /**
     * Create ArticleDeletedEvent with just UUIDs.
     */
    private static function createArticleDeletedEvent(string $uuid): ArticleDeletedEvent
    {
        return new ArticleDeletedEvent(ProcessUuid::fromNative(self::PROCESS_UUID), Uuid::fromNative($uuid));
    }
}
