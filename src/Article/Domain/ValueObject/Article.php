<?php

declare(strict_types=1);

namespace Micro\Article\Domain\ValueObject;

use MicroModule\Base\Domain\ValueObject\BaseEntityValueObject;
use MicroModule\Base\Domain\ValueObject\CreatedAt;
use MicroModule\Base\Domain\ValueObject\UpdatedAt;

/**
 * @class Article
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class Article extends BaseEntityValueObject
{
    /**
     * Fields, that should be compared.
     */
    public const COMPARED_FIELDS = [
        'title',
        'short_description',
        'description',
        'slug',
        'event_id',
        'status',
        'published_at',
        'archived_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Return Title value object.
     */
    protected ?Title $title = null;

    /**
     * Return ShortDescription value object.
     */
    protected ?ShortDescription $shortDescription = null;

    /**
     * Return Description value object.
     */
    protected ?Description $description = null;

    /**
     * Return Slug value object.
     */
    protected ?Slug $slug = null;

    /**
     * Return EventId value object.
     */
    protected ?EventId $eventId = null;

    /**
     * Return Status value object.
     */
    protected ?Status $status = null;

    /**
     * Return PublishedAt value object.
     */
    protected ?PublishedAt $publishedAt = null;

    /**
     * Return ArchivedAt value object.
     */
    protected ?ArchivedAt $archivedAt = null;

    /**
     * Return CreatedAt value object.
     */
    protected ?CreatedAt $createdAt = null;

    /**
     * Return UpdatedAt value object.
     */
    protected ?UpdatedAt $updatedAt = null;

    /**
     * Build Article object from array.
     */
    public static function fromArray(array $data): static
    {
        $valueObject = new static();
        if (isset($data['title'])) {
            $valueObject->title = Title::fromNative($data['title']);
        }

        if (isset($data['short_description'])) {
            $valueObject->shortDescription = ShortDescription::fromNative($data['short_description']);
        }

        if (isset($data['description'])) {
            $valueObject->description = Description::fromNative($data['description']);
        }

        if (isset($data['slug'])) {
            $valueObject->slug = Slug::fromNative($data['slug']);
        }

        if (isset($data['event_id'])) {
            $valueObject->eventId = EventId::fromNative($data['event_id']);
        }

        if (isset($data['status'])) {
            $valueObject->status = Status::fromNative($data['status']);
        }

        if (isset($data['published_at'])) {
            $valueObject->publishedAt = PublishedAt::fromNative($data['published_at']);
        }

        if (isset($data['archived_at'])) {
            $valueObject->archivedAt = ArchivedAt::fromNative($data['archived_at']);
        }

        if (isset($data['created_at'])) {
            $valueObject->createdAt = CreatedAt::fromNative($data['created_at']);
        }

        if (isset($data['updated_at'])) {
            $valueObject->updatedAt = UpdatedAt::fromNative($data['updated_at']);
        }

        return $valueObject;
    }

    /**
     * Build Article object from array.
     */
    public function toArray(): array
    {
        $data = [];
        if ($this->title instanceof Title) {
            $data['title'] = $this->title->toNative();
        }

        if ($this->shortDescription instanceof ShortDescription) {
            $data['short_description'] = $this->shortDescription->toNative();
        }

        if ($this->description instanceof Description) {
            $data['description'] = $this->description->toNative();
        }

        if ($this->slug instanceof Slug) {
            $data['slug'] = $this->slug->toNative();
        }

        if ($this->eventId instanceof EventId) {
            $data['event_id'] = $this->eventId->toNative();
        }

        if ($this->status instanceof Status) {
            $data['status'] = $this->status->toNative();
        }

        if ($this->publishedAt instanceof PublishedAt) {
            $data['published_at'] = $this->publishedAt->toNative();
        }

        if ($this->archivedAt instanceof ArchivedAt) {
            $data['archived_at'] = $this->archivedAt->toNative();
        }

        if ($this->createdAt instanceof CreatedAt) {
            $data['created_at'] = $this->createdAt->toNative();
        }

        if ($this->updatedAt instanceof UpdatedAt) {
            $data['updated_at'] = $this->updatedAt->toNative();
        }

        return $this->enrich($data);
    }

    /**
     * Return Title value object.
     */
    public function getTitle(): ?Title
    {
        return $this->title;
    }

    /**
     * Return ShortDescription value object.
     */
    public function getShortDescription(): ?ShortDescription
    {
        return $this->shortDescription;
    }

    /**
     * Return Description value object.
     */
    public function getDescription(): ?Description
    {
        return $this->description;
    }

    /**
     * Return Slug value object.
     */
    public function getSlug(): ?Slug
    {
        return $this->slug;
    }

    /**
     * Return EventId value object.
     */
    public function getEventId(): ?EventId
    {
        return $this->eventId;
    }

    /**
     * Return Status value object.
     */
    public function getStatus(): ?Status
    {
        return $this->status;
    }

    /**
     * Return PublishedAt value object.
     */
    public function getPublishedAt(): ?PublishedAt
    {
        return $this->publishedAt;
    }

    /**
     * Return ArchivedAt value object.
     */
    public function getArchivedAt(): ?ArchivedAt
    {
        return $this->archivedAt;
    }

    /**
     * Return CreatedAt value object.
     */
    public function getCreatedAt(): ?CreatedAt
    {
        return $this->createdAt;
    }

    /**
     * Return UpdatedAt value object.
     */
    public function getUpdatedAt(): ?UpdatedAt
    {
        return $this->updatedAt;
    }
}
