<?php

declare(strict_types=1);

namespace Tests\Unit\Mock\Article\Domain\Factory;

use Micro\Article\Domain\Entity\ArticleEntityInterface;
use Micro\Article\Domain\Factory\ReadModelFactoryInterface;
use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Mockery\MockInterface;

/**
 * Mock Factory Trait for ReadModelFactoryInterface.
 */
trait ReadModelFactoryMockTrait
{
    protected MockInterface|ReadModelFactoryInterface $readModelFactoryMock;

    /**
     * Create a mock for ReadModelFactoryInterface.
     */
    protected function createReadModelFactoryMock(): MockInterface|ReadModelFactoryInterface
    {
        $this->readModelFactoryMock = \Mockery::mock(ReadModelFactoryInterface::class);

        return $this->readModelFactoryMock;
    }

    /**
     * Configure mock to expect makeArticleActualInstance method call.
     */
    protected function expectReadModelFactoryMakeArticleActualInstance(
        ArticleReadModelInterface $returnModel,
        int $times = 1,
    ): void {
        $this->readModelFactoryMock
            ->shouldReceive('makeArticleActualInstance')
            ->with(\Mockery::type(Article::class), \Mockery::type(Uuid::class))
            ->times($times)
            ->andReturn($returnModel);
    }

    /**
     * Configure mock to expect makeArticleActualInstanceByEntity method call.
     */
    protected function expectReadModelFactoryMakeArticleActualInstanceByEntity(
        ArticleReadModelInterface $returnModel,
        int $times = 1,
    ): void {
        $this->readModelFactoryMock
            ->shouldReceive('makeArticleActualInstanceByEntity')
            ->with(\Mockery::type(ArticleEntityInterface::class))
            ->times($times)
            ->andReturn($returnModel);
    }

    /**
     * Configure mock to throw exception on makeArticleActualInstance.
     */
    protected function expectReadModelFactoryMakeArticleActualInstanceThrowsException(
        \Throwable $exception,
        int $times = 1,
    ): void {
        $this->readModelFactoryMock
            ->shouldReceive('makeArticleActualInstance')
            ->times($times)
            ->andThrow($exception);
    }

    /**
     * Configure mock to throw exception on makeArticleActualInstanceByEntity.
     */
    protected function expectReadModelFactoryMakeArticleActualInstanceByEntityThrowsException(
        \Throwable $exception,
        int $times = 1,
    ): void {
        $this->readModelFactoryMock
            ->shouldReceive('makeArticleActualInstanceByEntity')
            ->times($times)
            ->andThrow($exception);
    }
}
