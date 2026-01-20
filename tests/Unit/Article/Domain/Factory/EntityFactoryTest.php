<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\Factory;

use Micro\Article\Domain\Entity\ArticleEntity;
use Micro\Article\Domain\Entity\ArticleEntityInterface;
use Micro\Article\Domain\Event\ArticleCreatedEvent;
use Micro\Article\Domain\Factory\EntityFactory;
use Micro\Article\Domain\Factory\EntityFactoryInterface;
use Micro\Article\Domain\Factory\EventFactoryInterface;
use Micro\Article\Domain\Factory\ValueObjectFactoryInterface;
use Micro\Article\Domain\Service\ArticleSlugGeneratorServiceInterface;
use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Mockery;
use Ramsey\Uuid\Uuid as RamseyUuid;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\Factory\EntityFactoryDataProvider;

/**
 * Unit tests for EntityFactory.
 */
#[CoversClass(EntityFactory::class)]
final class EntityFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private EntityFactory $factory;
    private EventFactoryInterface&Mockery\MockInterface $eventFactoryMock;
    private ValueObjectFactoryInterface&Mockery\MockInterface $valueObjectFactoryMock;
    private ArticleSlugGeneratorServiceInterface&Mockery\MockInterface $slugGeneratorMock;

    protected function setUp(): void
    {
        $this->eventFactoryMock = \Mockery::mock(EventFactoryInterface::class);
        $this->valueObjectFactoryMock = \Mockery::mock(ValueObjectFactoryInterface::class);
        $this->slugGeneratorMock = \Mockery::mock(ArticleSlugGeneratorServiceInterface::class);

        $this->factory = new EntityFactory(
            $this->eventFactoryMock,
            $this->valueObjectFactoryMock,
            $this->slugGeneratorMock
        );
    }

    #[Test]
    #[DataProviderExternal(EntityFactoryDataProvider::class, 'provideMakeActualArticleInstanceScenarios')]
    public function makeActualArticleInstanceShouldCreateEntity(string $uuid, array $articleData): void
    {
        // Arrange
        $uuidVo = Uuid::fromNative($uuid);
        $article = Article::fromArray($articleData);

        // Act
        $result = $this->factory->makeActualArticleInstance($uuidVo, $article);

        // Assert
        $this->assertInstanceOf(ArticleEntityInterface::class, $result);
        $this->assertInstanceOf(ArticleEntity::class, $result);
        $this->assertSame($uuid, $result->getUuid()->toNative());
    }

    #[Test]
    public function makeActualArticleInstanceShouldAssembleEntityFromArticle(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuidVo = Uuid::fromNative($uuid);
        $articleData = [
            'title' => 'Test Title',
            'short_description' => 'Short description.',
            'description' => 'Full description that meets the minimum requirements of fifty characters for validation testing purposes.',
            'slug' => 'test-title',
            'status' => 'draft',
        ];
        $article = Article::fromArray($articleData);

        // Act
        $result = $this->factory->makeActualArticleInstance($uuidVo, $article);

        // Assert
        $this->assertSame('Test Title', $result->getTitle()->toNative());
        $this->assertSame('test-title', $result->getSlug()->toNative());
        $this->assertSame('draft', $result->getStatus()->toNative());
    }

    #[Test]
    public function makeActualArticleInstanceWithCustomFactoriesShouldUseProvidedFactories(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuidVo = Uuid::fromNative($uuid);
        $articleData = [
            'title' => 'Test',
            'status' => 'draft',
        ];
        $article = Article::fromArray($articleData);

        $customEventFactory = \Mockery::mock(EventFactoryInterface::class);
        $customValueObjectFactory = \Mockery::mock(ValueObjectFactoryInterface::class);
        $customSlugGenerator = \Mockery::mock(ArticleSlugGeneratorServiceInterface::class);

        // Act
        $result = $this->factory->makeActualArticleInstance(
            $uuidVo,
            $article,
            $customEventFactory,
            $customValueObjectFactory,
            $customSlugGenerator
        );

        // Assert
        $this->assertInstanceOf(ArticleEntityInterface::class, $result);
    }

    #[Test]
    public function factoryShouldImplementEntityFactoryInterface(): void
    {
        // Assert
        $this->assertInstanceOf(EntityFactoryInterface::class, $this->factory);
    }

    #[Test]
    public function constructorShouldAcceptRequiredDependencies(): void
    {
        // Arrange & Act
        $factory = new EntityFactory(
            $this->eventFactoryMock,
            $this->valueObjectFactoryMock,
            $this->slugGeneratorMock
        );

        // Assert
        $this->assertInstanceOf(EntityFactory::class, $factory);
    }

    #[Test]
    #[DataProviderExternal(EntityFactoryDataProvider::class, 'provideCreateArticleInstanceScenarios')]
    public function createArticleInstanceShouldCreateEntityWithGeneratedUuid(string $processUuid, array $articleData): void
    {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $article = Article::fromArray($articleData);

        // Mock slug generator - ArticleEntity::create() calls generateSlug()
        $this->slugGeneratorMock
            ->shouldReceive('generateSlug')
            ->once()
            ->andReturn($articleData['slug']);

        // Mock valueObjectFactory->makeArticle() - called after slug generation
        $this->valueObjectFactoryMock
            ->shouldReceive('makeArticle')
            ->once()
            ->andReturn($article);

        // Create real event - Broadway requires real event class for apply dispatch
        $generatedUuid = Uuid::fromNative(RamseyUuid::uuid4()->toString());
        $articleCreatedEvent = new ArticleCreatedEvent($processUuidVo, $generatedUuid, $article);
        $this->eventFactoryMock
            ->shouldReceive('makeArticleCreatedEvent')
            ->once()
            ->andReturn($articleCreatedEvent);

        // Act - uuid is null, should generate new one
        $result = $this->factory->createArticleInstance($processUuidVo, $article);

        // Assert
        $this->assertInstanceOf(ArticleEntityInterface::class, $result);
        $this->assertInstanceOf(ArticleEntity::class, $result);
        // UUID should be a valid UUID (auto-generated)
        $this->assertNotEmpty($result->getUuid()->toNative());
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $result->getUuid()->toNative()
        );
    }

    #[Test]
    public function createArticleInstanceWithOurUuidShouldUseProvidedUuid(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $providedUuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $article = Article::fromArray([
            'title' => 'Test Title',
            'short_description' => 'Short description.',
            'description' => 'Full description that meets the minimum requirements of fifty characters for validation testing purposes.',
            'slug' => 'test-title',
            'status' => 'draft',
        ]);

        // Mock slug generator - ArticleEntity::create() calls generateSlug()
        $this->slugGeneratorMock
            ->shouldReceive('generateSlug')
            ->once()
            ->andReturn('test-title');

        // Mock valueObjectFactory->makeArticle() - called after slug generation
        $this->valueObjectFactoryMock
            ->shouldReceive('makeArticle')
            ->once()
            ->andReturn($article);

        // Create real event - Broadway requires real event class for apply dispatch
        $articleCreatedEvent = new ArticleCreatedEvent($processUuid, $providedUuid, $article);
        $this->eventFactoryMock
            ->shouldReceive('makeArticleCreatedEvent')
            ->once()
            ->andReturn($articleCreatedEvent);

        // Act - uuid is our Uuid type
        $result = $this->factory->createArticleInstance($processUuid, $article, $providedUuid);

        // Assert
        $this->assertInstanceOf(ArticleEntityInterface::class, $result);
        $this->assertSame('6ba7b810-9dad-11d1-80b4-00c04fd430c8', $result->getUuid()->toNative());
    }

    #[Test]
    public function createArticleInstanceWithRamseyUuidShouldConvertUuid(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $ramseyUuid = RamseyUuid::fromString('7c9e6679-7425-40de-944b-e07fc1f90ae7');
        $article = Article::fromArray([
            'title' => 'Test Title',
            'short_description' => 'Short description.',
            'description' => 'Full description that meets the minimum requirements of fifty characters for validation testing purposes.',
            'slug' => 'test-title',
            'status' => 'draft',
        ]);

        // Mock slug generator - ArticleEntity::create() calls generateSlug()
        $this->slugGeneratorMock
            ->shouldReceive('generateSlug')
            ->once()
            ->andReturn('test-title');

        // Mock valueObjectFactory->makeArticle() - called after slug generation
        $this->valueObjectFactoryMock
            ->shouldReceive('makeArticle')
            ->once()
            ->andReturn($article);

        // Create real event - Broadway requires real event class for apply dispatch
        // Convert Ramsey UUID to our Uuid type for the event
        $convertedUuid = Uuid::fromNative($ramseyUuid->toString());
        $articleCreatedEvent = new ArticleCreatedEvent($processUuid, $convertedUuid, $article);
        $this->eventFactoryMock
            ->shouldReceive('makeArticleCreatedEvent')
            ->once()
            ->andReturn($articleCreatedEvent);

        // Act - uuid is Ramsey\Uuid\Uuid (not our Uuid), should convert
        $result = $this->factory->createArticleInstance($processUuid, $article, $ramseyUuid);

        // Assert
        $this->assertInstanceOf(ArticleEntityInterface::class, $result);
        $this->assertSame('7c9e6679-7425-40de-944b-e07fc1f90ae7', $result->getUuid()->toNative());
    }

    #[Test]
    public function createArticleInstanceShouldAssembleEntityFromArticle(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $article = Article::fromArray([
            'title' => 'Created Article',
            'short_description' => 'Short description for testing.',
            'description' => 'Full description that meets the minimum requirements of fifty characters for validation testing purposes.',
            'slug' => 'created-article',
            'status' => 'draft',
        ]);

        // Mock slug generator - ArticleEntity::create() calls generateSlug()
        $this->slugGeneratorMock
            ->shouldReceive('generateSlug')
            ->once()
            ->andReturn('created-article');

        // Mock valueObjectFactory->makeArticle() - called after slug generation
        $this->valueObjectFactoryMock
            ->shouldReceive('makeArticle')
            ->once()
            ->andReturn($article);

        // Create real event - Broadway requires real event class for apply dispatch
        $generatedUuid = Uuid::fromNative(RamseyUuid::uuid4()->toString());
        $articleCreatedEvent = new ArticleCreatedEvent($processUuid, $generatedUuid, $article);
        $this->eventFactoryMock
            ->shouldReceive('makeArticleCreatedEvent')
            ->once()
            ->andReturn($articleCreatedEvent);

        // Act
        $result = $this->factory->createArticleInstance($processUuid, $article);

        // Assert
        $this->assertSame('Created Article', $result->getTitle()->toNative());
        $this->assertSame('created-article', $result->getSlug()->toNative());
        $this->assertSame('draft', $result->getStatus()->toNative());
    }

    #[Test]
    public function createArticleInstanceWithCustomFactoriesShouldUseProvidedFactories(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $article = Article::fromArray([
            'title' => 'Test',
            'status' => 'draft',
        ]);

        $customEventFactory = \Mockery::mock(EventFactoryInterface::class);
        $customValueObjectFactory = \Mockery::mock(ValueObjectFactoryInterface::class);
        $customSlugGenerator = \Mockery::mock(ArticleSlugGeneratorServiceInterface::class);

        // Mock slug generator - ArticleEntity::create() calls generateSlug() on custom mock
        $customSlugGenerator
            ->shouldReceive('generateSlug')
            ->once()
            ->andReturn('test');

        // Mock customValueObjectFactory->makeArticle() - called after slug generation
        $customValueObjectFactory
            ->shouldReceive('makeArticle')
            ->once()
            ->andReturn($article);

        // Mock customEventFactory->makeArticleCreatedEvent() - called to create the domain event
        $articleCreatedEvent = \Mockery::mock(ArticleCreatedEvent::class);
        $customEventFactory
            ->shouldReceive('makeArticleCreatedEvent')
            ->once()
            ->andReturn($articleCreatedEvent);

        // Act
        $result = $this->factory->createArticleInstance(
            $processUuid,
            $article,
            null, // uuid
            $customEventFactory,
            $customValueObjectFactory,
            $customSlugGenerator
        );

        // Assert
        $this->assertInstanceOf(ArticleEntityInterface::class, $result);
    }

    #[Test]
    public function createArticleInstanceWithAllParametersShouldCreateEntity(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $article = Article::fromArray([
            'title' => 'Full Parameters Test',
            'short_description' => 'Short description.',
            'description' => 'Full description that meets the minimum requirements of fifty characters for validation testing purposes.',
            'slug' => 'full-parameters-test',
            'event_id' => 12345,
            'status' => 'draft',
        ]);

        $customEventFactory = \Mockery::mock(EventFactoryInterface::class);
        $customValueObjectFactory = \Mockery::mock(ValueObjectFactoryInterface::class);
        $customSlugGenerator = \Mockery::mock(ArticleSlugGeneratorServiceInterface::class);

        // Mock slug generator - ArticleEntity::create() calls generateSlug() on custom mock
        $customSlugGenerator
            ->shouldReceive('generateSlug')
            ->once()
            ->andReturn('full-parameters-test');

        // Mock customValueObjectFactory->makeArticle() - called after slug generation
        $customValueObjectFactory
            ->shouldReceive('makeArticle')
            ->once()
            ->andReturn($article);

        // Create real event - Broadway requires real event class for apply dispatch
        $articleCreatedEvent = new ArticleCreatedEvent($processUuid, $uuid, $article);
        $customEventFactory
            ->shouldReceive('makeArticleCreatedEvent')
            ->once()
            ->andReturn($articleCreatedEvent);

        // Act
        $result = $this->factory->createArticleInstance(
            $processUuid,
            $article,
            $uuid,
            $customEventFactory,
            $customValueObjectFactory,
            $customSlugGenerator
        );

        // Assert
        $this->assertInstanceOf(ArticleEntityInterface::class, $result);
        $this->assertSame('6ba7b810-9dad-11d1-80b4-00c04fd430c8', $result->getUuid()->toNative());
        $this->assertSame('Full Parameters Test', $result->getTitle()->toNative());
    }
}
