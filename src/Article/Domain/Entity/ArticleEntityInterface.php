<?php

declare(strict_types=1);

namespace Micro\Article\Domain\Entity;

use Micro\Article\Domain\Service\ArticleSlugGeneratorServiceInterface;
use Micro\Article\Domain\ValueObject\ArchivedAt;
use Micro\Article\Domain\ValueObject\Description;
use Micro\Article\Domain\ValueObject\EventId;
use Micro\Article\Domain\ValueObject\Article;
use Micro\Article\Domain\ValueObject\PublishedAt;
use Micro\Article\Domain\ValueObject\ShortDescription;
use Micro\Article\Domain\ValueObject\Slug;
use Micro\Article\Domain\ValueObject\Status;
use Micro\Article\Domain\ValueObject\Title;
use MicroModule\Base\Domain\Entity\EntityInterface;
use MicroModule\Base\Domain\ValueObject\CreatedAt;
use MicroModule\Base\Domain\ValueObject\Payload;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\UpdatedAt;

/**
 * @interface ArticleEntityInterface
 */
interface ArticleEntityInterface extends EntityInterface
{
    /**
     * Execute article-create command.
     */
    public function articleCreate(ProcessUuid $processUuid, Article $article, ?Payload $payload = null): void;

    /**
     * Execute article-update command.
     */
    public function articleUpdate(ProcessUuid $processUuid, Article $article, ?Payload $payload = null): void;

    /**
     * Execute article-publish command.
     */
    public function articlePublish(ProcessUuid $processUuid, ?Payload $payload = null): void;

    /**
     * Execute article-unpublish command.
     */
    public function articleUnpublish(ProcessUuid $processUuid, ?Payload $payload = null): void;

    /**
     * Execute article-archive command.
     */
    public function articleArchive(ProcessUuid $processUuid, ?Payload $payload = null): void;

    /**
     * Execute article-delete command.
     */
    public function articleDelete(ProcessUuid $processUuid, ?Payload $payload = null): void;

    /**
     * Return title value object.
     */
    public function getTitle(): ?Title;

    /**
     * Return short_description value object.
     */
    public function getShortDescription(): ?ShortDescription;

    /**
     * Return description value object.
     */
    public function getDescription(): ?Description;

    /**
     * Return slug value object.
     */
    public function getSlug(): ?Slug;

    /**
     * Return event_id value object.
     */
    public function getEventId(): ?EventId;

    /**
     * Return status value object.
     */
    public function getStatus(): ?Status;

    /**
     * Return published_at value object.
     */
    public function getPublishedAt(): ?PublishedAt;

    /**
     * Return archived_at value object.
     */
    public function getArchivedAt(): ?ArchivedAt;

    /**
     * Return created_at value object.
     */
    public function getCreatedAt(): ?CreatedAt;

    /**
     * Return updated_at value object.
     */
    public function getUpdatedAt(): ?UpdatedAt;

    /**
     * Set the ArticleSlugGeneratorService for entities loaded from event store.
     */
    public function setArticleSlugGeneratorService(ArticleSlugGeneratorServiceInterface $articleSlugGeneratorService): void;
}
