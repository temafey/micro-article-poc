<?php

declare(strict_types=1);

namespace Tests\Integration\Article\Infrastructure\Repository\Storage;

use Micro\Article\Domain\Factory\ReadModelFactoryInterface;
use Micro\Article\Domain\Factory\ValueObjectFactoryInterface;
use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use Micro\Article\Domain\ValueObject\Article;
use Micro\Article\Domain\ValueObject\Status;
use Micro\Article\Infrastructure\Repository\Storage\DBALReadModelStore;
use MicroModule\Base\Domain\Repository\ReadModelStoreInterface;
use MicroModule\Base\Domain\ValueObject\Uuid;
use MicroModule\Base\Infrastructure\Repository\Exception\NotFoundException;
use MicroModule\Base\Infrastructure\Repository\InMemoryReadModelStore;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid as RamseyUuid;
use Tests\Integration\IntegrationTestCase;

/**
 * Integration tests for DBALReadModelStore.
 *
 * Tests the DBAL-based read model store's ability to perform
 * CRUD operations on article read models in PostgreSQL.
 *
 * Note: Some tests are skipped when running with InMemoryReadModelStore
 * (test environment) as they specifically test DBAL behavior.
 */
#[CoversClass(DBALReadModelStore::class)]
#[Group('integration')]
#[Group('repository')]
#[Group('dbal')]
final class DBALReadModelStoreTest extends IntegrationTestCase
{
    private const TABLE_NAME = 'article_read_model';

    private ReadModelStoreInterface $readModelStore;
    private ReadModelFactoryInterface $readModelFactory;
    private ValueObjectFactoryInterface $valueObjectFactory;
    private bool $isInMemoryStore = false;

    protected function setUp(): void
    {
        parent::setUp();

        // Get the article-specific DBAL read model store
        $this->readModelStore = $this->getService('article.infrastructure.repository.storage.read_model.dbal.article');
        $this->readModelFactory = $this->getService(ReadModelFactoryInterface::class);
        $this->valueObjectFactory = $this->getService(ValueObjectFactoryInterface::class);

        // Detect if we're using InMemoryReadModelStore (test environment override)
        $this->isInMemoryStore = $this->readModelStore instanceof InMemoryReadModelStore;
    }

    #[Test]
    public function insertOneShouldPersistReadModel(): void
    {
        // Arrange
        $uuidString = RamseyUuid::uuid4()->toString();
        $readModel = $this->createReadModel($uuidString);

        // Act
        $this->readModelStore->insertOne($readModel);

        // Assert
        $result = $this->readModelStore->findOne($uuidString);
        $this->assertIsArray($result);
        $this->assertEquals($uuidString, $result[ArticleReadModelInterface::KEY_UUID]);
    }

    #[Test]
    public function findOneShouldRetrieveExistingRecord(): void
    {
        // Arrange
        $uuidString = RamseyUuid::uuid4()->toString();
        $readModel = $this->createReadModel($uuidString);
        $this->readModelStore->insertOne($readModel);

        // Act
        $result = $this->readModelStore->findOne($uuidString);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals($uuidString, $result[ArticleReadModelInterface::KEY_UUID]);
        $this->assertEquals('Test Article Title', $result['title']);
    }

    #[Test]
    public function findOneShouldThrowExceptionForNonExistentRecord(): void
    {
        // Arrange
        $nonExistentUuid = RamseyUuid::uuid4()->toString();

        // Assert
        $this->expectException(NotFoundException::class);

        // Act
        $this->readModelStore->findOne($nonExistentUuid);
    }

    #[Test]
    public function updateOneShouldModifyExistingRecord(): void
    {
        // Arrange
        $uuidString = RamseyUuid::uuid4()->toString();
        $readModel = $this->createReadModel($uuidString);
        $this->readModelStore->insertOne($readModel);

        // Create updated read model
        $updatedData = $this->createArticleData($uuidString);
        $updatedData['title'] = 'Updated Title';
        $updatedReadModel = $this->readModelFactory->makeArticleActualInstance(
            Article::fromNative($updatedData),
            Uuid::fromNative($uuidString)
        );

        // Act
        $this->readModelStore->updateOne($updatedReadModel);

        // Assert
        $result = $this->readModelStore->findOne($uuidString);
        $this->assertEquals('Updated Title', $result['title']);
    }

    #[Test]
    public function deleteOneShouldRemoveRecord(): void
    {
        // Arrange
        $uuidString = RamseyUuid::uuid4()->toString();
        $readModel = $this->createReadModel($uuidString);
        $this->readModelStore->insertOne($readModel);

        // Verify it exists
        $result = $this->readModelStore->findOne($uuidString);
        $this->assertIsArray($result);

        // Act
        $this->readModelStore->deleteOne($readModel);

        // Assert
        $this->expectException(NotFoundException::class);
        $this->readModelStore->findOne($uuidString);
    }

