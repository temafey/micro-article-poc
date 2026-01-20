<?php

declare(strict_types=1);

namespace Micro\Article\Domain\Factory;

use Micro\Article\Domain\Entity\ArticleEntityInterface;
use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Domain\ValueObject\Uuid;

/**
 * @interface ReadModelFactoryInterface
 */
interface ReadModelFactoryInterface
{
    /**
     * Create Article read model.
     */
    public function makeArticleActualInstance(Article $article, Uuid $uuid): ArticleReadModelInterface;

    /**
     * Create Article read model from Entity.
     */
    public function makeArticleActualInstanceByEntity(ArticleEntityInterface $articleEntity): ArticleReadModelInterface;
}
