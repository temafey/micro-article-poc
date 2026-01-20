<?php

declare(strict_types=1);

namespace Micro\Article\Domain\ReadModel;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Micro\Article\Domain\Entity\ArticleEntityInterface;
use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Domain\Exception\ValueObjectInvalidException;
use MicroModule\Base\Domain\ValueObject\Uuid;
use MicroModule\ValueObject\ValueObjectInterface;

/**
 * @class ArticleReadModel
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
#[Entity]
#[Table(name: 'article')]
class ArticleReadModel implements ArticleReadModelInterface
{
    #[Id]
    #[Column(name : 'uuid', type : Types::GUID, unique : true, nullable : true)]
    protected ?Uuid $uuid = null;

    #[Column(name : 'title', type : Types::STRING, nullable : true)]
    protected ?string $title = null;

    #[Column(name : 'short_description', type : Types::STRING, nullable : true)]
    protected ?string $shortDescription = null;

    #[Column(name : 'description', type : Types::STRING, nullable : true)]
    protected ?string $description = null;

    #[Column(name : 'slug', type : Types::STRING, nullable : true)]
    protected ?string $slug = null;

    #[Column(name : 'event_id', type : Types::INTEGER, nullable : true)]
    protected ?int $eventId = null;

    #[Column(name : 'status', type : Types::STRING, nullable : true)]
    protected ?string $status = null;

    #[Column(name : 'published_at', type : Types::DATETIME_MUTABLE, nullable : true)]
    protected ?\DateTimeInterface $publishedAt = null;

    #[Column(name : 'archived_at', type : Types::DATETIME_MUTABLE, nullable : true)]
    protected ?\DateTimeInterface $archivedAt = null;

    #[Column(name : 'created_at', type : Types::DATETIME_MUTABLE, nullable : true)]
    protected ?\DateTimeInterface $createdAt = null;

    #[Column(name : 'updated_at', type : Types::DATETIME_MUTABLE, nullable : true)]
    protected ?\DateTimeInterface $updatedAt = null;

    /**
     * Return uuid value object.
     */
    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    /**
     * Return title value.
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Return short_description value.
     */
    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    /**
     * Return description value.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Return slug value.
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * Return event_id value.
     */
    public function getEventId(): ?int
    {
        return $this->eventId;
    }

    /**
     * Return status value.
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * Return published_at value.
     */
    public function getPublishedAt(): ?\DateTimeInterface
    {
        return $this->publishedAt;
    }

    /**
     * Return archived_at value.
     */
    public function getArchivedAt(): ?\DateTimeInterface
    {
        return $this->archivedAt;
    }

    /**
     * Return created_at value.
     */
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Return updated_at value.
     */
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * Assemble entity from value object.
     */
    public function assembleFromValueObject(ValueObjectInterface $valueObject, ?Uuid $uuid): void
    {
        if (! $valueObject instanceof Article) {
            throw new ValueObjectInvalidException('ArticleEntity can be assembled only with Article value object');
        }

        $this->uuid = $uuid;
        $this->title = $valueObject->getTitle()?->toNative();
        $this->shortDescription = $valueObject->getShortDescription()?->toNative();
        $this->description = $valueObject->getDescription()?->toNative();
        $this->slug = $valueObject->getSlug()?->toNative();
        $this->eventId = $valueObject->getEventId()?->toNative();
        $this->status = $valueObject->getStatus()?->toNative();
        $this->publishedAt = $valueObject->getPublishedAt()?->toNative();
        $this->archivedAt = $valueObject->getArchivedAt()?->toNative();
        $this->createdAt = $valueObject->getCreatedAt()?->toNative();
        $this->updatedAt = $valueObject->getUpdatedAt()?->toNative();
    }

    /**
     * Convert entity object to array.
     */
    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid?->toNative(),
            'title' => $this->title,
            'short_description' => $this->shortDescription,
            'description' => $this->description,
            'slug' => $this->slug,
            'event_id' => $this->eventId,
            'status' => $this->status,
            'published_at' => $this->publishedAt?->format(\DateTimeInterface::ATOM),
            'archived_at' => $this->archivedAt?->format(\DateTimeInterface::ATOM),
            'created_at' => $this->createdAt?->format(\DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt?->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * Return entity primary key value.
     */
    public function getPrimaryKeyValue(): ?string
    {
        return $this->uuid?->toNative();
    }

    /**
     * Create ArticleReadModel by ArticleEntity.
     */
    public static function createByEntity(ArticleEntityInterface $entity): ArticleReadModelInterface
    {
        $readModel = new static();
        $readModel->assembleFromValueObject($entity->assembleToValueObject(), $entity->getUuid());

        return $readModel;
    }

    /**
     * Create ArticleReadModel by Article value object.
     */
    public static function createByValueObject(Article $entityValueObject, Uuid $uuid): ArticleReadModelInterface
    {
        $readModel = new static();
        $readModel->assembleFromValueObject($entityValueObject, $uuid);

        return $readModel;
    }

    /**
     * Update ArticleReadModel by Article value object.
     */
    public function updateByValueObject(Article $entityValueObject, Uuid $uuid): void
    {
        $this->assembleFromValueObject($entityValueObject, $uuid);
    }

    /**
     * Convert entity object to array.
     *
     * @return array<string, mixed>
     */
    public function normalize(): array
    {
        return $this->toArray();
    }

    /**
     * Specify data which should be serialized to JSON.
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
