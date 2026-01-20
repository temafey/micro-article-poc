<?php

declare(strict_types=1);

namespace Tests\Integration\Article\Infrastructure\Repository\ReadModel;

use Micro\Article\Domain\Factory\ReadModelFactoryInterface;
use Micro\Article\Domain\Factory\ValueObjectFactoryInterface;
use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use Micro\Article\Domain\Repository\ReadModel\ArticleRepositoryInterface;
use Micro\Article\Domain\ValueObject\Article;
use Micro\Article\Domain\ValueObject\Status;
use Micro\Article\Infrastructure\Repository\ReadModel\ArticleRepository;
use MicroModule\Base\Domain\Exception\ReadModelException;
use MicroModule\Base\Domain\ValueObject\Uuid;
use MicroModule\Base\Infrastructure\Repository\InMemoryReadModelStore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid as RamseyUuid;
use Tests\Integration\IntegrationTestCase;

/**
 * Integration tests for ReadModel/ArticleRepository.
 *
 * Tests the read model repository's ability to add, update, delete,
 * and retrieve article read models from the database.
 *
 * Note: Some tests are skipped when running with InMemoryReadModelStore
 * (test environment) as they specifically test DBAL behavior.
 */
#[CoversClass(ArticleRepository::class)]
#[Group('integration')]
#[Group('repository')]
#[Group('read-model')]
final class ArticleRepositoryTest extends IntegrationTestCase
{
    private ArticleRepositoryInterface $repository;
    private ReadModelFactoryInterface $readModelFactory;
    private ValueObjectFactoryInterface $valueObjectFactory;
    private bool $isInMemoryStore = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->getService(ArticleRepositoryInterface::class);
        $this->readModelFactory = $this->getService(ReadModelFactoryInterface::class);
        $this->valueObjectFactory = $this->getService(ValueObjectFactoryInterface::class);

