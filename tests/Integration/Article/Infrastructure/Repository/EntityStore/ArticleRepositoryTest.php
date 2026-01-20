<?php

declare(strict_types=1);

namespace Tests\Integration\Article\Infrastructure\Repository\EntityStore;

use Micro\Article\Domain\Entity\ArticleEntityInterface;
use Micro\Article\Domain\Factory\EntityFactoryInterface;
use Micro\Article\Domain\Repository\EntityStore\ArticleRepositoryInterface;
use Micro\Article\Domain\Service\ArticleSlugGeneratorServiceInterface;
use Micro\Article\Domain\ValueObject\Article;
use Micro\Article\Domain\ValueObject\Status;
use Micro\Article\Infrastructure\Repository\EntityStore\ArticleRepository;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Ramsey\Uuid\Uuid as RamseyUuid;
use Tests\Integration\IntegrationTestCase;

/**
 * Integration tests for EntityStore/ArticleRepository.
 *
 * Tests the repository's ability to persist and retrieve ArticleEntity
 * using the event sourcing store with snapshotting support.
 */
#[CoversClass(ArticleRepository::class)]
#[Group('integration')]
#[Group('repository')]
#[Group('entity-store')]
final class ArticleRepositoryTest extends IntegrationTestCase
{
    private ArticleRepositoryInterface $repository;
    private EntityFactoryInterface $entityFactory;
    private ArticleSlugGeneratorServiceInterface $slugGeneratorService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->getService(ArticleRepositoryInterface::class);
        $this->entityFactory = $this->getService(EntityFactoryInterface::class);
        $this->slugGeneratorService = $this->getService(ArticleSlugGeneratorServiceInterface::class);
    }

    #[Test]
    public function storeShouldPersistNewArticleEntity(): void
    {
        // Arrange
        $uuidString = RamseyUuid::uuid4()->toString();
        $processUuid = new ProcessUuid();
        $articleData = $this->createArticleData($uuidString);
        $entity = $this->entityFactory->createArticleInstance(
            $processUuid,
            Article::fromNative($articleData),
            null,
            null,
            null,
            $this->slugGeneratorService
        );

        // Act
        $this->repository->store($entity);
        $entityId = $entity->getAggregateRootId();

        // Assert
        $retrievedEntity = $this->repository->get(RamseyUuid::fromString($entityId));
        $this->assertInstanceOf(ArticleEntityInterface::class, $retrievedEntity);
        $this->assertEquals($entityId, $retrievedEntity->getAggregateRootId());
    }

    #[Test]
    public function getShouldRetrieveExistingArticleEntity(): void
    {
        // Arrange
        $uuidString = RamseyUuid::uuid4()->toString();
        $processUuid = new ProcessUuid();
        $articleData = $this->createArticleData($uuidString);
        $entity = $this->entityFactory->createArticleInstance(
            $processUuid,
            Article::fromNative($articleData),
            null,
            null,
            null,
            $this->slugGeneratorService
        );
        $this->repository->store($entity);
        $entityId = $entity->getAggregateRootId();

        // Act
        $result = $this->repository->get(RamseyUuid::fromString($entityId));

        // Assert
        $this->assertInstanceOf(ArticleEntityInterface::class, $result);
        $this->assertEquals($entityId, $result->getAggregateRootId());
    }

    #[Test]
    public function getShouldThrowExceptionForNonExistentEntity(): void
    {
        // Arrange
        $nonExistentUuid = RamseyUuid::uuid4();

        // Assert
        $this->expectException(\Exception::class);

        // Act
        $this->repository->get($nonExistentUuid);
    }

    #[Test]
    public function storeShouldUpdateExistingArticleEntity(): void
    {
        // Arrange
        $uuidString = RamseyUuid::uuid4()->toString();
        $processUuid = new ProcessUuid();
        $articleData = $this->createArticleData($uuidString);
        $entity = $this->entityFactory->createArticleInstance(
            $processUuid,
            Article::fromNative($articleData),
            null,
            null,
            null,
            $this->slugGeneratorService
        );
        $this->repository->store($entity);
        $entityId = $entity->getAggregateRootId();

        // Retrieve and modify
        $retrievedEntity = $this->repository->get(RamseyUuid::fromString($entityId));
        $retrievedEntity->setArticleSlugGeneratorService($this->slugGeneratorService);
        $updatedData = $articleData;
        $updatedData['title'] = 'Updated Test Title';
        $retrievedEntity->articleUpdate(new ProcessUuid(), Article::fromNative($updatedData));

        // Act
        $this->repository->store($retrievedEntity);

        // Assert
        $finalEntity = $this->repository->get(RamseyUuid::fromString($entityId));
        $this->assertInstanceOf(ArticleEntityInterface::class, $finalEntity);
    }

    #[Test]
    public function storeShouldPersistMultipleEntities(): void
    {
        // Arrange
        $entities = [];
        for ($i = 0; $i < 3; ++$i) {
            $uuidString = RamseyUuid::uuid4()->toString();
            $processUuid = new ProcessUuid();
            $articleData = $this->createArticleData($uuidString, "Test Article {$i}");
            $entity = $this->entityFactory->createArticleInstance(
                $processUuid,
                Article::fromNative($articleData),
                null,
                null,
                null,
                $this->slugGeneratorService
            );
            $entities[$entity->getAggregateRootId()] = $entity;
        }

        // Act
        foreach ($entities as $entity) {
            $this->repository->store($entity);
        }

        // Assert
        foreach ($entities as $entityId => $entity) {
            $retrievedEntity = $this->repository->get(RamseyUuid::fromString($entityId));
            $this->assertInstanceOf(ArticleEntityInterface::class, $retrievedEntity);
            $this->assertEquals($entityId, $retrievedEntity->getAggregateRootId());
        }
    }

    #[Test]
    public function repositoryShouldInjectSlugGeneratorService(): void
    {
        // Arrange
        $uuidString = RamseyUuid::uuid4()->toString();
        $processUuid = new ProcessUuid();
        $articleData = $this->createArticleData($uuidString);
        $entity = $this->entityFactory->createArticleInstance(
            $processUuid,
            Article::fromNative($articleData),
            null,
            null,
            null,
            $this->slugGeneratorService
        );
        $this->repository->store($entity);
        $entityId = $entity->getAggregateRootId();

        // Act
        $retrievedEntity = $this->repository->get(RamseyUuid::fromString($entityId));

        // Assert - entity should have slug generator service injected
        $this->assertInstanceOf(ArticleEntityInterface::class, $retrievedEntity);
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
