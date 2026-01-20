<?php

declare(strict_types=1);

namespace Micro\Article\Domain\Entity;

use Assert\Assertion;
use Broadway\EventSourcing\EventSourcedAggregateRoot;
use Broadway\Serializer\Serializable;
use Micro\Article\Domain\Event\ArticleArchivedEvent;
use Micro\Article\Domain\Event\ArticleCreatedEvent;
use Micro\Article\Domain\Event\ArticleDeletedEvent;
use Micro\Article\Domain\Event\ArticlePublishedEvent;
use Micro\Article\Domain\Event\ArticleUnpublishedEvent;
use Micro\Article\Domain\Event\ArticleUpdatedEvent;
use Micro\Article\Domain\Factory\EventFactory;
use Micro\Article\Domain\Factory\EventFactoryInterface;
use Micro\Article\Domain\Factory\ValueObjectFactory;
use Micro\Article\Domain\Factory\ValueObjectFactoryInterface;
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
use MicroModule\Base\Domain\Exception\ValueObjectInvalidException;
use MicroModule\Base\Domain\ValueObject\CreatedAt;
use MicroModule\Base\Domain\ValueObject\Payload;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\UpdatedAt;
use MicroModule\Base\Domain\ValueObject\Uuid;
use MicroModule\ValueObject\ValueObjectInterface;

