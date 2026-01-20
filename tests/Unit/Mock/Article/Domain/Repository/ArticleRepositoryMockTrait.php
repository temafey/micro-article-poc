<?php

declare(strict_types=1);

namespace Tests\Unit\Mock\Article\Domain\Repository;

use Micro\Article\Domain\Entity\ArticleEntity;
use Micro\Article\Domain\Repository\EntityStore\ArticleRepositoryInterface;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Mockery\MockInterface;

/**
 * Mock Factory Trait for ArticleRepositoryInterface.
 *
 * Provides reusable mock configuration for repository operations.
 */
trait ArticleRepositoryMockTrait
{
    protected MockInterface|ArticleRepositoryInterface $articleRepositoryMock;

    /**
     * Create a mock for ArticleRepositoryInterface.
     */
    protected function createArticleRepositoryMock(): MockInterface|ArticleRepositoryInterface
    {
        $this->articleRepositoryMock = \Mockery::mock(ArticleRepositoryInterface::class);

        return $this->articleRepositoryMock;
    }

    /**
     * Configure mock to expect store method call.
     */
    protected function expectArticleRepositoryStore(int $times = 1): void
    {
        $this->articleRepositoryMock
            ->shouldReceive('store')
            ->with(\Mockery::type(ArticleEntity::class))
            ->times($times);
    }

    /**
     * Configure mock to expect get method call (for entity retrieval by UUID).
     *
     * @param ArticleEntity|MockInterface $returnEntity The entity to return
     * @param int $times Number of expected calls
     */
    protected function expectArticleRepositoryGet(ArticleEntity|MockInterface $returnEntity, int $times = 1): void
    {
        $this->articleRepositoryMock
            ->shouldReceive('get')
            ->with(\Mockery::type(Uuid::class))
            ->times($times)
            ->andReturn($returnEntity);
    }

    /**
     * Configure mock to expect load method call.
     */
    protected function expectArticleRepositoryLoad(string $uuid, ?ArticleEntity $returnEntity, int $times = 1): void
    {
        $this->articleRepositoryMock
            ->shouldReceive('load')
            ->with($uuid)
            ->times($times)
            ->andReturn($returnEntity);
    }

    /**
     * Configure mock store to throw exception.
     */
    protected function expectArticleRepositoryStoreThrowsException(\Throwable $exception, int $times = 1): void
    {
        $this->articleRepositoryMock
            ->shouldReceive('store')
            ->times($times)
            ->andThrow($exception);
    }

    /**
     * Configure mock get to throw exception.
     */
    protected function expectArticleRepositoryGetThrowsException(\Throwable $exception, int $times = 1): void
    {
        $this->articleRepositoryMock
            ->shouldReceive('get')
            ->times($times)
            ->andThrow($exception);
    }

    /**
     * Create a mock ArticleEntity with UUID configured.
     *
     * @param string $uuid The UUID string for the entity
     * @return ArticleEntity|MockInterface
     */
    protected function createArticleEntityMock(string $uuid): ArticleEntity|MockInterface
    {
        $articleEntityMock = \Mockery::mock(ArticleEntity::class);
        $uuidMock = \Mockery::mock(Uuid::class);
        $uuidMock->shouldReceive('toString')->andReturn($uuid);
        $uuidMock->shouldReceive('toNative')->andReturn($uuid);
        $articleEntityMock->shouldReceive('getUuid')->andReturn($uuidMock);

        return $articleEntityMock;
    }
}
