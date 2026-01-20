<?php

declare(strict_types=1);

namespace Micro\Article\Application\Factory;

use Micro\Article\Application\Query\FetchOneArticleQuery;
use Micro\Article\Application\Query\FindByCriteriaArticleQuery;
use Micro\Article\Application\Query\FindOneByArticleQuery;
use MicroModule\Base\Application\Factory\QueryFactoryInterface as BaseQueryFactoryInterface;

/**
 * @interface QueryFactoryInterface
 */
interface QueryFactoryInterface extends BaseQueryFactoryInterface
{
    public const FETCH_ONE_ARTICLE_QUERY = 'FetchOneArticleQuery';

    public const FIND_BY_CRITERIA_ARTICLE_QUERY = 'FindByCriteriaArticleQuery';

    public const FIND_ONE_BY_ARTICLE_QUERY = 'FindOneByArticleQuery';

    /**
     * Create FetchOneArticleQuery Query.
     */
    public function makeFetchOneArticleQuery(string $processUuid, string $uuid): FetchOneArticleQuery;

    /**
     * Create FindByCriteriaArticleQuery Query.
     */
    public function makeFindByCriteriaArticleQuery(string $processUuid, array $findCriteria): FindByCriteriaArticleQuery;

    /**
     * Create FindOneByArticleQuery Query.
     */
    public function makeFindOneByArticleQuery(string $processUuid, array $findCriteria): FindOneByArticleQuery;
}
