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
use MicroModule\Base\Domain\Factory\CommonValueObjectFactory;

/**
 * @class ValueObjectFactory
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ValueObjectFactory extends CommonValueObjectFactory implements ValueObjectFactoryInterface
{
    /**
     * Create Title ValueObject.
     */
    public function makeTitle(string $title): Title
    {
        return Title::fromNative($title);
    }

    /**
     * Create ShortDescription ValueObject.
     */
    public function makeShortDescription(string $shortDescription): ShortDescription
    {
        return ShortDescription::fromNative($shortDescription);
    }

    /**
     * Create Description ValueObject.
     */
    public function makeDescription(string $description): Description
    {
        return Description::fromNative($description);
    }

    /**
     * Create Slug ValueObject.
     */
    public function makeSlug(string $slug): Slug
    {
        return Slug::fromNative($slug);
    }

    /**
     * Create EventId ValueObject.
     */
    public function makeEventId(int $eventId): EventId
    {
        return EventId::fromNative($eventId);
    }

    /**
     * Create Status ValueObject.
     */
    public function makeStatus(string $status): Status
    {
        return Status::fromNative($status);
    }

    /**
     * Create PublishedAt ValueObject.
     */
    public function makePublishedAt(\DateTimeInterface $publishedAt): PublishedAt
    {
        return PublishedAt::fromNative($publishedAt);
    }

    /**
     * Create ArchivedAt ValueObject.
     */
    public function makeArchivedAt(\DateTimeInterface $archivedAt): ArchivedAt
    {
        return ArchivedAt::fromNative($archivedAt);
    }

    /**
     * Create Article ValueObject.
     */
    public function makeArticle(array $article): Article
    {
        return Article::fromNative($article);
    }
}
