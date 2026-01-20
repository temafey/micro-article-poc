<?php

declare(strict_types=1);

namespace Micro\Article\Infrastructure\Subscriber;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use Micro\Article\Domain\Event\ArticleArchivedEvent;
use Micro\Article\Domain\Event\ArticleCreatedEvent;
use Micro\Article\Domain\Event\ArticleDeletedEvent;
use Micro\Article\Domain\Event\ArticlePublishedEvent;
use Micro\Article\Domain\Event\ArticleUnpublishedEvent;
use Micro\Article\Domain\Event\ArticleUpdatedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Broadway EventListener for cache invalidation on Article domain events.
 *
 * Listens to Article aggregate domain events and invalidates relevant
 * cache entries to maintain data consistency.
 *
 * @see ADR-007 Cache Stampede Prevention
 */
#[AutoconfigureTag(name: 'broadway.domain.event_listener')]
final readonly class ArticleCacheInvalidationSubscriber implements EventListener
{
    public function __construct(
        #[Autowire(service: 'read_model.cache')]
        private TagAwareCacheInterface $readModelCache,
        #[Autowire(service: 'query.cache')]
        private TagAwareCacheInterface $queryCache,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Handle Broadway domain messages and invalidate cache accordingly.
     */
    public function handle(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();

        match (true) {
            $event instanceof ArticleCreatedEvent => $this->handleArticleCreated($event),
            $event instanceof ArticleUpdatedEvent => $this->handleArticleUpdated($event),
            $event instanceof ArticlePublishedEvent => $this->handleArticlePublished($event),
            $event instanceof ArticleUnpublishedEvent => $this->handleArticleUnpublished($event),
            $event instanceof ArticleArchivedEvent => $this->handleArticleArchived($event),
            $event instanceof ArticleDeletedEvent => $this->handleArticleDeleted($event),
            default => null,
        };
    }

    /**
     * Handle ArticleCreatedEvent - invalidate list caches.
     */
    private function handleArticleCreated(ArticleCreatedEvent $event): void
    {
        $this->invalidateListCaches();
        $this->log('ArticleCreatedEvent', $event->getUuid()->toString());
    }

    /**
     * Handle ArticleUpdatedEvent - invalidate item and list caches.
     */
    private function handleArticleUpdated(ArticleUpdatedEvent $event): void
    {
        $uuid = $event->getUuid()
            ->toString();
        $this->invalidateItemCache($uuid);
        $this->invalidateListCaches();
        $this->log('ArticleUpdatedEvent', $uuid);
    }

    /**
     * Handle ArticlePublishedEvent - invalidate item and status caches.
     */
    private function handleArticlePublished(ArticlePublishedEvent $event): void
    {
        $uuid = $event->getUuid()
            ->toString();
        $this->invalidateItemCache($uuid);
        $this->invalidateStatusCaches('published');
        $this->invalidateStatusCaches('draft');
        $this->invalidateListCaches();
        $this->log('ArticlePublishedEvent', $uuid);
    }

    /**
     * Handle ArticleUnpublishedEvent - invalidate item and status caches.
     */
    private function handleArticleUnpublished(ArticleUnpublishedEvent $event): void
    {
        $uuid = $event->getUuid()
            ->toString();
        $this->invalidateItemCache($uuid);
        $this->invalidateStatusCaches('published');
        $this->invalidateStatusCaches('unpublished');
        $this->invalidateListCaches();
        $this->log('ArticleUnpublishedEvent', $uuid);
    }

    /**
     * Handle ArticleArchivedEvent - invalidate item and status caches.
     */
    private function handleArticleArchived(ArticleArchivedEvent $event): void
    {
        $uuid = $event->getUuid()
            ->toString();
        $this->invalidateItemCache($uuid);
        $this->invalidateStatusCaches('archived');
        $this->invalidateListCaches();
        $this->log('ArticleArchivedEvent', $uuid);
    }

    /**
     * Handle ArticleDeletedEvent - invalidate item and list caches.
     */
    private function handleArticleDeleted(ArticleDeletedEvent $event): void
    {
        $uuid = $event->getUuid()
            ->toString();
        $this->invalidateItemCache($uuid);
        $this->invalidateListCaches();
        $this->log('ArticleDeletedEvent', $uuid);
    }

    /**
     * Invalidate cache for a specific article item.
     */
    private function invalidateItemCache(string $uuid): void
    {
        $tag = sprintf('article.%s', $uuid);
        $this->readModelCache->invalidateTags([$tag]);
        $this->queryCache->invalidateTags([$tag]);
    }

    /**
     * Invalidate all list-related caches.
     */
    private function invalidateListCaches(): void
    {
        $this->readModelCache->invalidateTags(['article.list']);
        $this->queryCache->invalidateTags(['article.list']);
    }

    /**
     * Invalidate caches for a specific status.
     */
    private function invalidateStatusCaches(string $status): void
    {
        $tag = sprintf('article.status.%s', $status);
        $this->readModelCache->invalidateTags([$tag]);
        $this->queryCache->invalidateTags([$tag]);
    }

    /**
     * Log cache invalidation event.
     */
    private function log(string $event, string $uuid): void
    {
        $this->logger->info('Cache invalidated', [
            'event' => $event,
            'uuid' => $uuid,
            'subscriber' => self::class,
        ]);
    }
}
