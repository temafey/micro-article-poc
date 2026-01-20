<?php

declare(strict_types=1);

namespace Micro\Article\Domain\ReadModel;

use Micro\Article\Domain\Entity\ArticleEntityInterface;
use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Domain\ReadModel\ReadModelInterface;
use MicroModule\Base\Domain\ValueObject\Uuid;

/**
 * @interface ArticleReadModelInterface
 */
interface ArticleReadModelInterface extends ReadModelInterface
{
    /**
     * Return uuid value object.
     */
    public function getUuid(): ?Uuid;

    /**
     * Return title value.
     */
    public function getTitle(): ?string;

    /**
     * Return short_description value.
     */
    public function getShortDescription(): ?string;

    /**
     * Return description value.
     */
    public function getDescription(): ?string;

    /**
     * Return slug value.
     */
    public function getSlug(): ?string;

    /**
     * Return event_id value.
     */
    public function getEventId(): ?int;

    /**
     * Return status value.
     */
    public function getStatus(): ?string;

    /**
     * Return published_at value.
     */
    public function getPublishedAt(): ?\DateTimeInterface;

    /**
     * Return archived_at value.
     */
    public function getArchivedAt(): ?\DateTimeInterface;

    /**
     * Return created_at value.
     */
    public function getCreatedAt(): ?\DateTimeInterface;

    /**
     * Return updated_at value.
     */
    public function getUpdatedAt(): ?\DateTimeInterface;

    /**
     * Create ArticleEntity by ArticleEntity.
     */
    public static function createByEntity(ArticleEntityInterface $entity): self;

    /**
     * Create ArticleEntity by Article value object.
     */
    public static function createByValueObject(Article $entityValueObject, Uuid $uuid): self;

    /**
     * Update ArticleEntity by Article value object.
     */
    public function updateByValueObject(Article $entityValueObject, Uuid $uuid): void;
}
