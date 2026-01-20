<?php

declare(strict_types=1);

namespace Micro\Article\Application\QueryHandler;

use Micro\Article\Application\Query\FindByCriteriaArticleQuery;
use Micro\Article\Domain\Repository\Query\ArticleRepositoryInterface;
use MicroModule\Base\Application\Query\QueryInterface;
use MicroModule\Base\Application\QueryHandler\QueryHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @class FindByCriteriaArticleHandler
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
#[AutoconfigureTag(name: 'tactician.handler', attributes: [
    'command' => FindByCriteriaArticleQuery::class,
    'bus' => 'query.article',
])]
class FindByCriteriaArticleHandler implements QueryHandlerInterface
{
    public function __construct(
        protected ArticleRepositoryInterface $articleRepository,
    ) {
    }

    /**
     * Handle FindByCriteriaArticleQuery query.
     */
    public function handle(QueryInterface $findByCriteriaArticleQuery): ?array
    {
        return $this->articleRepository->findByCriteria($findByCriteriaArticleQuery->getFindCriteria());
    }
}
