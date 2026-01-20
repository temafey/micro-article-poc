<?php

declare(strict_types=1);

namespace Micro\Article\Infrastructure\Repository\EventSourcingStore;

use Broadway\EventHandling\EventBus as EventBusInterface;
use Broadway\EventSourcing\AggregateFactory\PublicConstructorAggregateFactory;
use Broadway\EventSourcing\EventSourcingRepository;
use Broadway\EventSourcing\EventStreamDecorator as EventStreamDecoratorInterface;
use Broadway\EventStore\EventStore as EventStoreInterface;
use Micro\Article\Domain\Entity\ArticleEntity;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @class ArticleRepository
 */
class ArticleRepository extends EventSourcingRepository
{
    /**
     * @param EventStreamDecoratorInterface[] $eventStreamDecorators
     */
    public function __construct(
        #[Autowire(service: 'broadway.event_store')]
        EventStoreInterface $eventStore,
        #[Autowire(service: 'queue_event_bus.global')]
        EventBusInterface $eventBus,
        array $eventStreamDecorators = [],
    ) {
        parent::__construct(
            $eventStore,
            $eventBus,
            ArticleEntity::class,
            new PublicConstructorAggregateFactory(),
            $eventStreamDecorators
        );
    }
}
