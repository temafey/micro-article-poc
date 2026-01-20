<?php

declare(strict_types=1);

namespace Micro\Article\Domain\Repository\ReadModel;

use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use MicroModule\Base\Domain\ValueObject\Uuid;

/**
 * @interface ArticleRepositoryInterface
 */
interface ArticleRepositoryInterface
{
    public function add(ArticleReadModelInterface $articleReadModel): void;

    public function update(ArticleReadModelInterface $articleReadModel): void;

    public function delete(ArticleReadModelInterface $articleReadModel): void;

    public function get(Uuid $uuid): ?ArticleReadModelInterface;
}
