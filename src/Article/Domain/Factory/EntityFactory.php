<?php

declare(strict_types=1);

namespace Micro\Article\Domain\Factory;

use Micro\Article\Domain\Entity\ArticleEntity;
use Micro\Article\Domain\Entity\ArticleEntityInterface;
use Micro\Article\Domain\Service\ArticleSlugGeneratorServiceInterface;
use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @class EntityFactory
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EntityFactory implements EntityFactoryInterface
{
    public function __construct(
        protected EventFactoryInterface $eventFactory,
        protected ValueObjectFactoryInterface $valueObjectFactory,
        protected ArticleSlugGeneratorServiceInterface $articleSlugGeneratorService,
    ) {
    }

    /**
     * Create ArticleEntity instance from value object with Uuid & ProcessId.
     *
     * @param ProcessUuid                            $processUuid              The process UUID for tracking
     * @param Article                                   $article                     The Article value object with entity data
     * @param UuidInterface|null                     $uuid                     Optional UUID for client-generated identifiers; if null, a new UUID is generated
     * @param EventFactoryInterface|null             $eventFactory             Optional event factory override
     * @param ValueObjectFactoryInterface|null       $valueObjectFactory       Optional value object factory override
     * @param ArticleSlugGeneratorServiceInterface|null $articleSlugGeneratorService Optional slug generator override
     */
    public function createArticleInstance(
        ProcessUuid $processUuid,
        Article $article,
        ?UuidInterface $uuid = null,
        ?EventFactoryInterface $eventFactory = null,
        ?ValueObjectFactoryInterface $valueObjectFactory = null,
        ?ArticleSlugGeneratorServiceInterface $articleSlugGeneratorService = null,
    ): ArticleEntityInterface {
        // Convert UuidInterface to our Uuid value object, or generate a new one
        if ($uuid === null) {
            $uuidValueObject = new Uuid();
        } elseif ($uuid instanceof Uuid) {
            $uuidValueObject = $uuid;
        } else {
            $uuidValueObject = Uuid::fromNative($uuid->toString());
        }

        return ArticleEntity::create(
            $processUuid,
            $uuidValueObject,
            $article,
            $eventFactory ?? $this->eventFactory,
            $valueObjectFactory ?? $this->valueObjectFactory,
            $articleSlugGeneratorService ?? $this->articleSlugGeneratorService
        );
    }

    /**
     * Create ArticleEntity instance from value object with Uuid.
     */
    public function makeActualArticleInstance(
        Uuid $uuid,
        Article $article,
        ?EventFactoryInterface $eventFactory = null,
        ?ValueObjectFactoryInterface $valueObjectFactory = null,
        ?ArticleSlugGeneratorServiceInterface $articleSlugGeneratorService = null,
    ): ArticleEntityInterface {
        return ArticleEntity::createActual(
            $uuid,
            $article,
            $eventFactory ?? $this->eventFactory,
            $valueObjectFactory ?? $this->valueObjectFactory,
            $articleSlugGeneratorService ?? $this->articleSlugGeneratorService
        );
    }
}
