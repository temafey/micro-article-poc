<?php

declare(strict_types=1);

namespace Tests\Unit\Mock\Article\Domain\Repository;

use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use Micro\Article\Domain\Repository\Query\ArticleRepositoryInterface;
use MicroModule\Base\Domain\ValueObject\FindCriteria;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Mockery\MockInterface;

/**
 * Mock Factory Trait for Query ArticleRepositoryInterface.
 */
trait QueryArticleRepositoryMockTrait
{
    protected MockInterface|ArticleRepositoryInterface $queryArticleRepositoryMock;

    /**
     * Create a mock for Query ArticleRepositoryInterface.
     */
    protected function createQueryArticleRepositoryMock(): MockInterface|ArticleRepositoryInterface
    {
        $this->queryArticleRepositoryMock = \Mockery::mock(ArticleRepositoryInterface::class);

        return $this->queryArticleRepositoryMock;
    }

    /**
     * Configure mock to expect fetchOne method call.
     */
    protected function expectQueryRepositoryFetchOne(
        string $uuid,
        ?ArticleReadModelInterface $returnModel,
        int $times = 1,
    ): void {
        $this->queryArticleRepositoryMock
            ->shouldReceive('fetchOne')
            ->with(\Mockery::on(fn (Uuid $arg) => $arg->toNative() === $uuid))
            ->times($times)
            ->andReturn($returnModel);
    }

    /**
     * Configure mock to expect findByCriteria method call.
     */
    protected function expectQueryRepositoryFindByCriteria(
        ?array $returnModels,
        int $times = 1,
    ): void {
        $this->queryArticleRepositoryMock
            ->shouldReceive('findByCriteria')
            ->with(\Mockery::type(FindCriteria::class))
            ->times($times)
            ->andReturn($returnModels);
    }

    /**
     * Configure mock to expect findOneBy method call.
     */
    protected function expectQueryRepositoryFindOneBy(
        ?ArticleReadModelInterface $returnModel,
        int $times = 1,
    ): void {
        $this->queryArticleRepositoryMock
            ->shouldReceive('findOneBy')
            ->with(\Mockery::type(FindCriteria::class))
            ->times($times)
            ->andReturn($returnModel);
    }

    /**
     * Configure mock fetchOne to throw exception.
     */
    protected function expectQueryRepositoryFetchOneThrowsException(\Throwable $exception, int $times = 1): void
    {
        $this->queryArticleRepositoryMock
            ->shouldReceive('fetchOne')
            ->times($times)
            ->andThrow($exception);
    }

    /**
     * Configure mock findByCriteria to throw exception.
     */
    protected function expectQueryRepositoryFindByCriteriaThrowsException(\Throwable $exception, int $times = 1): void
    {
        $this->queryArticleRepositoryMock
            ->shouldReceive('findByCriteria')
            ->times($times)
            ->andThrow($exception);
    }

    /**
     * Configure mock findOneBy to throw exception.
     */
    protected function expectQueryRepositoryFindOneByThrowsException(\Throwable $exception, int $times = 1): void
    {
        $this->queryArticleRepositoryMock
            ->shouldReceive('findOneBy')
            ->times($times)
            ->andThrow($exception);
    }
}
