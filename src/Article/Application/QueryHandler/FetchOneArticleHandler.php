<?php

declare(strict_types=1);

namespace Micro\Article\Application\QueryHandler;

use Micro\Article\Application\Query\FetchOneArticleQuery;
use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use Micro\Article\Domain\Repository\Query\ArticleRepositoryInterface;
use MicroModule\Base\Application\Query\QueryInterface;
use MicroModule\Base\Application\QueryHandler\QueryHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @class FetchOneArticleHandler
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
    'command' => FetchOneArticleQuery::class,
    'bus' => 'query.article',
])]
class FetchOneArticleHandler implements QueryHandlerInterface
{
    public function __construct(
        protected ArticleRepositoryInterface $articleRepository,
    ) {
    }

    /**
     * Handle FetchOneArticleQuery query.
     */
    public function handle(QueryInterface $fetchOneArticleQuery): ?ArticleReadModelInterface
    {
        return $this->articleRepository->fetchOne($fetchOneArticleQuery->getUuid());
    }
}
