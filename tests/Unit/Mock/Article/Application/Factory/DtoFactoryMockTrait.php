<?php

declare(strict_types=1);

namespace Tests\Unit\Mock\Article\Application\Factory;

use Micro\Article\Application\Dto\ArticleDto;
use Micro\Article\Application\Factory\DtoFactoryInterface;
use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use Mockery\MockInterface;

/**
 * Mock Factory Trait for DtoFactoryInterface.
 */
trait DtoFactoryMockTrait
{
    protected MockInterface|DtoFactoryInterface $dtoFactoryMock;

    /**
     * Create a mock for DtoFactoryInterface.
     */
    protected function createDtoFactoryMock(): MockInterface|DtoFactoryInterface
    {
        $this->dtoFactoryMock = \Mockery::mock(DtoFactoryInterface::class);

        return $this->dtoFactoryMock;
    }

    /**
     * Configure mock to expect makeArticleDtoFromReadModel method call.
     */
    protected function expectDtoFactoryMakeArticleDtoFromReadModel(
        ArticleDto $returnDto,
        int $times = 1,
    ): void {
        $this->dtoFactoryMock
            ->shouldReceive('makeArticleDtoFromReadModel')
            ->with(\Mockery::type(ArticleReadModelInterface::class))
            ->times($times)
            ->andReturn($returnDto);
    }

    /**
     * Configure mock to expect makeArticleDtoFromData method call.
     */
    protected function expectDtoFactoryMakeArticleDtoFromData(
        ArticleDto $returnDto,
        int $times = 1,
    ): void {
        $this->dtoFactoryMock
            ->shouldReceive('makeArticleDtoFromData')
            ->with(\Mockery::type('array'))
            ->times($times)
            ->andReturn($returnDto);
    }

    /**
     * Configure mock to expect makeArticleDtosFromReadModels method call.
     *
     * @param array<ArticleDto> $returnDtos
     */
    protected function expectDtoFactoryMakeArticleDtosFromReadModels(
        array $returnDtos,
        int $times = 1,
    ): void {
        $this->dtoFactoryMock
            ->shouldReceive('makeArticleDtosFromReadModels')
            ->with(\Mockery::type('iterable'))
            ->times($times)
            ->andReturn($returnDtos);
    }

    /**
     * Configure mock to throw exception on makeArticleDtoFromReadModel.
     */
    protected function expectDtoFactoryMakeArticleDtoFromReadModelThrowsException(
        \Throwable $exception,
        int $times = 1,
    ): void {
        $this->dtoFactoryMock
            ->shouldReceive('makeArticleDtoFromReadModel')
            ->times($times)
            ->andThrow($exception);
    }

    /**
     * Configure mock to throw exception on makeArticleDtoFromData.
     */
    protected function expectDtoFactoryMakeArticleDtoFromDataThrowsException(
        \Throwable $exception,
        int $times = 1,
    ): void {
        $this->dtoFactoryMock
            ->shouldReceive('makeArticleDtoFromData')
            ->times($times)
            ->andThrow($exception);
    }
}
