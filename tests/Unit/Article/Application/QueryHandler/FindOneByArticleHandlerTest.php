<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\QueryHandler;

use Micro\Article\Application\Query\FindOneByArticleQuery;
use Micro\Article\Application\QueryHandler\FindOneByArticleHandler;
use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use Micro\Article\Domain\Repository\Query\ArticleRepositoryInterface;
use MicroModule\Base\Domain\ValueObject\FindCriteria;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Application\QueryHandler\FindOneByArticleHandlerDataProvider;

/**
 * Unit tests for FindOneByArticleHandler.
 */
#[CoversClass(FindOneByArticleHandler::class)]
final class FindOneByArticleHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private FindOneByArticleHandler $handler;
    private ArticleRepositoryInterface&Mockery\MockInterface $repositoryMock;

    protected function setUp(): void
    {
        $this->repositoryMock = \Mockery::mock(ArticleRepositoryInterface::class);
        $this->handler = new FindOneByArticleHandler($this->repositoryMock);
    }

    #[Test]
    #[DataProviderExternal(FindOneByArticleHandlerDataProvider::class, 'provideSuccessScenarios')]
    public function handleShouldReturnReadModel(array $mockArgs, array $mockTimes): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative($mockArgs['processUuid']);
        $findCriteria = FindCriteria::fromNative($mockArgs['criteria']);
        $query = new FindOneByArticleQuery($processUuid, $findCriteria);

        $readModelMock = \Mockery::mock(ArticleReadModelInterface::class);

        $this->repositoryMock
            ->shouldReceive('findOneBy')
            ->times($mockTimes['findOneBy'])
            ->with(\Mockery::on(fn (FindCriteria $arg) => $arg->toNative() === $mockArgs['criteria']))
            ->andReturn($readModelMock);

        // Act
        $result = $this->handler->handle($query);

        // Assert
        $this->assertInstanceOf(ArticleReadModelInterface::class, $result);
    }

    #[Test]
    #[DataProviderExternal(FindOneByArticleHandlerDataProvider::class, 'provideNotFoundScenarios')]
    public function handleWithNonExistingCriteriaShouldReturnNull(array $mockArgs, array $mockTimes): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative($mockArgs['processUuid']);
        $findCriteria = FindCriteria::fromNative($mockArgs['criteria']);
        $query = new FindOneByArticleQuery($processUuid, $findCriteria);

        $this->repositoryMock
            ->shouldReceive('findOneBy')
            ->times($mockTimes['findOneBy'])
            ->with(\Mockery::on(fn (FindCriteria $arg) => $arg->toNative() === $mockArgs['criteria']))
            ->andReturn(null);

        // Act
        $result = $this->handler->handle($query);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function constructorShouldAcceptDependencies(): void
    {
        // Arrange & Act
        $handler = new FindOneByArticleHandler($this->repositoryMock);

        // Assert
        $this->assertInstanceOf(FindOneByArticleHandler::class, $handler);
    }
}