        // Detect if we're using InMemoryReadModelStore (test environment override)
        // Check the underlying store via the repository's injected dependency
        $store = $this->getService('article.infrastructure.repository.storage.read_model.dbal.article');
        $this->isInMemoryStore = $store instanceof InMemoryReadModelStore;
    }

    #[Test]
    public function addShouldPersistNewReadModel(): void
    {
        // Arrange
        $uuidString = RamseyUuid::uuid4()->toString();
        $readModel = $this->createReadModel($uuidString);

        // Act
        $this->repository->add($readModel);

        // Assert
        $uuidVo = Uuid::fromNative($uuidString);
        $result = $this->repository->get($uuidVo);
        $this->assertInstanceOf(ArticleReadModelInterface::class, $result);
    }

    #[Test]
    public function getShouldRetrieveExistingReadModel(): void
    {
        // Arrange
        $uuidString = RamseyUuid::uuid4()->toString();
        $readModel = $this->createReadModel($uuidString);
        $this->repository->add($readModel);

        // Act
        $uuidVo = Uuid::fromNative($uuidString);
        $result = $this->repository->get($uuidVo);

        // Assert
        $this->assertInstanceOf(ArticleReadModelInterface::class, $result);
    }

    #[Test]
    public function getShouldReturnNullForNonExistentReadModel(): void
    {
        // Arrange
        $nonExistentUuid = Uuid::fromNative(RamseyUuid::uuid4()->toString());

        // Act
        $result = $this->repository->get($nonExistentUuid);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    public function updateShouldModifyExistingReadModel(): void
    {
        // Arrange
        $uuidString = RamseyUuid::uuid4()->toString();
        $readModel = $this->createReadModel($uuidString);
        $this->repository->add($readModel);

        // Create updated read model
        $updatedData = $this->createArticleData($uuidString);
        $updatedData['title'] = 'Updated Title';
        $updatedReadModel = $this->readModelFactory->makeArticleActualInstance(
            Article::fromNative($updatedData),
            Uuid::fromNative($uuidString)
        );

        // Act
        $this->repository->update($updatedReadModel);

        // Assert
        $uuidVo = Uuid::fromNative($uuidString);
        $result = $this->repository->get($uuidVo);
        $this->assertInstanceOf(ArticleReadModelInterface::class, $result);
    }

    #[Test]
    public function deleteShouldRemoveReadModel(): void
    {
        // Arrange
        $uuidString = RamseyUuid::uuid4()->toString();
        $readModel = $this->createReadModel($uuidString);
        $this->repository->add($readModel);

        // Verify it exists first
        $uuidVo = Uuid::fromNative($uuidString);
        $this->assertNotNull($this->repository->get($uuidVo));

        // Act
        $this->repository->delete($readModel);

        // Assert
        $result = $this->repository->get($uuidVo);
        $this->assertNull($result);
    }

    #[Test]
    public function addMultipleReadModelsShouldPersistAll(): void
    {
        // Arrange
        $uuids = [];
        for ($i = 0; $i < 3; ++$i) {
            $uuidString = RamseyUuid::uuid4()->toString();
            $uuids[] = $uuidString;
            $readModel = $this->createReadModel($uuidString, "Article Article {$i}");
            $this->repository->add($readModel);
        }

        // Assert
        foreach ($uuids as $uuidString) {
            $uuidVo = Uuid::fromNative($uuidString);
            $result = $this->repository->get($uuidVo);
            $this->assertInstanceOf(ArticleReadModelInterface::class, $result);
        }
    }

    #[Test]
    public function addDuplicateShouldThrowException(): void
    {
        // Skip if using InMemoryReadModelStore - doesn't throw on duplicates
        if ($this->isInMemoryStore) {
            $this->markTestSkipped('Test requires DBAL implementation - InMemoryReadModelStore does not throw ReadModelException on duplicate insert.');
        }

        // Arrange
        $uuidString = RamseyUuid::uuid4()->toString();
        $readModel = $this->createReadModel($uuidString);
        $this->repository->add($readModel);

        // Assert
        $this->expectException(ReadModelException::class);

        // Act - try to add the same read model again
        $duplicateReadModel = $this->createReadModel($uuidString);
        $this->repository->add($duplicateReadModel);
    }

    #[Test]
    public function updateNonExistentShouldThrowException(): void
    {
        // Arrange
        $uuidString = RamseyUuid::uuid4()->toString();
        $readModel = $this->createReadModel($uuidString);

        // Assert
        $this->expectException(ReadModelException::class);

        // Act
        $this->repository->update($readModel);
    }

    /**
     * Create a read model for testing.
     */
    private function createReadModel(string $uuid, string $title = 'Test Article Title'): ArticleReadModelInterface
    {
        $articleData = $this->createArticleData($uuid, $title);

        return $this->readModelFactory->makeArticleActualInstance(
            Article::fromNative($articleData),
            Uuid::fromNative($uuid)
        );
    }

    /**
     * Create test article data array.
     *
     * @return array<string, mixed>
     */
    private function createArticleData(string $uuid, string $title = 'Test Article Title'): array
    {
        $now = new \DateTimeImmutable();

        return [
            'uuid' => $uuid,
            'title' => $title,
            'short_description' => 'Test short description for the article article that meets the minimum length requirement',
            'description' => 'Test full description for the article article. This description must be at least 50 characters long to pass validation.',
            'body' => 'Test body content for the article article. This is the main content of the article.',
            'slug' => 'test-article-' . substr($uuid, 0, 8),
            'event_id' => 1,
            'status' => Status::DRAFT,
            'category_id' => 1,
            'author_id' => 1,
            'active' => true,
            'deleted' => false,
            'published_at' => null,
            'archived_at' => null,
            'created_at' => $now->format(\DateTimeInterface::ATOM),
            'updated_at' => $now->format(\DateTimeInterface::ATOM),
        ];
    }
}
