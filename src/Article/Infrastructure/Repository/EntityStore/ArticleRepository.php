<?php

declare(strict_types=1);

namespace Micro\Article\Infrastructure\Repository\EntityStore;

use Broadway\EventSourcing\EventSourcingRepository;
use Broadway\EventStore\Dbal\DBALEventStore;
use Broadway\EventStore\EventStore;
use Micro\Article\Domain\Entity\ArticleEntityInterface;
use Micro\Article\Domain\Repository\EntityStore\ArticleRepositoryInterface;
use Micro\Article\Domain\Service\ArticleSlugGeneratorServiceInterface;
use MicroModule\Snapshotting\EventSourcing\SnapshottingEventSourcingRepository;
use MicroModule\Snapshotting\Snapshot\SnapshotRepositoryInterface;
use MicroModule\Snapshotting\Snapshot\Trigger\EventCountTrigger;
use MicroModule\Snapshotting\Snapshot\TriggerInterface;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @class ArticleRepository
 */
class ArticleRepository extends SnapshottingEventSourcingRepository implements ArticleRepositoryInterface
{
    public function __construct(
        #[Autowire(service: \Micro\Article\Infrastructure\Repository\EventSourcingStore\ArticleRepository::class)]
        EventSourcingRepository $eventSourcingRepository,
        #[Autowire(service: DBALEventStore::class)]
        EventStore $eventStore,
        #[Autowire(service: 'micro_module.snapshotting.snapshot.article.article.repository')]
        SnapshotRepositoryInterface $snapshotRepository,
        #[Autowire(service: EventCountTrigger::class)]
        TriggerInterface $trigger,
        protected ArticleSlugGeneratorServiceInterface $articleSlugGeneratorService,
    ) {
        parent::__construct($eventSourcingRepository, $eventStore, $snapshotRepository, $trigger);
    }

    /**
     * Retrieve ArticleEntity with applied events.
     */
    public function get(UuidInterface $uuid): ArticleEntityInterface
    {
        $entity = $this->load($uuid->toString());

        if (! $entity instanceof ArticleEntityInterface) {
            throw new \InvalidArgumentException('Return object should implement ArticleEntity.');
        }

        // Inject the slug generator service for update operations
        $entity->setArticleSlugGeneratorService($this->articleSlugGeneratorService);

        return $entity;
    }

    /**
     * Save ArticleEntity last uncommitted events.
     */
    public function store(ArticleEntityInterface $entity): void
    {
        $this->save($entity);
    }
}
