<?php

declare(strict_types=1);

namespace Micro\Article\Domain\Factory;

use Micro\Article\Domain\ValueObject\ArchivedAt;
use Micro\Article\Domain\ValueObject\Description;
use Micro\Article\Domain\ValueObject\EventId;
use Micro\Article\Domain\ValueObject\Article;
use Micro\Article\Domain\ValueObject\PublishedAt;
use Micro\Article\Domain\ValueObject\ShortDescription;
use Micro\Article\Domain\ValueObject\Slug;
use Micro\Article\Domain\ValueObject\Status;
use Micro\Article\Domain\ValueObject\Title;
use MicroModule\Base\Domain\Factory\CommonValueObjectFactoryInterface;

/**
 * @interface ValueObjectFactoryInterface
 */
interface ValueObjectFactoryInterface extends CommonValueObjectFactoryInterface
{
    /**
     * Create Title ValueObject.
     */
    public function makeTitle(string $title): Title;

    /**
     * Create ShortDescription ValueObject.
     */
    public function makeShortDescription(string $shortDescription): ShortDescription;

    /**
     * Create Description ValueObject.
     */
    public function makeDescription(string $description): Description;

    /**
     * Create Slug ValueObject.
     */
    public function makeSlug(string $slug): Slug;

    /**
     * Create EventId ValueObject.
     */
    public function makeEventId(int $eventId): EventId;

    /**
     * Create Status ValueObject.
     */
    public function makeStatus(string $status): Status;

    /**
     * Create PublishedAt ValueObject.
     */
    public function makePublishedAt(\DateTimeInterface $publishedAt): PublishedAt;

    /**
     * Create ArchivedAt ValueObject.
     */
    public function makeArchivedAt(\DateTimeInterface $archivedAt): ArchivedAt;

    /**
     * Create Article ValueObject.
     */
    public function makeArticle(array $article): Article;
}
