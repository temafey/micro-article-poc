<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Infrastructure\Repository\Query;

use Micro\Article\Domain\Factory\ReadModelFactoryInterface;
use Micro\Article\Domain\Factory\ValueObjectFactoryInterface;
use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use Micro\Article\Domain\Repository\Query\ArticleRepositoryInterface;
use Micro\Article\Domain\ValueObject\Article;
use Micro\Article\Infrastructure\Repository\Query\CachedArticleQueryRepository;
use MicroModule\Base\Domain\ValueObject\FindCriteria;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Tests\Unit\DataProvider\Article\Infrastructure\Repository\Query\CachedArticleQueryRepositoryDataProvider;

/**
 * Unit tests for CachedArticleQueryRepository.
 *
 * Tests caching behavior with stampede prevention for the query repository decorator.
 *
 * IMPORTANT: The production code caches ARRAYS (not objects) to avoid UUID serialization issues.
 * When cache returns array, factories are used to reconstruct ReadModel.
 */
#[CoversClass(CachedArticleQueryRepository::class)]
final class CachedArticleQueryRepositoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private CachedArticleQueryRepository $repository;
    private ArticleRepositoryInterface&Mockery\MockInterface $innerRepositoryMock;
    private TagAwareCacheInterface&Mockery\MockInterface $cacheMock;
    private LoggerInterface&Mockery\MockInterface $loggerMock;
    private ReadModelFactoryInterface&Mockery\MockInterface $readModelFactoryMock;
    private ValueObjectFactoryInterface&Mockery\MockInterface $valueObjectFactoryMock;

    protected function setUp(): void
    {
        $this->innerRepositoryMock = \Mockery::mock(ArticleRepositoryInterface::class);
        $this->cacheMock = \Mockery::mock(TagAwareCacheInterface::class);
        $this->loggerMock = \Mockery::mock(LoggerInterface::class);
        $this->readModelFactoryMock = \Mockery::mock(ReadModelFactoryInterface::class);
        $this->valueObjectFactoryMock = \Mockery::mock(ValueObjectFactoryInterface::class);

        $this->repository = new CachedArticleQueryRepository(
            $this->innerRepositoryMock,
            $this->cacheMock,
            $this->loggerMock,
            $this->readModelFactoryMock,
            $this->valueObjectFactoryMock
        );
    }

    #[Test]
    #[DataProviderExternal(CachedArticleQueryRepositoryDataProvider::class, 'fetchOneCacheScenarios')]
    public function fetchOneShouldUseCacheWithStampedePrevention(
        string $uuid,
        string $expectedCacheKey,
        array $expectedTags,
        ?array $cachedValue,
    ): void {
        // Arrange
        $uuidVo = Uuid::fromNative($uuid);
        $readModelMock = $cachedValue !== null
            ? \Mockery::mock(ArticleReadModelInterface::class)
            : null;

        // Cache returns array data (not objects) - this is the key difference
        $this->cacheMock
            ->shouldReceive('get')
            ->once()
            ->with($expectedCacheKey, \Mockery::type('callable'), \Mockery::on(fn ($beta) => $beta === 1.0))
            ->andReturn($cachedValue);

        // When cached data exists, factories reconstruct the ReadModel
        if ($cachedValue !== null) {
            $articleVoMock = \Mockery::mock(Article::class);
            $uuidVoMock = \Mockery::mock(Uuid::class);

            // Remove uuid from data for makeArticle call
            $dataWithoutUuid = $cachedValue;
            unset($dataWithoutUuid[ArticleReadModelInterface::KEY_UUID]);

            $this->valueObjectFactoryMock
                ->shouldReceive('makeArticle')
                ->once()
                ->with($dataWithoutUuid)
                ->andReturn($articleVoMock);

            $this->valueObjectFactoryMock
                ->shouldReceive('makeUuid')
                ->once()
                ->with($uuid)
                ->andReturn($uuidVoMock);

            $this->readModelFactoryMock
                ->shouldReceive('makeArticleActualInstance')
                ->once()
                ->with($articleVoMock, $uuidVoMock)
                ->andReturn($readModelMock);
        }

        // Act
        $result = $this->repository->fetchOne($uuidVo);

        // Assert
        if ($cachedValue !== null) {
            $this->assertSame($readModelMock, $result);
        } else {
            $this->assertNull($result);
        }
    }

    #[Test]
    public function fetchOneShouldReturnNullWhenNotFoundAndCacheMiss(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuidVo = Uuid::fromNative($uuid);

        $this->cacheMock
            ->shouldReceive('get')
            ->once()
            ->andReturnUsing(function ($key, $callback) {
                $itemMock = \Mockery::mock(ItemInterface::class);
                $itemMock->shouldReceive('expiresAfter')
                    ->with(900);
                $itemMock->shouldReceive('tag')
                    ->with(['article', 'article.550e8400-e29b-41d4-a716-446655440000']);

                // Simulate calling the callback which returns null (from inner->fetchOne)
                return $callback($itemMock);
            });

        $this->innerRepositoryMock
            ->shouldReceive('fetchOne')
            ->once()
            ->with($uuidVo)
            ->andReturn(null);

        $this->loggerMock
            ->shouldReceive('debug')
            ->once()
            ->with('Cache MISS', \Mockery::type('array'));

        // Act
        $result = $this->repository->fetchOne($uuidVo);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function fetchOneShouldDelegateToInnerOnCacheMiss(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuidVo = Uuid::fromNative($uuid);

        // Create ReadModel mock that can return array for caching
        $readModelArray = [
            ArticleReadModelInterface::KEY_UUID => $uuid,
            'title' => 'Test Title',
            'slug' => 'test-title',
            'short_description' => 'Short description',
            'description' => 'Full description with at least fifty characters for validation testing.',
            'body' => '<p>Body content</p>',
            'status' => 'published',
            'active' => true,
            'deleted' => false,
        ];

        $innerReadModelMock = \Mockery::mock(ArticleReadModelInterface::class);
        $innerReadModelMock
            ->shouldReceive('toArray')
            ->once()
            ->andReturn($readModelArray);

        $this->cacheMock
            ->shouldReceive('get')
            ->once()
            ->andReturnUsing(function ($key, $callback) {
                $itemMock = \Mockery::mock(ItemInterface::class);
                $itemMock->shouldReceive('expiresAfter')
                    ->with(900);
                $itemMock->shouldReceive('tag')
                    ->with(['article', 'article.550e8400-e29b-41d4-a716-446655440000']);

                return $callback($itemMock);
            });

        $this->innerRepositoryMock
            ->shouldReceive('fetchOne')
            ->once()
            ->with($uuidVo)
            ->andReturn($innerReadModelMock);

        $this->loggerMock
            ->shouldReceive('debug')
            ->once()
            ->with('Cache MISS', \Mockery::type('array'));

        // Setup factory mocks for reconstruction from cached array
        $articleVoMock = \Mockery::mock(Article::class);
        $uuidVoMock = \Mockery::mock(Uuid::class);
        $reconstructedReadModelMock = \Mockery::mock(ArticleReadModelInterface::class);

        // Remove uuid from data for makeArticle call
        $dataWithoutUuid = $readModelArray;
        unset($dataWithoutUuid[ArticleReadModelInterface::KEY_UUID]);

        $this->valueObjectFactoryMock
            ->shouldReceive('makeArticle')
            ->once()
            ->with($dataWithoutUuid)
            ->andReturn($articleVoMock);

        $this->valueObjectFactoryMock
            ->shouldReceive('makeUuid')
            ->once()
            ->with($uuid)
            ->andReturn($uuidVoMock);

        $this->readModelFactoryMock
            ->shouldReceive('makeArticleActualInstance')
            ->once()
            ->with($articleVoMock, $uuidVoMock)
            ->andReturn($reconstructedReadModelMock);

        // Act
        $result = $this->repository->fetchOne($uuidVo);

        // Assert
        $this->assertInstanceOf(ArticleReadModelInterface::class, $result);
        $this->assertSame($reconstructedReadModelMock, $result);
    }

    #[Test]
    #[DataProviderExternal(CachedArticleQueryRepositoryDataProvider::class, 'findByCriteriaCacheScenarios')]
    public function findByCriteriaShouldUseCacheWithListTags(
        array $criteria,
        string $expectedCacheKeyPrefix,
        array $expectedTags,
        ?array $cachedValue,
    ): void {
        // Arrange
        $findCriteria = FindCriteria::fromNative($criteria);

        $this->cacheMock
            ->shouldReceive('get')
            ->once()
            ->with(
                \Mockery::on(fn ($key) => str_starts_with($key, $expectedCacheKeyPrefix)),
                \Mockery::type('callable'),
                \Mockery::on(fn ($beta) => $beta === 1.0)
            )
            ->andReturn($cachedValue);

        // When cached data exists, factories reconstruct each ReadModel
        if ($cachedValue !== null) {
            foreach ($cachedValue as $itemData) {
                $articleVoMock = \Mockery::mock(Article::class);
                $uuidVoMock = \Mockery::mock(Uuid::class);
                $readModelMock = \Mockery::mock(ArticleReadModelInterface::class);

                $dataWithoutUuid = $itemData;
                $uuid = $itemData[ArticleReadModelInterface::KEY_UUID];
                unset($dataWithoutUuid[ArticleReadModelInterface::KEY_UUID]);

                $this->valueObjectFactoryMock
                    ->shouldReceive('makeArticle')
                    ->once()
                    ->with($dataWithoutUuid)
                    ->andReturn($articleVoMock);

                $this->valueObjectFactoryMock
                    ->shouldReceive('makeUuid')
                    ->once()
                    ->with($uuid)
                    ->andReturn($uuidVoMock);

                $this->readModelFactoryMock
                    ->shouldReceive('makeArticleActualInstance')
                    ->once()
                    ->with($articleVoMock, $uuidVoMock)
                    ->andReturn($readModelMock);
            }
        }

        // Act
        $result = $this->repository->findByCriteria($findCriteria);

        // Assert
        if ($cachedValue === null) {
            $this->assertNull($result);
        } else {
            $this->assertIsArray($result);
            $this->assertCount(count($cachedValue), $result);
        }
    }

    #[Test]
    public function findByCriteriaShouldDelegateToInnerOnCacheMiss(): void
    {
        // Arrange
        $criteria = [
            'status' => 'published',
        ];
        $findCriteria = FindCriteria::fromNative($criteria);

        // Mock ReadModels with toArray() for caching
        $readModelArray1 = [
            ArticleReadModelInterface::KEY_UUID => '550e8400-e29b-41d4-a716-446655440001',
            'title' => 'Article 1',
            'slug' => 'article-1',
            'short_description' => 'Short 1',
            'description' => 'Full description with at least fifty characters for validation testing.',
            'body' => '<p>Body 1</p>',
            'status' => 'published',
            'active' => true,
            'deleted' => false,
        ];
        $readModelArray2 = [
            ArticleReadModelInterface::KEY_UUID => '550e8400-e29b-41d4-a716-446655440002',
            'title' => 'Article 2',
            'slug' => 'article-2',
            'short_description' => 'Short 2',
            'description' => 'Full description with at least fifty characters for validation testing.',
            'body' => '<p>Body 2</p>',
            'status' => 'published',
            'active' => true,
            'deleted' => false,
        ];

        $innerReadModel1 = \Mockery::mock(ArticleReadModelInterface::class);
        $innerReadModel1->shouldReceive('toArray')->once()->andReturn($readModelArray1);

        $innerReadModel2 = \Mockery::mock(ArticleReadModelInterface::class);
        $innerReadModel2->shouldReceive('toArray')->once()->andReturn($readModelArray2);

        $expectedResults = [$innerReadModel1, $innerReadModel2];

        $this->cacheMock
            ->shouldReceive('get')
            ->once()
            ->andReturnUsing(function ($key, $callback) {
                $itemMock = \Mockery::mock(ItemInterface::class);
                $itemMock->shouldReceive('expiresAfter')
                    ->with(300);
                $itemMock->shouldReceive('tag')
                    ->with(['article', 'article.list']);

                return $callback($itemMock);
            });

        $this->innerRepositoryMock
            ->shouldReceive('findByCriteria')
            ->once()
            ->with($findCriteria)
            ->andReturn($expectedResults);

        $this->loggerMock
            ->shouldReceive('debug')
            ->once()
            ->with('Cache MISS', \Mockery::type('array'));

        // Setup factory mocks for reconstruction (2 items)
        foreach ([$readModelArray1, $readModelArray2] as $readModelArray) {
            $articleVoMock = \Mockery::mock(Article::class);
            $uuidVoMock = \Mockery::mock(Uuid::class);
            $reconstructedMock = \Mockery::mock(ArticleReadModelInterface::class);

            $dataWithoutUuid = $readModelArray;
            $uuid = $readModelArray[ArticleReadModelInterface::KEY_UUID];
            unset($dataWithoutUuid[ArticleReadModelInterface::KEY_UUID]);

            $this->valueObjectFactoryMock
                ->shouldReceive('makeArticle')
                ->once()
                ->with($dataWithoutUuid)
                ->andReturn($articleVoMock);

            $this->valueObjectFactoryMock
                ->shouldReceive('makeUuid')
                ->once()
                ->with($uuid)
                ->andReturn($uuidVoMock);

            $this->readModelFactoryMock
                ->shouldReceive('makeArticleActualInstance')
                ->once()
                ->with($articleVoMock, $uuidVoMock)
                ->andReturn($reconstructedMock);
        }

        // Act
        $result = $this->repository->findByCriteria($findCriteria);

        // Assert
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    #[Test]
    #[DataProviderExternal(CachedArticleQueryRepositoryDataProvider::class, 'findOneByCacheScenarios')]
    public function findOneByShouldUseCacheWithProperTags(
        array $criteria,
        string $expectedCacheKeyPrefix,
        array $expectedTags,
        ?array $cachedValue,
    ): void {
        // Arrange
        $findCriteria = FindCriteria::fromNative($criteria);
        $readModelMock = $cachedValue !== null
            ? \Mockery::mock(ArticleReadModelInterface::class)
            : null;

        // Cache returns array data (not objects)
        $this->cacheMock
            ->shouldReceive('get')
            ->once()
            ->with(
                \Mockery::on(fn ($key) => str_starts_with($key, $expectedCacheKeyPrefix)),
                \Mockery::type('callable'),
                \Mockery::on(fn ($beta) => $beta === 1.0)
            )
            ->andReturn($cachedValue);

        // When cached data exists, factories reconstruct the ReadModel
        if ($cachedValue !== null) {
            $articleVoMock = \Mockery::mock(Article::class);
            $uuidVoMock = \Mockery::mock(Uuid::class);

            $dataWithoutUuid = $cachedValue;
            $uuid = $cachedValue[ArticleReadModelInterface::KEY_UUID];
            unset($dataWithoutUuid[ArticleReadModelInterface::KEY_UUID]);

            $this->valueObjectFactoryMock
                ->shouldReceive('makeArticle')
                ->once()
                ->with($dataWithoutUuid)
                ->andReturn($articleVoMock);

            $this->valueObjectFactoryMock
                ->shouldReceive('makeUuid')
                ->once()
                ->with($uuid)
                ->andReturn($uuidVoMock);

            $this->readModelFactoryMock
                ->shouldReceive('makeArticleActualInstance')
                ->once()
                ->with($articleVoMock, $uuidVoMock)
                ->andReturn($readModelMock);
        }

        // Act
        $result = $this->repository->findOneBy($findCriteria);

        // Assert
        if ($cachedValue !== null) {
            $this->assertSame($readModelMock, $result);
        } else {
            $this->assertNull($result);
        }
    }

    #[Test]
    public function findOneByShouldDelegateToInnerOnCacheMiss(): void
    {
        // Arrange
        $criteria = [
            'slug' => 'test-article',
        ];
        $findCriteria = FindCriteria::fromNative($criteria);
        $uuid = '550e8400-e29b-41d4-a716-446655440000';

        // Create ReadModel mock that can return array for caching
        $readModelArray = [
            ArticleReadModelInterface::KEY_UUID => $uuid,
            'title' => 'Test Article',
            'slug' => 'test-article',
            'short_description' => 'Short description',
            'description' => 'Full description with at least fifty characters for validation testing.',
            'body' => '<p>Body content</p>',
            'status' => 'published',
            'active' => true,
            'deleted' => false,
        ];

        $innerReadModelMock = \Mockery::mock(ArticleReadModelInterface::class);
        $innerReadModelMock
            ->shouldReceive('toArray')
            ->once()
            ->andReturn($readModelArray);

        $this->cacheMock
            ->shouldReceive('get')
            ->once()
            ->andReturnUsing(function ($key, $callback) {
                $itemMock = \Mockery::mock(ItemInterface::class);
                $itemMock->shouldReceive('expiresAfter')
                    ->with(600);
                $itemMock->shouldReceive('tag')
                    ->with(['article']);

                return $callback($itemMock);
            });

        $this->innerRepositoryMock
            ->shouldReceive('findOneBy')
            ->once()
            ->with($findCriteria)
            ->andReturn($innerReadModelMock);

        $this->loggerMock
            ->shouldReceive('debug')
            ->once()
            ->with('Cache MISS', \Mockery::type('array'));

        // Setup factory mocks for reconstruction from cached array
        $articleVoMock = \Mockery::mock(Article::class);
        $uuidVoMock = \Mockery::mock(Uuid::class);
        $reconstructedReadModelMock = \Mockery::mock(ArticleReadModelInterface::class);

        $dataWithoutUuid = $readModelArray;
        unset($dataWithoutUuid[ArticleReadModelInterface::KEY_UUID]);

        $this->valueObjectFactoryMock
            ->shouldReceive('makeArticle')
            ->once()
            ->with($dataWithoutUuid)
            ->andReturn($articleVoMock);

        $this->valueObjectFactoryMock
            ->shouldReceive('makeUuid')
            ->once()
            ->with($uuid)
            ->andReturn($uuidVoMock);

        $this->readModelFactoryMock
            ->shouldReceive('makeArticleActualInstance')
            ->once()
            ->with($articleVoMock, $uuidVoMock)
            ->andReturn($reconstructedReadModelMock);

        // Act
        $result = $this->repository->findOneBy($findCriteria);

        // Assert
        $this->assertInstanceOf(ArticleReadModelInterface::class, $result);
        $this->assertSame($reconstructedReadModelMock, $result);
    }

    #[Test]
    public function constructorShouldAcceptDependencies(): void
    {
        // Arrange & Act
        $repository = new CachedArticleQueryRepository(
            $this->innerRepositoryMock,
            $this->cacheMock,
            $this->loggerMock,
            $this->readModelFactoryMock,
            $this->valueObjectFactoryMock
        );

        // Assert
        $this->assertInstanceOf(CachedArticleQueryRepository::class, $repository);
        $this->assertInstanceOf(ArticleRepositoryInterface::class, $repository);
    }

    #[Test]
    public function fetchOneShouldGenerateCorrectCacheKey(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuidVo = Uuid::fromNative($uuid);
        $expectedCacheKey = 'article.query.item.' . $uuid;

        $this->cacheMock
            ->shouldReceive('get')
            ->once()
            ->with($expectedCacheKey, \Mockery::type('callable'), 1.0)
            ->andReturn(null);

        // Act
        $this->repository->fetchOne($uuidVo);

        // Assert - Mockery verifies the cache key
        $this->assertTrue(true);
    }

    #[Test]
    public function findByCriteriaShouldGenerateDeterministicCacheKey(): void
    {
        // Arrange
        $criteria = [
            'status' => 'published',
        ];
        $findCriteria = FindCriteria::fromNative($criteria);
        $expectedHash = md5(serialize($criteria));
        $expectedPrefix = 'article.query.criteria.' . $expectedHash;

        $this->cacheMock
            ->shouldReceive('get')
            ->once()
            ->with($expectedPrefix, \Mockery::type('callable'), 1.0)
            ->andReturn(null);

        // Act
        $this->repository->findByCriteria($findCriteria);

        // Assert - Mockery verifies the cache key
        $this->assertTrue(true);
    }

    #[Test]
    public function fetchOneShouldSetCorrectTtl(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuidVo = Uuid::fromNative($uuid);
        $ttlCapture = null;

        $this->cacheMock
            ->shouldReceive('get')
            ->once()
            ->andReturnUsing(function ($key, $callback) use (&$ttlCapture) {
                $itemMock = \Mockery::mock(ItemInterface::class);
                $itemMock->shouldReceive('expiresAfter')
                    ->once()
                    ->with(\Mockery::capture($ttlCapture));
                $itemMock->shouldReceive('tag')
                    ->with(\Mockery::type('array'));

                return $callback($itemMock);
            });

        $this->innerRepositoryMock
            ->shouldReceive('fetchOne')
            ->andReturn(null);

        $this->loggerMock->shouldReceive('debug');

        // Act
        $this->repository->fetchOne($uuidVo);

        // Assert - 15 minutes = 900 seconds
        $this->assertSame(900, $ttlCapture);
    }

    #[Test]
    public function findByCriteriaShouldSetCorrectTtl(): void
    {
        // Arrange
        $criteria = [
            'status' => 'published',
        ];
        $findCriteria = FindCriteria::fromNative($criteria);
        $ttlCapture = null;

        $this->cacheMock
            ->shouldReceive('get')
            ->once()
            ->andReturnUsing(function ($key, $callback) use (&$ttlCapture) {
                $itemMock = \Mockery::mock(ItemInterface::class);
                $itemMock->shouldReceive('expiresAfter')
                    ->once()
                    ->with(\Mockery::capture($ttlCapture));
                $itemMock->shouldReceive('tag')
                    ->with(\Mockery::type('array'));

                return $callback($itemMock);
            });

        $this->innerRepositoryMock
            ->shouldReceive('findByCriteria')
            ->andReturn(null);

        $this->loggerMock->shouldReceive('debug');

        // Act
        $this->repository->findByCriteria($findCriteria);

        // Assert - 5 minutes = 300 seconds
        $this->assertSame(300, $ttlCapture);
    }

    #[Test]
    public function findOneByShouldSetCorrectTtl(): void
    {
        // Arrange
        $criteria = [
            'slug' => 'test',
        ];
        $findCriteria = FindCriteria::fromNative($criteria);
        $ttlCapture = null;

        $this->cacheMock
            ->shouldReceive('get')
            ->once()
            ->andReturnUsing(function ($key, $callback) use (&$ttlCapture) {
                $itemMock = \Mockery::mock(ItemInterface::class);
                $itemMock->shouldReceive('expiresAfter')
                    ->once()
                    ->with(\Mockery::capture($ttlCapture));
                $itemMock->shouldReceive('tag')
                    ->with(\Mockery::type('array'));

                return $callback($itemMock);
            });

        $this->innerRepositoryMock
            ->shouldReceive('findOneBy')
            ->andReturn(null);

        $this->loggerMock->shouldReceive('debug');

        // Act
        $this->repository->findOneBy($findCriteria);

        // Assert - 10 minutes = 600 seconds
        $this->assertSame(600, $ttlCapture);
    }

    #[Test]
    public function fetchOneShouldUseOptimalBetaValue(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuidVo = Uuid::fromNative($uuid);

        $this->cacheMock
            ->shouldReceive('get')
            ->once()
            ->with(
                \Mockery::type('string'),
                \Mockery::type('callable'),
                1.0 // Optimal beta value per XFetch algorithm
            )
            ->andReturn(null);

        // Act
        $this->repository->fetchOne($uuidVo);

        // Assert - Mockery verifies beta = 1.0
        $this->assertTrue(true);
    }
}
