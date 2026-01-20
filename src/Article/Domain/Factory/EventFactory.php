<?php

declare(strict_types=1);

namespace Micro\Article\Domain\Factory;

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
use MicroModule\Base\Domain\ValueObject\Payload;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\UpdatedAt;
use MicroModule\Base\Domain\ValueObject\Uuid;

/**
 * @class EventFactory
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EventFactory implements EventFactoryInterface
{
    /**
     * Create ArticleCreatedEvent Event.
     */
    public function makeArticleCreatedEvent(
        ProcessUuid $processUuid,
        Uuid $uuid,
        Article $article,
        ?Payload $payload = null,
    ): ArticleCreatedEvent {
        return new ArticleCreatedEvent($processUuid, $uuid, $article, $payload);
    }

    /**
     * Create ArticleUpdatedEvent Event.
     */
    public function makeArticleUpdatedEvent(
        ProcessUuid $processUuid,
        Uuid $uuid,
        Article $article,
        ?Payload $payload = null,
    ): ArticleUpdatedEvent {
        return new ArticleUpdatedEvent($processUuid, $uuid, $article, $payload);
    }

    /**
     * Create ArticlePublishedEvent Event.
     */
    public function makeArticlePublishedEvent(
        ProcessUuid $processUuid,
        Uuid $uuid,
        Status $status,
        PublishedAt $publishedAt,
        UpdatedAt $updatedAt,
        ?Payload $payload = null,
    ): ArticlePublishedEvent {
        return new ArticlePublishedEvent($processUuid, $uuid, $status, $publishedAt, $updatedAt, $payload);
    }

    /**
     * Create ArticleUnpublishedEvent Event.
     */
    public function makeArticleUnpublishedEvent(
        ProcessUuid $processUuid,
        Uuid $uuid,
        Status $status,
        UpdatedAt $updatedAt,
        ?Payload $payload = null,
    ): ArticleUnpublishedEvent {
        return new ArticleUnpublishedEvent($processUuid, $uuid, $status, $updatedAt, $payload);
    }

    /**
     * Create ArticleArchivedEvent Event.
     */
    public function makeArticleArchivedEvent(
        ProcessUuid $processUuid,
        Uuid $uuid,
        Status $status,
        ArchivedAt $archivedAt,
        UpdatedAt $updatedAt,
        ?Payload $payload = null,
    ): ArticleArchivedEvent {
        return new ArticleArchivedEvent($processUuid, $uuid, $status, $archivedAt, $updatedAt, $payload);
    }

    /**
     * Create ArticleDeletedEvent Event.
     */
    public function makeArticleDeletedEvent(
        ProcessUuid $processUuid,
        Uuid $uuid,
        ?Payload $payload = null,
    ): ArticleDeletedEvent {
        return new ArticleDeletedEvent($processUuid, $uuid, $payload);
    }
}
