<?php

declare(strict_types=1);

namespace Micro\Article\Infrastructure\Repository\Query;

use Micro\Article\Domain\Factory\ReadModelFactoryInterface;
use Micro\Article\Domain\Factory\ValueObjectFactoryInterface;
use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use Micro\Article\Domain\Repository\Query\ArticleRepositoryInterface as QueryRepositoryInterface;
use MicroModule\Base\Domain\Repository\ReadModelStoreInterface;
use MicroModule\Base\Domain\ValueObject\FindCriteria;
use MicroModule\Base\Domain\ValueObject\Uuid;
use MicroModule\Base\Infrastructure\Repository\Exception\NotFoundException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @class ArticleRepository
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ArticleRepository implements QueryRepositoryInterface
{
    public function __construct(
        #[Autowire(service: 'article.infrastructure.repository.storage.read_model.dbal.article')]
        protected ReadModelStoreInterface $readModelStore,
        protected ReadModelFactoryInterface $readModelFactory,
        protected ValueObjectFactoryInterface $valueObjectFactory,
    ) {
    }

    /**
     * Find and return Article Read Model by Uuid.
     */
    public function fetchOne(Uuid $uuid): ?ArticleReadModelInterface
    {
        try {
            $result = $this->readModelStore->findOne($uuid->toString());
        } catch (NotFoundException) {
            return null;
        }

        $uuid = $result[ArticleReadModelInterface::KEY_UUID];
        unset($result[ArticleReadModelInterface::KEY_UUID]);

        return $this->readModelFactory->makeArticleActualInstance(
            $this->valueObjectFactory->makeArticle($result),
            $this->valueObjectFactory->makeUuid($uuid)
        );
    }

    /**
     * Find and return array of Article Read Models by FindCriteria.
     *
     * @return ArticleReadModelInterface[]|null
     */
    public function findByCriteria(FindCriteria $findCriteria): ?array
    {
        try {
            $result = $this->readModelStore->findBy($findCriteria->toNative());
        } catch (NotFoundException) {
            return null;
        }

        $collection = [];

        foreach ($result as $data) {
            $uuid = $data[ArticleReadModelInterface::KEY_UUID];
            unset($data[ArticleReadModelInterface::KEY_UUID]);
            $collection[] = $this->readModelFactory->makeArticleActualInstance(
                $this->valueObjectFactory->makeArticle($data),
                $this->valueObjectFactory->makeUuid($uuid)
            );
        }

        return $collection;
    }

    /**
     * Find and return Article Read Model by Uuid.
     */
    public function findOneBy(FindCriteria $findCriteria): ?ArticleReadModelInterface
    {
        try {
            $result = $this->readModelStore->findOneBy($findCriteria->toNative());
        } catch (NotFoundException) {
            return null;
        }

        $uuid = $result[ArticleReadModelInterface::KEY_UUID];
        unset($result[ArticleReadModelInterface::KEY_UUID]);

        return $this->readModelFactory->makeArticleActualInstance(
            $this->valueObjectFactory->makeArticle($result),
            $this->valueObjectFactory->makeUuid($uuid)
        );
    }
}
