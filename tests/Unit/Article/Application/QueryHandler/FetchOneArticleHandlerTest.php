<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\QueryHandler;

use Micro\Article\Application\Query\FetchOneArticleQuery;
use Micro\Article\Application\QueryHandler\FetchOneArticleHandler;
use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use Micro\Article\Domain\Repository\Query\ArticleRepositoryInterface;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Application\QueryHandler\FetchOneArticleHandlerDataProvider;

/**
 * Unit tests for FetchOneArticleHandler.
 */
#[CoversClass(FetchOneArticleHandler::class)]
final class FetchOneArticleHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private FetchOneArticleHandler $handler;
    private ArticleRepositoryInterface&Mockery\MockInterface $repositoryMock;

    protected function setUp(): void
    {
        $this->repositoryMock = \Mockery::mock(ArticleRepositoryInterface::class);
        $this->handler = new FetchOneArticleHandler($this->repositoryMock);
    }

    #[Test]
    #[DataProviderExternal(FetchOneArticleHandlerDataProvider::class, 'provideSuccessScenarios')]
    public function handleShouldReturnReadModel(array $mockArgs, array $mockTimes): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative($mockArgs['processUuid']);
        $uuid = Uuid::fromNative($mockArgs['uuid']);
        $query = new FetchOneArticleQuery($processUuid, $uuid);

        $readModelMock = \Mockery::mock(ArticleReadModelInterface::class);

        $this->repositoryMock
            ->shouldReceive('fetchOne')
            ->times($mockTimes['fetchOne'])
            ->with(\Mockery::on(fn (Uuid $arg) => $arg->toNative() === $mockArgs['uuid']))
            ->andReturn($readModelMock);

        // Act
        $result = $this->handler->handle($query);

        // Assert
        $this->assertInstanceOf(ArticleReadModelInterface::class, $result);
    }

    #[Test]
    #[DataProviderExternal(FetchOneArticleHandlerDataProvider::class, 'provideNotFoundScenarios')]
    public function handleWithNonExistingUuidShouldReturnNull(array $mockArgs, array $mockTimes): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative($mockArgs['processUuid']);
        $uuid = Uuid::fromNative($mockArgs['uuid']);
        $query = new FetchOneArticleQuery($processUuid, $uuid);

        $this->repositoryMock
            ->shouldReceive('fetchOne')
            ->times($mockTimes['fetchOne'])
            ->with(\Mockery::on(fn (Uuid $arg) => $arg->toNative() === $mockArgs['uuid']))
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
        $handler = new FetchOneArticleHandler($this->repositoryMock);

        // Assert
        $this->assertInstanceOf(FetchOneArticleHandler::class, $handler);
    }
}
