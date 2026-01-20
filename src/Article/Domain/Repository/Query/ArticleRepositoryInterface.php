<?php

declare(strict_types=1);

namespace Micro\Article\Domain\Repository\Query;

use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use MicroModule\Base\Domain\ValueObject\FindCriteria;
use MicroModule\Base\Domain\ValueObject\Uuid;

/**
 * @interface ArticleRepositoryInterface
 */
interface ArticleRepositoryInterface
{
    public function fetchOne(Uuid $uuid): ?ArticleReadModelInterface;

    public function findByCriteria(FindCriteria $findCriteria): ?array;

    public function findOneBy(FindCriteria $findCriteria): ?ArticleReadModelInterface;
}
