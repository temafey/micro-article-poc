<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Infrastructure\Repository\Query;

use Micro\Article\Domain\Factory\ReadModelFactoryInterface;
use Micro\Article\Domain\Factory\ValueObjectFactoryInterface;
use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use Micro\Article\Domain\ValueObject\Article;
use Micro\Article\Infrastructure\Repository\Query\ArticleRepository;
use MicroModule\Base\Domain\Repository\ReadModelStoreInterface;
use MicroModule\Base\Domain\ValueObject\FindCriteria;
use MicroModule\Base\Domain\ValueObject\Uuid;
use MicroModule\Base\Infrastructure\Repository\Exception\NotFoundException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Infrastructure\Repository\Query\ArticleRepositoryDataProvider;

/**
 * Unit tests for Query/ArticleRepository.
 */
#[CoversClass(ArticleRepository::class)]
final class ArticleRepositoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ArticleRepository $repository;
    private ReadModelStoreInterface&Mockery\MockInterface $readModelStoreMock;
    private ReadModelFactoryInterface&Mockery\MockInterface $readModelFactoryMock;
    private ValueObjectFactoryInterface&Mockery\MockInterface $valueObjectFactoryMock;

    protected function setUp(): void
    {
        $this->readModelStoreMock = \Mockery::mock(ReadModelStoreInterface::class);
        $this->readModelFactoryMock = \Mockery::mock(ReadModelFactoryInterface::class);
        $this->valueObjectFactoryMock = \Mockery::mock(ValueObjectFactoryInterface::class);

        $this->repository = new ArticleRepository(
            $this->readModelStoreMock,
            $this->readModelFactoryMock,
            $this->valueObjectFactoryMock
        );
    }

    #[Test]
    #[DataProviderExternal(ArticleRepositoryDataProvider::class, 'fetchOneSuccessScenarios')]
    public function fetchOneShouldReturnReadModelWhenFound(string $uuid, array $storeResult): void
    {
        // Arrange
        $uuidVo = Uuid::fromNative($uuid);
        $articleMock = \Mockery::mock(Article::class);
        $uuidMock = \Mockery::mock(Uuid::class);
        $readModelMock = \Mockery::mock(ArticleReadModelInterface::class);

        $this->readModelStoreMock
            ->shouldReceive('findOne')
            ->once()
            ->with($uuid)
            ->andReturn($storeResult);

        $expectedData = $storeResult;
        unset($expectedData[ArticleReadModelInterface::KEY_UUID]);

        $this->valueObjectFactoryMock
            ->shouldReceive('makeArticle')
            ->once()
            ->with($expectedData)
            ->andReturn($articleMock);

        $this->valueObjectFactoryMock
            ->shouldReceive('makeUuid')
            ->once()
            ->with($uuid)
            ->andReturn($uuidMock);

        $this->readModelFactoryMock
            ->shouldReceive('makeArticleActualInstance')
            ->once()
            ->with($articleMock, $uuidMock)
            ->andReturn($readModelMock);

        // Act
        $result = $this->repository->fetchOne($uuidVo);

        // Assert
        $this->assertInstanceOf(ArticleReadModelInterface::class, $result);
    }

    #[Test]
    public function fetchOneShouldReturnNullWhenNotFound(): void
    {
        // Arrange
        $uuid = Uuid::fromNative('550e8400-e29b-41d4-a716-446655440000');

        $this->readModelStoreMock
            ->shouldReceive('findOne')
            ->once()
            ->with('550e8400-e29b-41d4-a716-446655440000')
            ->andThrow(new NotFoundException('Not found'));

        // Act
        $result = $this->repository->fetchOne($uuid);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    #[DataProviderExternal(ArticleRepositoryDataProvider::class, 'findByCriteriaSuccessScenarios')]
    public function findByCriteriaShouldReturnArrayOfReadModels(
        array $criteria,
        array $storeResult,
        int $expectedCount,
    ): void {
        // Arrange
        $findCriteria = FindCriteria::fromNative($criteria);

        $this->readModelStoreMock
            ->shouldReceive('findBy')
            ->once()
            ->with($criteria)
            ->andReturn($storeResult);

        foreach ($storeResult as $index => $data) {
            $articleMock = \Mockery::mock(Article::class);
            $uuidMock = \Mockery::mock(Uuid::class);
            $readModelMock = \Mockery::mock(ArticleReadModelInterface::class);

            $expectedData = $data;
            $expectedUuid = $data[ArticleReadModelInterface::KEY_UUID];
            unset($expectedData[ArticleReadModelInterface::KEY_UUID]);

            $this->valueObjectFactoryMock
                ->shouldReceive('makeArticle')
                ->once()
                ->with($expectedData)
                ->andReturn($articleMock);

            $this->valueObjectFactoryMock
                ->shouldReceive('makeUuid')
                ->once()
                ->with($expectedUuid)
                ->andReturn($uuidMock);

            $this->readModelFactoryMock
                ->shouldReceive('makeArticleActualInstance')
                ->once()
                ->with($articleMock, $uuidMock)
                ->andReturn($readModelMock);
        }

        // Act
        $result = $this->repository->findByCriteria($findCriteria);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount($expectedCount, $result);
    }

    #[Test]
    public function findByCriteriaShouldReturnNullWhenNotFound(): void
    {
        // Arrange
        $criteria = [
            'status' => 'nonexistent',
        ];
        $findCriteria = FindCriteria::fromNative($criteria);

        $this->readModelStoreMock
            ->shouldReceive('findBy')
            ->once()
            ->with($criteria)
            ->andThrow(new NotFoundException('Not found'));

        // Act
        $result = $this->repository->findByCriteria($findCriteria);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    #[DataProviderExternal(ArticleRepositoryDataProvider::class, 'findOneBySuccessScenarios')]
    public function findOneByShouldReturnReadModelWhenFound(array $criteria, array $storeResult): void
    {
        // Arrange
        $findCriteria = FindCriteria::fromNative($criteria);
        $articleMock = \Mockery::mock(Article::class);
        $uuidMock = \Mockery::mock(Uuid::class);
        $readModelMock = \Mockery::mock(ArticleReadModelInterface::class);

        $this->readModelStoreMock
            ->shouldReceive('findOneBy')
            ->once()
            ->with($criteria)
            ->andReturn($storeResult);

        $expectedData = $storeResult;
        $expectedUuid = $storeResult[ArticleReadModelInterface::KEY_UUID];
        unset($expectedData[ArticleReadModelInterface::KEY_UUID]);

        $this->valueObjectFactoryMock
            ->shouldReceive('makeArticle')
            ->once()
            ->with($expectedData)
            ->andReturn($articleMock);

        $this->valueObjectFactoryMock
            ->shouldReceive('makeUuid')
            ->once()
            ->with($expectedUuid)
            ->andReturn($uuidMock);

        $this->readModelFactoryMock
            ->shouldReceive('makeArticleActualInstance')
            ->once()
            ->with($articleMock, $uuidMock)
            ->andReturn($readModelMock);

        // Act
        $result = $this->repository->findOneBy($findCriteria);

        // Assert
        $this->assertInstanceOf(ArticleReadModelInterface::class, $result);
    }

    #[Test]
    public function findOneByShouldReturnNullWhenNotFound(): void
    {
        // Arrange
        $criteria = [
            'slug' => 'nonexistent-slug',
        ];
        $findCriteria = FindCriteria::fromNative($criteria);

        $this->readModelStoreMock
            ->shouldReceive('findOneBy')
            ->once()
            ->with($criteria)
            ->andThrow(new NotFoundException('Not found'));

        // Act
        $result = $this->repository->findOneBy($findCriteria);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function constructorShouldAcceptDependencies(): void
    {
        // Arrange & Act
        $repository = new ArticleRepository(
            $this->readModelStoreMock,
            $this->readModelFactoryMock,
            $this->valueObjectFactoryMock
        );

        // Assert
        $this->assertInstanceOf(ArticleRepository::class, $repository);
    }
}