/**
 * @class ArticleEntity
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
class ArticleEntity extends EventSourcedAggregateRoot implements ArticleEntityInterface, Serializable
{
    /**
     * process_uuid value object.
     */
    protected ?ProcessUuid $processUuid = null;

    /**
     * uuid value object.
     */
    protected ?Uuid $uuid = null;

    /**
     * title value object.
     */
    protected ?Title $title = null;

    /**
     * short_description value object.
     */
    protected ?ShortDescription $shortDescription = null;

    /**
     * description value object.
     */
    protected ?Description $description = null;

    /**
     * slug value object.
     */
    protected ?Slug $slug = null;

    /**
     * event_id value object.
     */
    protected ?EventId $eventId = null;

    /**
     * status value object.
     */
    protected ?Status $status = null;

    /**
     * published_at value object.
     */
    protected ?PublishedAt $publishedAt = null;

    /**
     * archived_at value object.
     */
    protected ?ArchivedAt $archivedAt = null;

    /**
     * created_at value object.
     */
    protected ?CreatedAt $createdAt = null;

    /**
     * updated_at value object.
     */
    protected ?UpdatedAt $updatedAt = null;

    /**
     * Note: ArticleSlugGeneratorServiceInterface is optional to support deserialization scenarios
     * (snapshots, event replay). It must be provided when creating new entities via EntityFactory.
     */
    public function __construct(
        protected ?EventFactoryInterface $eventFactory = null,
        protected ?ValueObjectFactoryInterface $valueObjectFactory = null,
        protected ?ArticleSlugGeneratorServiceInterface $articleSlugGeneratorService = null,
    ) {
        $this->eventFactory ??= new EventFactory();
        $this->valueObjectFactory ??= new ValueObjectFactory();
        // ArticleSlugGeneratorService is validated at usage time in articleCreate/articleUpdate methods
    }

    /**
     * Factory method for creating a new UuidEntity.
     */
    public static function create(
        ProcessUuid $processUuid,
        Uuid $uuid,
        Article $article,
        ?EventFactoryInterface $eventFactory = null,
        ?ValueObjectFactoryInterface $valueObjectFactory = null,
        ?ArticleSlugGeneratorServiceInterface $articleSlugGeneratorService = null,
    ): self {
        $entity = new static($eventFactory, $valueObjectFactory, $articleSlugGeneratorService);
        $entity->uuid = $uuid;
        $entity->articleCreate($processUuid, $article);

        return $entity;
    }

    /**
     * Factory method for creating a new ArticleEntity.
     */
    public static function createActual(
        Uuid $uuid,
        Article $article,
        ?EventFactoryInterface $eventFactory = null,
        ?ValueObjectFactoryInterface $valueObjectFactory = null,
        ?ArticleSlugGeneratorServiceInterface $articleSlugGeneratorService = null,
    ): self {
        $entity = new static($eventFactory, $valueObjectFactory, $articleSlugGeneratorService);
        $entity->uuid = $uuid;
        $entity->assembleFromValueObject($article);

        return $entity;
    }

    /**
     * Execute article-create command.
     *
     * @business-purpose Create new article article with SEO-friendly slug and optional event association to inform stakeholders about event-related updates
     *
     * @business-trigger Content Manager initiates article creation via CMS interface after preparing article content
     *
     * @business-rules Title required (max 255 chars), descriptions required with valid HTML/Markdown formatting, slug auto-generated and must be unique, event reference must exist if provided, all content sanitized for XSS prevention
     *
     * @business-outcome Article article created with unique identifier, auto-generated SEO slug, stored with preserved formatting, associated with event if specified, ready for publication workflow, indexed for search
     *
     * @entity-changes  (type: update)
     *
     * @workflow-context Initial content creation step in article publication lifecycle, precedes publication approval workflow, triggers search indexing queue, populates cache for listing queries, creates audit trail entry
     *
     * @business-context article_article_691af8cd38e980.74753648 | tags: article, entity, triggered, state_changing, workflow_integrated
     */
    public function articleCreate(ProcessUuid $processUuid, Article $article, ?Payload $payload = null): void
    {
        // Validate service availability (required for slug generation)
        if (! $this->articleSlugGeneratorService instanceof ArticleSlugGeneratorServiceInterface) {
            throw new \InvalidArgumentException(
                'ArticleSlugGeneratorServiceInterface is required for article creation. Use EntityFactory to create ArticleEntity instances.'
            );
        }

        // Service execution: Data transformation
        // Extract data from value objects for service consumption
        $title = $article->getTitle();

        if (! $title instanceof Title) {
            throw new \InvalidArgumentException('Article title is required for creation');
        }

        $titleData = $title->toNative();

        // Generate unique SEO-friendly slug from title
        $generatedSlug = $this->articleSlugGeneratorService->generateSlug($titleData);

        // Apply generated slug and default values to Article value object
        $articleData = $article->toArray();
        $articleData['slug'] = $generatedSlug;

        // Set default status to 'draft' if not provided (business rule: new articles start as drafts)
        if (! isset($articleData['status'])) {
            $articleData['status'] = Status::DRAFT;
        }

        // Set created_at timestamp if not provided
        $now = new \DateTime()
            ->format(\DateTimeInterface::ATOM);
        if (! isset($articleData['created_at'])) {
            $articleData['created_at'] = $now;
        }

        // Set updated_at timestamp if not provided (same as created_at initially)
        if (! isset($articleData['updated_at'])) {
            $articleData['updated_at'] = $now;
        }

        $articleWithDefaults = $this->valueObjectFactory->makeArticle($articleData);

        // Apply event with updated Article containing generated slug and defaults
        $this->apply($this->eventFactory->makeArticleCreatedEvent($processUuid, $this->uuid, $articleWithDefaults, $payload));
    }

    /**
     * Apply ArticleCreatedEvent event.
     */
    public function applyArticleCreatedEvent(ArticleCreatedEvent $event): void
    {
        $this->processUuid = $event->getProcessUuid();
        $this->uuid = $event->getUuid();
        $this->assembleFromValueObject($event->getArticle());
    }

    /**
     * Execute article-update command.
     *
     * @business-purpose Update existing article article content while maintaining slug consistency, event association integrity, and triggering re-indexing workflows
     *
     * @business-trigger Content Manager modifies existing article content via CMS interface to correct errors, add information, or update association
     *
     * @business-rules Article must exist, title constraints apply (max 255 chars), slug regenerated if title changes with uniqueness validation, event validation required if changed, content sanitized, preserve publication status
     *
     * @business-outcome Article article updated with new content, slug potentially updated with old URL redirect created, change timestamp recorded, cache invalidated for affected queries, search index updated, audit log entry created
     *
     * @entity-changes  (type: update)
     *
     * @workflow-context Content revision workflow within publication lifecycle, triggers re-indexing for search, cache invalidation for listing and detail views, potential URL redirect setup if slug changed, maintains publication continuity
     *
     * @business-context article_article_691af8cd38f445.76377671 | tags: article, entity, triggered, state_changing, workflow_integrated
     */
    public function articleUpdate(ProcessUuid $processUuid, Article $article, ?Payload $payload = null): void
    {
        // Validate service availability (required for slug generation)
        if (! $this->articleSlugGeneratorService instanceof ArticleSlugGeneratorServiceInterface) {
            throw new \InvalidArgumentException(
                'ArticleSlugGeneratorServiceInterface is required for article update. Use EntityFactory to create ArticleEntity instances.'
            );
        }

        // Service execution: Data transformation
        // Extract data from value objects for service consumption
        $title = $article->getTitle();

        if (! $title instanceof Title) {
            throw new \InvalidArgumentException('Article title is required for update');
        }

        $titleData = $title->toNative();
        // Extract slug for service parameter: existingSlug
        $existingSlugData = $article->getSlug()?->toNative();

        // Generate slug with uniqueness check excluding current entity
        $excludeUuid = $this->uuid?->toNative();
        $generatedSlug = $this->articleSlugGeneratorService->generateSlug($titleData, $existingSlugData, $excludeUuid);

        // Apply generated slug to Article value object
        $articleData = $article->toArray();
        $articleData['slug'] = $generatedSlug;
        $articleWithSlug = $this->valueObjectFactory->makeArticle($articleData);

        // Apply event with updated Article containing generated slug
        $this->apply($this->eventFactory->makeArticleUpdatedEvent($processUuid, $this->uuid, $articleWithSlug, $payload));
    }

    /**
     * Apply ArticleUpdatedEvent event.
     */
    public function applyArticleUpdatedEvent(ArticleUpdatedEvent $event): void
    {
        $this->processUuid = $event->getProcessUuid();
        $this->assembleFromValueObject($event->getArticle());
    }

    /**
     * Execute article-publish command.
     *
     * @business-purpose Transition article article from draft to published state making it visible to end users and publicly accessible via website and search
     *
     * @business-trigger Publisher approves article article for publication after content quality review and validation
     *
     * @business-rules Article must exist, all required fields populated (title, descriptions, slug), content must pass quality validation, slug uniqueness confirmed, event reference valid if present
     *
     * @business-outcome Article article status changed to 'published', visible on public website listings, indexed in search engines, notifications sent to subscribers if configured, SEO metadata updated, cache populated for public access
     *
     * @entity-changes  (type: update)
     *
     * @workflow-context Publication approval workflow final step before public visibility, triggers notification workflows for subscribers, SEO sitemap update, social media integration hooks, analytics tracking initialization
     *
     * @business-context article_article_691af8cd38fd29.07462470 | tags: article, entity, triggered, state_changing, workflow_integrated
     */
    public function articlePublish(ProcessUuid $processUuid, ?Payload $payload = null): void
    {
        $status = $this->valueObjectFactory->makeStatus('published');
        $now = new \DateTime();
        $publishedAt = $this->valueObjectFactory->makePublishedAt($now);
        $updatedAt = $this->valueObjectFactory->makeUpdatedAt($now->format(\DateTimeInterface::ATOM));
        $this->apply(
            $this->eventFactory->makeArticlePublishedEvent(
                $processUuid,
                $this->uuid,
                $status,
                $publishedAt,
                $updatedAt,
                $payload
            )
        );
    }

    /**
     * Apply ArticlePublishedEvent event.
     */
    public function applyArticlePublishedEvent(ArticlePublishedEvent $event): void
    {
        $this->processUuid = $event->getProcessUuid();
        $this->status = $event->getStatus();
        $this->publishedAt = $event->getPublishedAt();
        $this->updatedAt = $event->getUpdatedAt();
    }

    /**
     * Execute article-unpublish command.
     *
     * @business-purpose Remove article article from public visibility while preserving content for potential re-publication or archival purposes
     *
     * @business-trigger Content Manager unpublishes article due to content issues, event cancellation, or temporary content removal requirements
     *
     * @business-rules Article must exist and be in published status, cannot unpublish already unpublished article, preserve all content and metadata
     *
     * @business-outcome Article article status changed to 'draft', removed from public listings and search results, URL still accessible but returns 'not published' state, search index updated to remove from results
     *
     * @entity-changes  (type: update)
     *
     * @workflow-context Content lifecycle management for temporary removal, preserves SEO history and URL structure, allows re-publication without creating new article, maintains audit trail of publication history
     *
     * @business-context article_article_691af8cd390434.24047228 | tags: article, entity, triggered, state_changing, workflow_integrated
     */
    public function articleUnpublish(ProcessUuid $processUuid, ?Payload $payload = null): void
    {
        $status = $this->valueObjectFactory->makeStatus('draft');
        $updatedAt = $this->valueObjectFactory->makeUpdatedAt(new \DateTime()->format(\DateTimeInterface::ATOM));
        $this->apply(
            $this->eventFactory->makeArticleUnpublishedEvent($processUuid, $this->uuid, $status, $updatedAt, $payload)
        );
    }

    /**
     * Apply ArticleUnpublishedEvent event.
     */
    public function applyArticleUnpublishedEvent(ArticleUnpublishedEvent $event): void
    {
        $this->processUuid = $event->getProcessUuid();
        $this->status = $event->getStatus();
        $this->updatedAt = $event->getUpdatedAt();
    }

    /**
     * Execute article-archive command.
     *
     * @business-purpose Move article article to archived state removing from active listings while preserving content and maintaining URL accessibility for historical reference
     *
     * @business-trigger Content Manager archives outdated or event-completed article to reduce active content noise and improve content organization
     *
     * @business-rules Article must exist, cannot archive already archived article, preserve all content and metadata, maintain URL accessibility
     *
     * @business-outcome Article article status changed to 'archived', removed from active listings, searchable in archive section only, URL still accessible with archived status indication, SEO value preserved for historical searches
     *
     * @entity-changes  (type: update)
     *
     * @workflow-context Content lifecycle management for completed events, preserves SEO value and historical record, reduces noise in active content listings, maintains accessibility for reference and historical searches
     *
     * @business-context article_article_691af8cd390b79.54869908 | tags: article, entity, triggered, state_changing, workflow_integrated
     */
    public function articleArchive(ProcessUuid $processUuid, ?Payload $payload = null): void
    {
        $status = $this->valueObjectFactory->makeStatus('archived');
        $now = new \DateTime();
        $archivedAt = $this->valueObjectFactory->makeArchivedAt($now);
        $updatedAt = $this->valueObjectFactory->makeUpdatedAt($now->format(\DateTimeInterface::ATOM));
        $this->apply(
            $this->eventFactory->makeArticleArchivedEvent(
                $processUuid,
                $this->uuid,
                $status,
                $archivedAt,
                $updatedAt,
                $payload
            )
        );
    }

    /**
     * Apply ArticleArchivedEvent event.
     */
    public function applyArticleArchivedEvent(ArticleArchivedEvent $event): void
    {
        $this->processUuid = $event->getProcessUuid();
        $this->status = $event->getStatus();
        $this->archivedAt = $event->getArchivedAt();
        $this->updatedAt = $event->getUpdatedAt();
    }

    /**
     * Execute article-delete command.
     *
     * @business-purpose Permanently remove article article from system including all references, search indexes, and cached data for compliance or error correction
     *
     * @business-trigger Administrator permanently deletes article article due to legal requirements, compliance issues, or critical content errors requiring complete removal
     *
     * @business-rules Article must exist, deletion is permanent and irreversible, requires administrator privileges, create audit log entry before deletion
     *
     * @business-outcome Article article permanently deleted from database, search index updated to remove all references, cache cleared for all queries, URL returns 404 Not Found, audit log created with deletion details, redirect rule created to 410 Gone status
     *
     * @entity-changes  (type: update)
     *
     * @workflow-context Hard deletion for compliance or error correction, triggers comprehensive cleanup workflows, URL redirect to 410 Gone for SEO, audit trail for compliance reporting, irreversible operation requiring administrator confirmation
     *
     * @business-context article_article_691af8cd3910f0.14103871 | tags: article, entity, triggered, state_changing, workflow_integrated
     */
    public function articleDelete(ProcessUuid $processUuid, ?Payload $payload = null): void
    {
        $this->apply($this->eventFactory->makeArticleDeletedEvent($processUuid, $this->uuid, $payload));
    }

    /**
     * Apply ArticleDeletedEvent event.
     */
    public function applyArticleDeletedEvent(ArticleDeletedEvent $event): void
    {
        $this->processUuid = $event->getProcessUuid();
    }

    /**
     * Return process_uuid value object.
     */
    public function getProcessUuid(): ?ProcessUuid
    {
        return $this->processUuid;
    }

    /**
     * Return uuid value object.
     */
    public function getUuid(): ?Uuid
    {
        return $this->uuid;
    }

    /**
     * Return title value object.
     */
    public function getTitle(): ?Title
    {
        return $this->title;
    }

    /**
     * Return short_description value object.
     */
    public function getShortDescription(): ?ShortDescription
    {
        return $this->shortDescription;
    }

    /**
     * Return description value object.
     */
    public function getDescription(): ?Description
    {
        return $this->description;
    }

    /**
     * Return slug value object.
     */
    public function getSlug(): ?Slug
    {
        return $this->slug;
    }

    /**
     * Return event_id value object.
     */
    public function getEventId(): ?EventId
    {
        return $this->eventId;
    }

    /**
     * Return status value object.
     */
    public function getStatus(): ?Status
    {
        return $this->status;
    }

    /**
     * Return published_at value object.
     */
    public function getPublishedAt(): ?PublishedAt
    {
        return $this->publishedAt;
    }

    /**
     * Return archived_at value object.
     */
    public function getArchivedAt(): ?ArchivedAt
    {
        return $this->archivedAt;
    }

    /**
     * Return created_at value object.
     */
    public function getCreatedAt(): ?CreatedAt
    {
        return $this->createdAt;
    }

    /**
     * Return updated_at value object.
     */
    public function getUpdatedAt(): ?UpdatedAt
    {
        return $this->updatedAt;
    }

    /**
     * Factory method for creating a new ArticleEntity.
     */
    public static function deserialize(array $data): self
    {
        Assertion::keyExists($data, self::KEY_UUID);
        $article = Article::fromNative($data);

        return static::createActual(Uuid::fromNative($data[self::KEY_UUID]), $article);
    }

    /**
     * Assemble entity from value object.
     */
    public function assembleFromValueObject(ValueObjectInterface $valueObject): void
    {
        if (! $valueObject instanceof Article) {
            throw new ValueObjectInvalidException('ArticleEntity can be assembled only with Article value object');
        }

        if ($valueObject->getTitle() instanceof Title) {
            $this->title = $valueObject->getTitle();
        }

        if ($valueObject->getShortDescription() instanceof ShortDescription) {
            $this->shortDescription = $valueObject->getShortDescription();
        }

        if ($valueObject->getDescription() instanceof Description) {
            $this->description = $valueObject->getDescription();
        }

        if ($valueObject->getSlug() instanceof Slug) {
            $this->slug = $valueObject->getSlug();
        }

        if ($valueObject->getEventId() instanceof EventId) {
            $this->eventId = $valueObject->getEventId();
        }

        if ($valueObject->getStatus() instanceof Status) {
            $this->status = $valueObject->getStatus();
        }

        if ($valueObject->getPublishedAt() instanceof PublishedAt) {
            $this->publishedAt = $valueObject->getPublishedAt();
        }

        if ($valueObject->getArchivedAt() instanceof ArchivedAt) {
            $this->archivedAt = $valueObject->getArchivedAt();
        }

        if ($valueObject->getCreatedAt() instanceof CreatedAt) {
            $this->createdAt = $valueObject->getCreatedAt();
        }

        if ($valueObject->getUpdatedAt() instanceof UpdatedAt) {
            $this->updatedAt = $valueObject->getUpdatedAt();
        }
    }

    /**
     * Assemble value object from entity.
     */
    public function assembleToValueObject(): ValueObjectInterface
    {
        $article = $this->normalize();

        return Article::fromNative($article);
    }

    /**
     * Convert entity object to array.
     */
    public function normalize(): array
    {
        return [
            'process_uuid' => $this->getProcessUuid()?->toNative(),
            'uuid' => $this->getUuid()?->toNative(),
            'title' => $this->getTitle()?->toNative(),
            'short_description' => $this->getShortDescription()?->toNative(),
            'description' => $this->getDescription()?->toNative(),
            'slug' => $this->getSlug()?->toNative(),
            'event_id' => $this->getEventId()?->toNative(),
            'status' => $this->getStatus()?->toNative(),
            'published_at' => $this->getPublishedAt()?->toNative(),
            'archived_at' => $this->getArchivedAt()?->toNative(),
            'created_at' => $this->getCreatedAt()?->toNative(),
            'updated_at' => $this->getUpdatedAt()?->toNative(),
        ];
    }

    /**
     * Converting an object into an array.
     */
    public function serialize(): array
    {
        return $this->normalize();
    }

    /**
     * Return current aggregate root unique key.
     */
    public function getAggregateRootId(): string
    {
        return $this->uuid->toNative();
    }

    /**
     * Return entity primary key value.
     */
    public function getPrimaryKeyValue(): string
    {
        return $this->getAggregateRootId();
    }

    /**
     * Set the ArticleSlugGeneratorService for entities loaded from event store.
     * This is required for articleUpdate operations that need slug regeneration.
     */
    public function setArticleSlugGeneratorService(ArticleSlugGeneratorServiceInterface $articleSlugGeneratorService): void
    {
        $this->articleSlugGeneratorService = $articleSlugGeneratorService;
    }
}
