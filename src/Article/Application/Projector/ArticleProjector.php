<?php

declare(strict_types=1);

namespace Micro\Article\Application\Projector;

use Broadway\ReadModel\Projector;
use Micro\Article\Domain\Event\ArticleArchivedEvent;
use Micro\Article\Domain\Event\ArticleCreatedEvent;
use Micro\Article\Domain\Event\ArticleDeletedEvent;
use Micro\Article\Domain\Event\ArticlePublishedEvent;
use Micro\Article\Domain\Event\ArticleUnpublishedEvent;
use Micro\Article\Domain\Event\ArticleUpdatedEvent;
use Micro\Article\Domain\Factory\ReadModelFactory;
use Micro\Article\Domain\Factory\ReadModelFactoryInterface;
use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use Micro\Article\Domain\Repository\ReadModel\ArticleRepositoryInterface as ReadModelInterface;
use Micro\Article\Infrastructure\Repository\EntityStore\ArticleRepository;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @class ArticleProjector
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
#[AutoconfigureTag(name: 'broadway.domain.event_listener')]
class ArticleProjector extends Projector
{
    public function __construct(
        #[Autowire(service: ArticleRepository::class)]
        protected ArticleRepository $entityStore,
        #[Autowire(service: \Micro\Article\Infrastructure\Repository\ReadModel\ArticleRepository::class)]
        protected ReadModelInterface $readModelStore,
        #[Autowire(service: ReadModelFactory::class)]
        protected ReadModelFactoryInterface $readModelFactory,
    ) {
    }

    /**
     * Apply ArticleCreatedEvent event.
     */
    public function applyArticleCreatedEvent(ArticleCreatedEvent $event): void
    {
        $entity = $this->entityStore->get($event->getUuid());
        $readModel = $this->readModelFactory->makeArticleActualInstanceByEntity($entity);
        $this->readModelStore->add($readModel);
    }

    /**
     * Apply ArticleUpdatedEvent event.
     */
    public function applyArticleUpdatedEvent(ArticleUpdatedEvent $event): void
    {
        $entity = $this->entityStore->get($event->getUuid());
        $readModel = $this->readModelFactory->makeArticleActualInstanceByEntity($entity);
        $this->readModelStore->update($readModel);
    }

    /**
     * Apply ArticlePublishedEvent event.
     */
    public function applyArticlePublishedEvent(ArticlePublishedEvent $event): void
    {
        $entity = $this->entityStore->get($event->getUuid());
        $readModel = $this->readModelFactory->makeArticleActualInstanceByEntity($entity);
        $this->readModelStore->update($readModel);
    }

    /**
     * Apply ArticleUnpublishedEvent event.
     */
    public function applyArticleUnpublishedEvent(ArticleUnpublishedEvent $event): void
    {
        $entity = $this->entityStore->get($event->getUuid());
        $readModel = $this->readModelFactory->makeArticleActualInstanceByEntity($entity);
        $this->readModelStore->update($readModel);
    }

    /**
     * Apply ArticleArchivedEvent event.
     */
    public function applyArticleArchivedEvent(ArticleArchivedEvent $event): void
    {
        $entity = $this->entityStore->get($event->getUuid());
        $readModel = $this->readModelFactory->makeArticleActualInstanceByEntity($entity);
        $this->readModelStore->update($readModel);
    }

    /**
     * Apply ArticleDeletedEvent event.
     */
    public function applyArticleDeletedEvent(ArticleDeletedEvent $event): void
    {
        $readModel = $this->readModelStore->get($event->getUuid());

        if (! $readModel instanceof ArticleReadModelInterface) {
            throw new \Exception(sprintf("ReadModel with id '%s' not found", $event->getUuid()->toNative()));
        }

        $this->readModelStore->delete($readModel);
    }
}
