<?php

declare(strict_types=1);

namespace Micro\Article\Infrastructure\Repository\ReadModel;

use Micro\Article\Domain\Factory\ReadModelFactoryInterface;
use Micro\Article\Domain\Factory\ValueObjectFactoryInterface;
use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use Micro\Article\Domain\Repository\ReadModel\ArticleRepositoryInterface;
use MicroModule\Base\Domain\Exception\ReadModelException;
use MicroModule\Base\Domain\Repository\ReadModelStoreInterface;
use MicroModule\Base\Domain\ValueObject\Uuid;
use MicroModule\Base\Infrastructure\Repository\Exception\DBALEventStoreException;
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
class ArticleRepository implements ArticleRepositoryInterface
{
    public function __construct(
        #[Autowire(service: 'article.infrastructure.repository.storage.read_model.dbal.article')]
        protected ReadModelStoreInterface $readModelStore,
        protected ReadModelFactoryInterface $readModelFactory,
        protected ValueObjectFactoryInterface $valueObjectFactory,
    ) {
    }

    /**
     * Add ArticleReadModel ReadModel in Storage.
     */
    public function add(ArticleReadModelInterface $articleReadModel): void
    {
        try {
            $this->readModelStore->insertOne($articleReadModel);
        } catch (DBALEventStoreException $dbalEventStoreException) {
            throw new ReadModelException(
                'ArticleReadModelInterface $articleReadModel was not add in read model.',
                $dbalEventStoreException->getCode(),
                $dbalEventStoreException
            );
        }
    }

    /**
     * Update ArticleReadModel ReadModel in Storage.
     */
    public function update(ArticleReadModelInterface $articleReadModel): void
    {
        try {
            // Check if the read model exists before updating
            $this->readModelStore->findOne($articleReadModel->getUuid()->toNative());
            $this->readModelStore->updateOne($articleReadModel);
        } catch (NotFoundException $notFoundException) {
            throw new ReadModelException(
                'ArticleReadModelInterface $articleReadModel was not found in read model.',
                $notFoundException->getCode(),
                $notFoundException
            );
        } catch (DBALEventStoreException $dbalEventStoreException) {
            throw new ReadModelException(
                'ArticleReadModelInterface $articleReadModel was not update in read model.',
                $dbalEventStoreException->getCode(),
                $dbalEventStoreException
            );
        }
    }

    /**
     * Delete ArticleReadModel ReadModel in Storage.
     */
    public function delete(ArticleReadModelInterface $articleReadModel): void
    {
        try {
            $this->readModelStore->deleteOne($articleReadModel);
        } catch (DBALEventStoreException $dbalEventStoreException) {
            throw new ReadModelException(
                'ArticleReadModelInterface $articleReadModel was not delete in read model.',
                $dbalEventStoreException->getCode(),
                $dbalEventStoreException
            );
        }
    }

    /**
     * Find and return Article Read Model by Uuid.
     */
    public function get(Uuid $uuid): ?ArticleReadModelInterface
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
}
