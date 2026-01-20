<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\ReadModel;

use Micro\Article\Domain\Entity\ArticleEntityInterface;
use Micro\Article\Domain\ReadModel\ArticleReadModel;
use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\ReadModel\ArticleReadModelDataProvider;

/**
 * Unit tests for ArticleReadModel.
 */
#[CoversClass(ArticleReadModel::class)]
final class ArticleReadModelTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[Test]
    #[DataProviderExternal(ArticleReadModelDataProvider::class, 'provideCompleteReadModelData')]
    public function createByValueObjectShouldCreateReadModel(string $uuid, array $articleData): void
    {
        // Arrange
        $uuidVo = Uuid::fromNative($uuid);
        $article = Article::fromArray($articleData);

        // Act
        $result = ArticleReadModel::createByValueObject($article, $uuidVo);

        // Assert
        $this->assertInstanceOf(ArticleReadModelInterface::class, $result);
        $this->assertInstanceOf(ArticleReadModel::class, $result);
    }

    #[Test]
    public function createByValueObjectShouldPopulateAllFields(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuidVo = Uuid::fromNative($uuid);
        $articleData = [
            'title' => 'Test Article',
            'short_description' => 'Short description.',
            'description' => 'Full description that meets the minimum requirements of fifty characters for validation testing purposes.',
            'slug' => 'test-article',
            'event_id' => 12345,
            'status' => 'published',
        ];
        $article = Article::fromArray($articleData);

        // Act
        $result = ArticleReadModel::createByValueObject($article, $uuidVo);

        // Assert
        $this->assertSame($uuid, $result->getUuid()->toNative());
        $this->assertSame('Test Article', $result->getTitle());
        $this->assertSame('Short description.', $result->getShortDescription());
        $this->assertSame('test-article', $result->getSlug());
        $this->assertSame(12345, $result->getEventId());
        $this->assertSame('published', $result->getStatus());
    }

    #[Test]
    public function createByEntityShouldCreateReadModel(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuidVo = Uuid::fromNative($uuid);
        $articleData = [
            'title' => 'Entity Article',
            'status' => 'draft',
        ];
        $article = Article::fromArray($articleData);

        $entityMock = \Mockery::mock(ArticleEntityInterface::class);
        $entityMock->shouldReceive('assembleToValueObject')
            ->andReturn($article);
        $entityMock->shouldReceive('getUuid')
            ->andReturn($uuidVo);

        // Act
        $result = ArticleReadModel::createByEntity($entityMock);

        // Assert
        $this->assertInstanceOf(ArticleReadModelInterface::class, $result);
    }

    #[Test]
    #[DataProviderExternal(ArticleReadModelDataProvider::class, 'provideToArrayScenarios')]
    public function toArrayShouldReturnExpectedKeys(string $uuid, array $articleData, array $expectedKeys): void
    {
        // Arrange
        $uuidVo = Uuid::fromNative($uuid);
        $article = Article::fromArray($articleData);
        $readModel = ArticleReadModel::createByValueObject($article, $uuidVo);

        // Act
        $result = $readModel->toArray();

        // Assert
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $result);
        }
    }

    #[Test]
    public function getPrimaryKeyValueShouldReturnUuid(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuidVo = Uuid::fromNative($uuid);
        $article = Article::fromArray([
            'title' => 'Test',
        ]);
        $readModel = ArticleReadModel::createByValueObject($article, $uuidVo);

        // Act
        $result = $readModel->getPrimaryKeyValue();

        // Assert
        $this->assertSame($uuid, $result);
    }

    #[Test]
    public function updateByValueObjectShouldUpdateFields(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuidVo = Uuid::fromNative($uuid);
        $initialArticle = Article::fromArray([
            'title' => 'Initial Title',
        ]);
        $readModel = ArticleReadModel::createByValueObject($initialArticle, $uuidVo);

        $updatedArticle = Article::fromArray([
            'title' => 'Updated Title',
            'status' => 'published',
        ]);

        // Act
        $readModel->updateByValueObject($updatedArticle, $uuidVo);

        // Assert
        $this->assertSame('Updated Title', $readModel->getTitle());
        $this->assertSame('published', $readModel->getStatus());
    }

    #[Test]
    public function normalizeShouldReturnSameAsToArray(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuidVo = Uuid::fromNative($uuid);
        $article = Article::fromArray([
            'title' => 'Test',
        ]);
        $readModel = ArticleReadModel::createByValueObject($article, $uuidVo);

        // Act
        $toArrayResult = $readModel->toArray();
        $normalizeResult = $readModel->normalize();

        // Assert
        $this->assertSame($toArrayResult, $normalizeResult);
    }

    #[Test]
    public function jsonSerializeShouldReturnSameAsToArray(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuidVo = Uuid::fromNative($uuid);
        $article = Article::fromArray([
            'title' => 'Test',
        ]);
        $readModel = ArticleReadModel::createByValueObject($article, $uuidVo);

        // Act
        $toArrayResult = $readModel->toArray();
        $jsonSerializeResult = $readModel->jsonSerialize();

        // Assert
        $this->assertSame($toArrayResult, $jsonSerializeResult);
    }

    #[Test]
    public function gettersForNullFieldsShouldReturnNull(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuidVo = Uuid::fromNative($uuid);
        $article = Article::fromArray([]);
        $readModel = ArticleReadModel::createByValueObject($article, $uuidVo);

        // Assert
        $this->assertNull($readModel->getTitle());
        $this->assertNull($readModel->getShortDescription());
        $this->assertNull($readModel->getDescription());
        $this->assertNull($readModel->getSlug());
        $this->assertNull($readModel->getEventId());
        $this->assertNull($readModel->getStatus());
        $this->assertNull($readModel->getPublishedAt());
        $this->assertNull($readModel->getArchivedAt());
        $this->assertNull($readModel->getCreatedAt());
        $this->assertNull($readModel->getUpdatedAt());
    }

    #[Test]
    public function readModelShouldImplementInterface(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuidVo = Uuid::fromNative($uuid);
        $article = Article::fromArray([]);
        $readModel = ArticleReadModel::createByValueObject($article, $uuidVo);

        // Assert
        $this->assertInstanceOf(ArticleReadModelInterface::class, $readModel);
    }

    #[Test]
    public function assembleFromValueObjectWithInvalidTypeShouldThrowException(): void
    {
        // Arrange
        $uuid = Uuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $readModel = new ArticleReadModel();
        $invalidValueObject = \Mockery::mock(\MicroModule\ValueObject\ValueObjectInterface::class);

        // Assert
        $this->expectException(\MicroModule\Base\Domain\Exception\ValueObjectInvalidException::class);
        $this->expectExceptionMessage('ArticleEntity can be assembled only with Article value object');

        // Act
        $readModel->assembleFromValueObject($invalidValueObject, $uuid);
    }
}