    #[Test]
    public function findByShouldReturnMatchingRecords(): void
    {
        // Arrange
        $status = Status::DRAFT;
        $uuids = [];
        for ($i = 0; $i < 3; ++$i) {
            $uuidString = RamseyUuid::uuid4()->toString();
            $uuids[] = $uuidString;
            $readModel = $this->createReadModel($uuidString, "Article Article {$i}", $status);
            $this->readModelStore->insertOne($readModel);
        }

        // Act
        $results = $this->readModelStore->findBy([
            'status' => $status,
        ]);

        // Assert
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(3, count($results));
    }

    #[Test]
    public function findByWithLimitShouldReturnLimitedRecords(): void
    {
        // Skip if using InMemoryReadModelStore - limit behavior differs from DBAL
        if ($this->isInMemoryStore) {
            $this->markTestSkipped('Test requires DBAL implementation - InMemoryReadModelStore does not support limit parameter correctly.');
        }

        // Arrange - use archived status to avoid conflicts with draft records
        $status = Status::ARCHIVED;
        for ($i = 0; $i < 5; ++$i) {
            $uuidString = RamseyUuid::uuid4()->toString();
            $readModel = $this->createReadModel($uuidString, "Limited Article {$i}", $status);
            $this->readModelStore->insertOne($readModel);
        }

        // Act
        $results = $this->readModelStore->findBy([
            'status' => $status,
        ], null, 2);

        // Assert
        $this->assertIsArray($results);
        $this->assertLessThanOrEqual(2, count($results));
    }

    #[Test]
    public function findOneByShouldReturnSingleMatch(): void
    {
        // Skip if using InMemoryReadModelStore - requires primary key in criteria
        if ($this->isInMemoryStore) {
            $this->markTestSkipped('Test requires DBAL implementation - InMemoryReadModelStore requires primary key in findOneBy criteria.');
        }

        // Arrange
        $uuidString = RamseyUuid::uuid4()->toString();
        $slug = 'unique-slug-' . substr($uuidString, 0, 8);
        $readModel = $this->createReadModelWithSlug($uuidString, $slug);
        $this->readModelStore->insertOne($readModel);

        // Act
        $result = $this->readModelStore->findOneBy([
            'slug' => $slug,
        ]);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals($uuidString, $result[ArticleReadModelInterface::KEY_UUID]);
        $this->assertEquals($slug, $result['slug']);
    }

    #[Test]
    public function findOneByShouldThrowExceptionWhenNotFound(): void
    {
        // Skip if using InMemoryReadModelStore - requires primary key in criteria
        if ($this->isInMemoryStore) {
            $this->markTestSkipped('Test requires DBAL implementation - InMemoryReadModelStore requires primary key in findOneBy criteria.');
        }

        // Arrange
        $nonExistentSlug = 'non-existent-slug-' . RamseyUuid::uuid4()->toString();

        // Assert
        $this->expectException(NotFoundException::class);

        // Act
        $this->readModelStore->findOneBy([
            'slug' => $nonExistentSlug,
        ]);
    }

    #[Test]
    public function insertMultipleRecordsShouldPersistAll(): void
    {
        // Arrange
        $uuids = [];
        for ($i = 0; $i < 5; ++$i) {
            $uuidString = RamseyUuid::uuid4()->toString();
            $uuids[] = $uuidString;
            $readModel = $this->createReadModel($uuidString, "Batch Article {$i}");
            $this->readModelStore->insertOne($readModel);
        }

        // Assert
        foreach ($uuids as $uuidString) {
            $result = $this->readModelStore->findOne($uuidString);
            $this->assertIsArray($result);
            $this->assertEquals($uuidString, $result[ArticleReadModelInterface::KEY_UUID]);
        }
    }

    /**
     * Create a read model for testing.
     */
    private function createReadModel(
        string $uuid,
        string $title = 'Test Article Title',
        string $status = Status::DRAFT,
    ): ArticleReadModelInterface {
        $articleData = $this->createArticleData($uuid, $title, $status);

        return $this->readModelFactory->makeArticleActualInstance(
            Article::fromNative($articleData),
            Uuid::fromNative($uuid)
        );
    }

    /**
     * Create a read model with a specific slug.
     */
    private function createReadModelWithSlug(string $uuid, string $slug): ArticleReadModelInterface
    {
        $articleData = $this->createArticleData($uuid);
        $articleData['slug'] = $slug;

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
    private function createArticleData(
        string $uuid,
        string $title = 'Test Article Title',
        string $status = Status::DRAFT,
    ): array {
        $now = new \DateTimeImmutable();

        return [
            'uuid' => $uuid,
            'title' => $title,
            'short_description' => 'Test short description for the article article that meets the minimum length requirement',
            'description' => 'Test full description for the article article. This description must be at least 50 characters long to pass validation.',
            'body' => 'Test body content for the article article. This is the main content of the article.',
            'slug' => 'test-article-' . substr($uuid, 0, 8),
            'event_id' => 1,
            'status' => $status,
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
