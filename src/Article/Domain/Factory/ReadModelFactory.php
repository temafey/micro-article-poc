<?php

declare(strict_types=1);

namespace Micro\Article\Domain\Factory;

use Micro\Article\Domain\Entity\ArticleEntityInterface;
use Micro\Article\Domain\ReadModel\ArticleReadModel;
use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Domain\ValueObject\Uuid;

/**
 * @class ReadModelFactory
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ReadModelFactory implements ReadModelFactoryInterface
{
    /**
     * Create Article read model.
     */
    public function makeArticleActualInstance(Article $article, Uuid $uuid): ArticleReadModelInterface
    {
        return ArticleReadModel::createByValueObject($article, $uuid);
    }

    /**
     * Create Article read model from Entity.
     */
    public function makeArticleActualInstanceByEntity(ArticleEntityInterface $articleEntity): ArticleReadModelInterface
    {
        return ArticleReadModel::createByEntity($articleEntity);
    }
}
