<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\Factory;

use Micro\Article\Domain\Entity\ArticleEntityInterface;
use Micro\Article\Domain\Factory\ReadModelFactory;
use Micro\Article\Domain\Factory\ReadModelFactoryInterface;
use Micro\Article\Domain\ReadModel\ArticleReadModel;
use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\Factory\ReadModelFactoryDataProvider;

/**
 * Unit tests for ReadModelFactory.
 */
#[CoversClass(ReadModelFactory::class)]
final class ReadModelFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ReadModelFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ReadModelFactory();
    }

    #[Test]
    #[DataProviderExternal(ReadModelFactoryDataProvider::class, 'provideMakeArticleActualInstanceScenarios')]
    public function makeArticleActualInstanceShouldCreateReadModel(string $uuid, array $articleData): void
    {
        // Arrange
        $uuidVo = Uuid::fromNative($uuid);
        $article = Article::fromArray($articleData);

        // Act
        $result = $this->factory->makeArticleActualInstance($article, $uuidVo);

        // Assert
        $this->assertInstanceOf(ArticleReadModelInterface::class, $result);
        $this->assertInstanceOf(ArticleReadModel::class, $result);
    }

    #[Test]
    public function makeArticleActualInstanceShouldPopulateReadModelWithData(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuidVo = Uuid::fromNative($uuid);
        $articleData = [
            'title' => 'Test Article Article',
            'short_description' => 'Short description.',
            'description' => 'Full description that meets the minimum requirements of fifty characters for validation testing purposes.',
            'slug' => 'test-article-article',
            'status' => 'published',
        ];
        $article = Article::fromArray($articleData);

        // Act
        $result = $this->factory->makeArticleActualInstance($article, $uuidVo);

        // Assert
        $this->assertSame($uuid, $result->getUuid()->toNative());
        $this->assertSame('Test Article Article', $result->getTitle());
        $this->assertSame('Short description.', $result->getShortDescription());
        $this->assertSame('test-article-article', $result->getSlug());
        $this->assertSame('published', $result->getStatus());
    }

    #[Test]
    #[DataProviderExternal(ReadModelFactoryDataProvider::class, 'provideMakeArticleActualInstanceByEntityScenarios')]
    public function makeArticleActualInstanceByEntityShouldCreateReadModel(string $uuid, array $articleData): void
    {
        // Arrange
        $uuidVo = Uuid::fromNative($uuid);
        $article = Article::fromArray($articleData);

        $entityMock = \Mockery::mock(ArticleEntityInterface::class);
        $entityMock->shouldReceive('assembleToValueObject')
            ->andReturn($article);
        $entityMock->shouldReceive('getUuid')
            ->andReturn($uuidVo);

        // Act
        $result = $this->factory->makeArticleActualInstanceByEntity($entityMock);

        // Assert
        $this->assertInstanceOf(ArticleReadModelInterface::class, $result);
        $this->assertInstanceOf(ArticleReadModel::class, $result);
    }

    #[Test]
    public function factoryShouldImplementReadModelFactoryInterface(): void
    {
        // Assert
        $this->assertInstanceOf(ReadModelFactoryInterface::class, $this->factory);
    }
}
