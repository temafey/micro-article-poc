<?php

declare(strict_types=1);

namespace Micro\Article\Application\QueryHandler;

use Micro\Article\Application\Query\FindOneByArticleQuery;
use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use Micro\Article\Domain\Repository\Query\ArticleRepositoryInterface;
use MicroModule\Base\Application\Query\QueryInterface;
use MicroModule\Base\Application\QueryHandler\QueryHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @class FindOneByArticleHandler
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
    'command' => FindOneByArticleQuery::class,
    'bus' => 'query.article',
])]
class FindOneByArticleHandler implements QueryHandlerInterface
{
    public function __construct(
        protected ArticleRepositoryInterface $articleRepository,
    ) {
    }

    /**
     * Handle FindOneByArticleQuery query.
     */
    public function handle(QueryInterface $findOneByArticleQuery): ?ArticleReadModelInterface
    {
        return $this->articleRepository->findOneBy($findOneByArticleQuery->getFindCriteria());
    }
}
