<?php

declare(strict_types=1);

namespace Tests\Unit\Mock\Article\Domain\Factory;

use Micro\Article\Domain\Entity\ArticleEntity;
use Micro\Article\Domain\Factory\EntityFactoryInterface;
use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Mockery\MockInterface;

/**
 * Mock Factory Trait for EntityFactoryInterface.
 *
 * Provides reusable mock configuration for entity factory operations.
 */
trait EntityFactoryMockTrait
{
    protected MockInterface|EntityFactoryInterface $entityFactoryMock;

    /**
     * Create a mock for EntityFactoryInterface.
     */
    protected function createEntityFactoryMock(): MockInterface|EntityFactoryInterface
    {
        $this->entityFactoryMock = \Mockery::mock(EntityFactoryInterface::class);

        return $this->entityFactoryMock;
    }

    /**
     * Configure mock to expect createArticleInstance method call and return a mock entity.
     *
     * @param string $expectedUuid The UUID string for the created entity
     * @param int $times Number of expected calls
     * @return MockInterface|ArticleEntity The configured mock entity
     */
    protected function expectEntityFactoryCreateArticleInstance(
        string $expectedUuid,
        int $times = 1,
    ): MockInterface|ArticleEntity {
        $articleEntityMock = $this->createArticleEntityMockForFactory($expectedUuid);

        $this->entityFactoryMock
            ->shouldReceive('createArticleInstance')
            ->with(
                \Mockery::type(ProcessUuid::class),
                \Mockery::type(Article::class),
                \Mockery::any() // Optional third parameter (can be null or Uuid)
            )
            ->times($times)
            ->andReturn($articleEntityMock);

        return $articleEntityMock;
    }

    /**
     * Configure mock createArticleInstance to throw exception.
     */
    protected function expectEntityFactoryCreateArticleInstanceThrowsException(
        \Throwable $exception,
        int $times = 1,
    ): void {
        $this->entityFactoryMock
            ->shouldReceive('createArticleInstance')
            ->times($times)
            ->andThrow($exception);
    }

    /**
     * Create a mock ArticleEntity with UUID configured for factory return.
     *
     * @param string $uuid The UUID string for the entity
     * @return ArticleEntity|MockInterface
     */
    protected function createArticleEntityMockForFactory(string $uuid): ArticleEntity|MockInterface
    {
        $articleEntityMock = \Mockery::mock(ArticleEntity::class);
        $uuidMock = \Mockery::mock(Uuid::class);
        $uuidMock->shouldReceive('toString')->andReturn($uuid);
        $uuidMock->shouldReceive('toNative')->andReturn($uuid);
        $articleEntityMock->shouldReceive('getUuid')->andReturn($uuidMock);

        return $articleEntityMock;
    }
}
