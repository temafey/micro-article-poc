<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\QueryHandler;

use Micro\Article\Application\Query\FindByCriteriaArticleQuery;
use Micro\Article\Application\QueryHandler\FindByCriteriaArticleHandler;
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
use Tests\Unit\DataProvider\Article\Application\QueryHandler\FindByCriteriaArticleHandlerDataProvider;

/**
 * Unit tests for FindByCriteriaArticleHandler.
 */
#[CoversClass(FindByCriteriaArticleHandler::class)]
final class FindByCriteriaArticleHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private FindByCriteriaArticleHandler $handler;
    private ArticleRepositoryInterface&Mockery\MockInterface $repositoryMock;

    protected function setUp(): void
    {
        $this->repositoryMock = \Mockery::mock(ArticleRepositoryInterface::class);
        $this->handler = new FindByCriteriaArticleHandler($this->repositoryMock);
    }

    #[Test]
    #[DataProviderExternal(FindByCriteriaArticleHandlerDataProvider::class, 'provideSuccessScenarios')]
    public function handleShouldReturnArrayOfReadModels(array $mockArgs, array $mockTimes): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative($mockArgs['processUuid']);
        $findCriteria = FindCriteria::fromNative($mockArgs['criteria']);
        $query = new FindByCriteriaArticleQuery($processUuid, $findCriteria);

        $results = [];
        for ($i = 0; $i < $mockArgs['resultCount']; ++$i) {
            $results[] = \Mockery::mock(ArticleReadModelInterface::class);
        }

        $this->repositoryMock
            ->shouldReceive('findByCriteria')
            ->times($mockTimes['findByCriteria'])
            ->with(\Mockery::on(fn (FindCriteria $arg) => $arg->toNative() === $mockArgs['criteria']))
            ->andReturn($results);

        // Act
        $result = $this->handler->handle($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount($mockArgs['resultCount'], $result);
    }

    #[Test]
    #[DataProviderExternal(FindByCriteriaArticleHandlerDataProvider::class, 'provideEmptyResultScenarios')]
    public function handleWithNoMatchesShouldReturnEmptyArray(array $mockArgs, array $mockTimes): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative($mockArgs['processUuid']);
        $findCriteria = FindCriteria::fromNative($mockArgs['criteria']);
        $query = new FindByCriteriaArticleQuery($processUuid, $findCriteria);

        $this->repositoryMock
            ->shouldReceive('findByCriteria')
            ->times($mockTimes['findByCriteria'])
            ->with(\Mockery::on(fn (FindCriteria $arg) => $arg->toNative() === $mockArgs['criteria']))
            ->andReturn([]);

        // Act
        $result = $this->handler->handle($query);

        // Assert
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    #[Test]
    public function constructorShouldAcceptDependencies(): void
    {
        // Arrange & Act
        $handler = new FindByCriteriaArticleHandler($this->repositoryMock);

        // Assert
        $this->assertInstanceOf(FindByCriteriaArticleHandler::class, $handler);
    }
}
