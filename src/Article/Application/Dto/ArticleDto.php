<?php

declare(strict_types=1);

namespace Micro\Article\Application\Dto;

use Micro\Component\Common\Infrastructure\Mapper\Transform\DateTimeToIso8601Transform;
use Micro\Component\Common\Infrastructure\Mapper\Transform\UuidToStringTransform;
use Micro\Article\Domain\ReadModel\ArticleReadModel;
use Symfony\Component\ObjectMapper\Attribute\Map;

/**
 * Article Data Transfer Object.
 *
 * Uses Symfony ObjectMapper for automatic mapping from ReadModel.
 * Transforms are applied for UUID and DateTime conversions.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
#[Map(source: ArticleReadModel::class)]
class ArticleDto implements ArticleDtoInterface
{
    public function __construct(
        #[Map(transform: UuidToStringTransform::class)]
        public readonly ?string $uuid = null,
        public readonly ?string $title = null,
        public readonly ?string $shortDescription = null,
        public readonly ?string $description = null,
        public readonly ?string $slug = null,
        public readonly ?int $eventId = null,
        public readonly ?string $status = null,
        #[Map(transform: DateTimeToIso8601Transform::class)]
        public readonly ?string $publishedAt = null,
        #[Map(transform: DateTimeToIso8601Transform::class)]
        public readonly ?string $archivedAt = null,
        #[Map(transform: DateTimeToIso8601Transform::class)]
        public readonly ?string $createdAt = null,
        #[Map(transform: DateTimeToIso8601Transform::class)]
        public readonly ?string $updatedAt = null,
    ) {
    }

    /**
     * Convert array to DTO object.
     */
    public static function denormalize(array $data): static
    {
        $uuid = null;
        if (array_key_exists(static::UUID, $data)) {
            $uuid = $data[static::UUID];
        }

        $title = null;
        if (array_key_exists(static::TITLE, $data)) {
            $title = $data[static::TITLE];
        }

        $shortDescription = null;
        if (array_key_exists(static::SHORT_DESCRIPTION, $data)) {
            $shortDescription = $data[static::SHORT_DESCRIPTION];
        }

        $description = null;
        if (array_key_exists(static::DESCRIPTION, $data)) {
            $description = $data[static::DESCRIPTION];
        }

        $slug = null;
        if (array_key_exists(static::SLUG, $data)) {
            $slug = $data[static::SLUG];
        }

        $eventId = null;
        if (array_key_exists(static::EVENT_ID, $data)) {
            $eventId = $data[static::EVENT_ID];
        }

        $status = null;
        if (array_key_exists(static::STATUS, $data)) {
            $status = $data[static::STATUS];
        }

        $publishedAt = null;
        if (array_key_exists(static::PUBLISHED_AT, $data)) {
            $publishedAt = $data[static::PUBLISHED_AT];
        }

        $archivedAt = null;
        if (array_key_exists(static::ARCHIVED_AT, $data)) {
            $archivedAt = $data[static::ARCHIVED_AT];
        }

        $createdAt = null;
        if (array_key_exists(static::CREATED_AT, $data)) {
            $createdAt = $data[static::CREATED_AT];
        }

        $updatedAt = null;
        if (array_key_exists(static::UPDATED_AT, $data)) {
            $updatedAt = $data[static::UPDATED_AT];
        }

        return new static(
            $uuid,
            $title,
            $shortDescription,
            $description,
            $slug,
            $eventId,
            $status,
            $publishedAt,
            $archivedAt,
            $createdAt,
            $updatedAt
        );
    }

    /**
     * Convert dto object to array.
     */
    public function normalize(): array
    {
        $data = [];

        if ($this->uuid !== null) {
            $data[static::UUID] = $this->uuid;
        }

        if ($this->title !== null) {
            $data[static::TITLE] = $this->title;
        }

        if ($this->shortDescription !== null) {
            $data[static::SHORT_DESCRIPTION] = $this->shortDescription;
        }

        if ($this->description !== null) {
            $data[static::DESCRIPTION] = $this->description;
        }

        if ($this->slug !== null) {
            $data[static::SLUG] = $this->slug;
        }

        if ($this->eventId !== null) {
            $data[static::EVENT_ID] = $this->eventId;
        }

        if ($this->status !== null) {
            $data[static::STATUS] = $this->status;
        }

        if ($this->publishedAt !== null) {
            $data[static::PUBLISHED_AT] = $this->publishedAt;
        }

        if ($this->archivedAt !== null) {
            $data[static::ARCHIVED_AT] = $this->archivedAt;
        }

        if ($this->createdAt !== null) {
            $data[static::CREATED_AT] = $this->createdAt;
        }

        if ($this->updatedAt !== null) {
            $data[static::UPDATED_AT] = $this->updatedAt;
        }

        return $data;
    }
}
