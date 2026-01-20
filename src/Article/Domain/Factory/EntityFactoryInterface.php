<?php

declare(strict_types=1);

namespace Micro\Article\Domain\Factory;

use Micro\Article\Domain\Entity\ArticleEntityInterface;
use Micro\Article\Domain\Service\ArticleSlugGeneratorServiceInterface;
use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @interface EntityFactoryInterface
 */
interface EntityFactoryInterface
{
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
    ): ArticleEntityInterface;

    /**
     * Create ArticleEntity instance from value object with Uuid.
     */
    public function makeActualArticleInstance(
        Uuid $uuid,
        Article $article,
        ?EventFactoryInterface $eventFactory = null,
        ?ValueObjectFactoryInterface $valueObjectFactory = null,
        ?ArticleSlugGeneratorServiceInterface $articleSlugGeneratorService = null,
    ): ArticleEntityInterface;
}
