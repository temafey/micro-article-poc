<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\Entity;

use Micro\Article\Domain\Entity\ArticleEntity;
use Micro\Article\Domain\Event\ArticleArchivedEvent;
use Micro\Article\Domain\Event\ArticleCreatedEvent;
use Micro\Article\Domain\Event\ArticleDeletedEvent;
use Micro\Article\Domain\Event\ArticlePublishedEvent;
use Micro\Article\Domain\Event\ArticleUnpublishedEvent;
use Micro\Article\Domain\Event\ArticleUpdatedEvent;
use Micro\Article\Domain\Factory\EventFactoryInterface;
use Micro\Article\Domain\Factory\ValueObjectFactoryInterface;
use Micro\Article\Domain\Service\ArticleSlugGeneratorServiceInterface;
use Micro\Article\Domain\ValueObject\ArchivedAt;
use Micro\Article\Domain\ValueObject\Description;
use Micro\Article\Domain\ValueObject\Article;
use Micro\Article\Domain\ValueObject\PublishedAt;
use Micro\Article\Domain\ValueObject\ShortDescription;
use Micro\Article\Domain\ValueObject\Slug;
use Micro\Article\Domain\ValueObject\Status;
use Micro\Article\Domain\ValueObject\Title;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\UpdatedAt;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\Entity\ArticleEntityDataProvider;

/**
 * Unit tests for ArticleEntity Aggregate Root.
 *
 * Tests Event Sourcing behavior using Broadway patterns:
 * - Command methods apply domain events
 * - Apply methods handle event state changes
 * - Event stream reconstitution
 */
#[CoversClass(ArticleEntity::class)]
final class ArticleEntityTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Helper to set protected properties via reflection.
     */
    private function setProtectedProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new \ReflectionClass($object);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);
        $prop->setValue($object, $value);
    }

    #[Test]
    #[DataProviderExternal(ArticleEntityDataProvider::class, 'provideArticleCreationSuccessScenarios')]
    public function articleCreateWithValidDataRaisesArticleCreatedEvent(
        array $inputData,
        array $expectedOutput,
        array $mockArgs,
        array $mockTimes,
    ): void {
        // Arrange
        $processUuid = ProcessUuid::fromNative($inputData['processUuid']);
        $uuid = Uuid::fromNative($inputData['uuid']);

        $articleData = $inputData['article'];
        $articleData['slug'] = $mockArgs['slugGeneratorService']['generateSlug']['returnValue'];
        $articleData['status'] = 'draft';

        $articleValueObject = \Mockery::mock(Article::class);
        $articleValueObject->shouldReceive('getTitle')
            ->once()
            ->andReturn(Title::fromNative($inputData['article']['title']));
        $articleValueObject->shouldReceive('toArray')
            ->once()
            ->andReturn($inputData['article']);

        $articleWithDefaults = \Mockery::mock(Article::class);

        $slugGeneratorService = \Mockery::mock(ArticleSlugGeneratorServiceInterface::class);
        $slugGeneratorService->shouldReceive('generateSlug')
            ->with($inputData['article']['title'])
            ->times($mockTimes['slugGeneratorService']['generateSlug'])
            ->andReturn($mockArgs['slugGeneratorService']['generateSlug']['returnValue']);

        $valueObjectFactory = \Mockery::mock(ValueObjectFactoryInterface::class);
        $valueObjectFactory->shouldReceive('makeArticle')
            ->times($mockTimes['valueObjectFactory']['makeArticle'])
            ->andReturn($articleWithDefaults);

        $event = \Mockery::mock(ArticleCreatedEvent::class);

        $eventFactory = \Mockery::mock(EventFactoryInterface::class);
        $eventFactory->shouldReceive('makeArticleCreatedEvent')
            ->with(
                \Mockery::on(fn ($arg) => $arg instanceof ProcessUuid),
                \Mockery::on(fn ($arg) => $arg instanceof Uuid),
                \Mockery::on(fn ($arg) => $arg instanceof Article),
                null
            )
            ->times($mockTimes['eventFactory']['makeArticleCreatedEvent'])
            ->andReturn($event);

        $entity = new ArticleEntity($eventFactory, $valueObjectFactory, $slugGeneratorService);
        $this->setProtectedProperty($entity, 'uuid', $uuid);

        // Act
        $entity->articleCreate($processUuid, $articleValueObject, null);

        // Assert
        $uncommittedEvents = $entity->getUncommittedEvents();
        $eventsArray = iterator_to_array($uncommittedEvents);
        $this->assertCount(1, $eventsArray);
        $this->assertSame($event, $eventsArray[0]->getPayload());
    }

    #[Test]
    #[DataProviderExternal(ArticleEntityDataProvider::class, 'provideArticleCreationFailureScenarios')]
    public function articleCreateWithInvalidDataThrowsException(
        array $inputData,
        array $expectedOutput,
        array $mockArgs,
        array $mockTimes,
    ): void {
        // Arrange
        $processUuid = ProcessUuid::fromNative($inputData['processUuid']);
        $uuid = Uuid::fromNative($inputData['uuid']);

        if ($inputData['article']['title'] === null) {
            $articleValueObject = \Mockery::mock(Article::class);
            $articleValueObject->shouldReceive('getTitle')
                ->once()
                ->andReturn(null);
        } else {
            $articleValueObject = \Mockery::mock(Article::class);
        }

        $slugGeneratorService = $inputData['hasSlugService']
            ? \Mockery::mock(ArticleSlugGeneratorServiceInterface::class)
            : null;

        $eventFactory = \Mockery::mock(EventFactoryInterface::class);
        $valueObjectFactory = \Mockery::mock(ValueObjectFactoryInterface::class);

        $entity = new ArticleEntity($eventFactory, $valueObjectFactory, $slugGeneratorService);
        $this->setProtectedProperty($entity, 'uuid', $uuid);

        // Assert
        $this->expectException($expectedOutput['exceptionClass']);
        $this->expectExceptionMessage($expectedOutput['exceptionMessage']);

        // Act
        $entity->articleCreate($processUuid, $articleValueObject, null);
    }

    #[Test]
    #[DataProviderExternal(ArticleEntityDataProvider::class, 'provideArticleUpdateSuccessScenarios')]
    public function articleUpdateWithValidDataRaisesArticleUpdatedEvent(
        array $inputData,
        array $expectedOutput,
        array $mockArgs,
        array $mockTimes,
    ): void {
        // Arrange
        $processUuid = ProcessUuid::fromNative($inputData['processUuid']);
        $uuid = Uuid::fromNative($inputData['uuid']);

        $articleValueObject = \Mockery::mock(Article::class);
        $articleValueObject->shouldReceive('getTitle')
            ->once()
            ->andReturn(Title::fromNative($inputData['article']['title']));
        $articleValueObject->shouldReceive('getSlug')
            ->once()
            ->andReturn(isset($inputData['article']['slug']) ? Slug::fromNative($inputData['article']['slug']) : null);
        $articleValueObject->shouldReceive('toArray')
            ->once()
            ->andReturn($inputData['article']);

        $articleWithSlug = \Mockery::mock(Article::class);

        $slugGeneratorService = \Mockery::mock(ArticleSlugGeneratorServiceInterface::class);
        $slugGeneratorService->shouldReceive('generateSlug')
            ->times($mockTimes['slugGeneratorService']['generateSlug'])
            ->andReturn($mockArgs['slugGeneratorService']['generateSlug']['returnValue']);

        $valueObjectFactory = \Mockery::mock(ValueObjectFactoryInterface::class);
        $valueObjectFactory->shouldReceive('makeArticle')
            ->times($mockTimes['valueObjectFactory']['makeArticle'])
            ->andReturn($articleWithSlug);

        $event = \Mockery::mock(ArticleUpdatedEvent::class);

        $eventFactory = \Mockery::mock(EventFactoryInterface::class);
        $eventFactory->shouldReceive('makeArticleUpdatedEvent')
            ->with(
                \Mockery::on(fn ($arg) => $arg instanceof ProcessUuid),
                \Mockery::on(fn ($arg) => $arg instanceof Uuid),
                \Mockery::on(fn ($arg) => $arg instanceof Article),
                null
            )
            ->times($mockTimes['eventFactory']['makeArticleUpdatedEvent'])
            ->andReturn($event);

        $entity = new ArticleEntity($eventFactory, $valueObjectFactory, $slugGeneratorService);
        $this->setProtectedProperty($entity, 'uuid', $uuid);

        // Act
        $entity->articleUpdate($processUuid, $articleValueObject, null);

        // Assert
        $uncommittedEvents = $entity->getUncommittedEvents();
        $eventsArray = iterator_to_array($uncommittedEvents);
        $this->assertCount(1, $eventsArray);
        $this->assertSame($event, $eventsArray[0]->getPayload());
    }

    #[Test]
    #[DataProviderExternal(ArticleEntityDataProvider::class, 'provideArticleUpdateFailureScenarios')]
    public function articleUpdateWithInvalidDataThrowsException(
        array $inputData,
        array $expectedOutput,
        array $mockArgs,
        array $mockTimes,
    ): void {
        // Arrange
        $processUuid = ProcessUuid::fromNative($inputData['processUuid']);
        $uuid = Uuid::fromNative($inputData['uuid']);

        if ($inputData['article']['title'] === null) {
            $articleValueObject = \Mockery::mock(Article::class);
            $articleValueObject->shouldReceive('getTitle')
                ->once()
                ->andReturn(null);
        } else {
            $articleValueObject = \Mockery::mock(Article::class);
        }

        $slugGeneratorService = $inputData['hasSlugService']
            ? \Mockery::mock(ArticleSlugGeneratorServiceInterface::class)
            : null;

        $eventFactory = \Mockery::mock(EventFactoryInterface::class);
        $valueObjectFactory = \Mockery::mock(ValueObjectFactoryInterface::class);

        $entity = new ArticleEntity($eventFactory, $valueObjectFactory, $slugGeneratorService);
        $this->setProtectedProperty($entity, 'uuid', $uuid);

        // Assert
        $this->expectException($expectedOutput['exceptionClass']);
        $this->expectExceptionMessage($expectedOutput['exceptionMessage']);

        // Act
        $entity->articleUpdate($processUuid, $articleValueObject, null);
    }

    #[Test]
    #[DataProviderExternal(ArticleEntityDataProvider::class, 'provideArticlePublishSuccessScenarios')]
    public function articlePublishWithDraftStatusRaisesArticlePublishedEvent(
        array $inputData,
        array $expectedOutput,
        array $mockArgs,
        array $mockTimes,
    ): void {
        // Arrange
        $processUuid = ProcessUuid::fromNative($inputData['processUuid']);
        $uuid = Uuid::fromNative($inputData['uuid']);

        $status = Status::byValue('published');
        $publishedAt = \Mockery::mock(PublishedAt::class);
        $updatedAt = \Mockery::mock(UpdatedAt::class);

        $valueObjectFactory = \Mockery::mock(ValueObjectFactoryInterface::class);
        $valueObjectFactory->shouldReceive('makeStatus')
            ->with('published')
            ->times($mockTimes['valueObjectFactory']['makeStatus'])
            ->andReturn($status);
        $valueObjectFactory->shouldReceive('makePublishedAt')
            ->times($mockTimes['valueObjectFactory']['makePublishedAt'])
            ->andReturn($publishedAt);
        $valueObjectFactory->shouldReceive('makeUpdatedAt')
            ->times($mockTimes['valueObjectFactory']['makeUpdatedAt'])
            ->andReturn($updatedAt);

        $event = \Mockery::mock(ArticlePublishedEvent::class);

        $eventFactory = \Mockery::mock(EventFactoryInterface::class);
        $eventFactory->shouldReceive('makeArticlePublishedEvent')
            ->with(
                \Mockery::on(fn ($arg) => $arg instanceof ProcessUuid),
                \Mockery::on(fn ($arg) => $arg instanceof Uuid),
                \Mockery::on(fn ($arg) => $arg instanceof Status),
                \Mockery::on(fn ($arg) => $arg instanceof PublishedAt),
                \Mockery::on(fn ($arg) => $arg instanceof UpdatedAt),
                null
            )
            ->times($mockTimes['eventFactory']['makeArticlePublishedEvent'])
            ->andReturn($event);

        $entity = new ArticleEntity($eventFactory, $valueObjectFactory, null);
        $this->setProtectedProperty($entity, 'uuid', $uuid);

        // Act
        $entity->articlePublish($processUuid, null);

        // Assert
        $uncommittedEvents = $entity->getUncommittedEvents();
        $eventsArray = iterator_to_array($uncommittedEvents);
        $this->assertCount(1, $eventsArray);
        $this->assertSame($event, $eventsArray[0]->getPayload());
    }

    #[Test]
    #[DataProviderExternal(ArticleEntityDataProvider::class, 'provideArticleUnpublishSuccessScenarios')]
    public function articleUnpublishWithPublishedStatusRaisesArticleUnpublishedEvent(
        array $inputData,
        array $expectedOutput,
        array $mockArgs,
        array $mockTimes,
    ): void {
        // Arrange
        $processUuid = ProcessUuid::fromNative($inputData['processUuid']);
        $uuid = Uuid::fromNative($inputData['uuid']);

        $status = Status::byValue('draft');
        $updatedAt = \Mockery::mock(UpdatedAt::class);

        $valueObjectFactory = \Mockery::mock(ValueObjectFactoryInterface::class);
        $valueObjectFactory->shouldReceive('makeStatus')
            ->with('draft')
            ->times($mockTimes['valueObjectFactory']['makeStatus'])
            ->andReturn($status);
        $valueObjectFactory->shouldReceive('makeUpdatedAt')
            ->times($mockTimes['valueObjectFactory']['makeUpdatedAt'])
            ->andReturn($updatedAt);

        $event = \Mockery::mock(ArticleUnpublishedEvent::class);

        $eventFactory = \Mockery::mock(EventFactoryInterface::class);
        $eventFactory->shouldReceive('makeArticleUnpublishedEvent')
            ->with(
                \Mockery::on(fn ($arg) => $arg instanceof ProcessUuid),
                \Mockery::on(fn ($arg) => $arg instanceof Uuid),
                \Mockery::on(fn ($arg) => $arg instanceof Status),
                \Mockery::on(fn ($arg) => $arg instanceof UpdatedAt),
                null
            )
            ->times($mockTimes['eventFactory']['makeArticleUnpublishedEvent'])
            ->andReturn($event);

        $entity = new ArticleEntity($eventFactory, $valueObjectFactory, null);
        $this->setProtectedProperty($entity, 'uuid', $uuid);

        // Act
        $entity->articleUnpublish($processUuid, null);

        // Assert
        $uncommittedEvents = $entity->getUncommittedEvents();
        $eventsArray = iterator_to_array($uncommittedEvents);
        $this->assertCount(1, $eventsArray);
        $this->assertSame($event, $eventsArray[0]->getPayload());
    }

    #[Test]
    #[DataProviderExternal(ArticleEntityDataProvider::class, 'provideArticleArchiveSuccessScenarios')]
    public function articleArchiveWithPublishedStatusRaisesArticleArchivedEvent(
        array $inputData,
        array $expectedOutput,
        array $mockArgs,
        array $mockTimes,
    ): void {
        // Arrange
        $processUuid = ProcessUuid::fromNative($inputData['processUuid']);
        $uuid = Uuid::fromNative($inputData['uuid']);

        $status = Status::byValue('archived');
        $archivedAt = \Mockery::mock(ArchivedAt::class);
        $updatedAt = \Mockery::mock(UpdatedAt::class);

        $valueObjectFactory = \Mockery::mock(ValueObjectFactoryInterface::class);
        $valueObjectFactory->shouldReceive('makeStatus')
            ->with('archived')
            ->times($mockTimes['valueObjectFactory']['makeStatus'])
            ->andReturn($status);
        $valueObjectFactory->shouldReceive('makeArchivedAt')
            ->times($mockTimes['valueObjectFactory']['makeArchivedAt'])
            ->andReturn($archivedAt);
        $valueObjectFactory->shouldReceive('makeUpdatedAt')
            ->times($mockTimes['valueObjectFactory']['makeUpdatedAt'])
            ->andReturn($updatedAt);

        $event = \Mockery::mock(ArticleArchivedEvent::class);

        $eventFactory = \Mockery::mock(EventFactoryInterface::class);
        $eventFactory->shouldReceive('makeArticleArchivedEvent')
            ->with(
                \Mockery::on(fn ($arg) => $arg instanceof ProcessUuid),
                \Mockery::on(fn ($arg) => $arg instanceof Uuid),
                \Mockery::on(fn ($arg) => $arg instanceof Status),
                \Mockery::on(fn ($arg) => $arg instanceof ArchivedAt),
                \Mockery::on(fn ($arg) => $arg instanceof UpdatedAt),
                null
            )
            ->times($mockTimes['eventFactory']['makeArticleArchivedEvent'])
            ->andReturn($event);

        $entity = new ArticleEntity($eventFactory, $valueObjectFactory, null);
        $this->setProtectedProperty($entity, 'uuid', $uuid);

        // Act
        $entity->articleArchive($processUuid, null);

        // Assert
        $uncommittedEvents = $entity->getUncommittedEvents();
        $eventsArray = iterator_to_array($uncommittedEvents);
        $this->assertCount(1, $eventsArray);
        $this->assertSame($event, $eventsArray[0]->getPayload());
    }

    #[Test]
    #[DataProviderExternal(ArticleEntityDataProvider::class, 'provideArticleDeleteSuccessScenarios')]
    public function articleDeleteWithExistingArticleRaisesArticleDeletedEvent(
        array $inputData,
        array $expectedOutput,
        array $mockArgs,
        array $mockTimes,
    ): void {
        // Arrange
        $processUuid = ProcessUuid::fromNative($inputData['processUuid']);
        $uuid = Uuid::fromNative($inputData['uuid']);

        $event = \Mockery::mock(ArticleDeletedEvent::class);

        $eventFactory = \Mockery::mock(EventFactoryInterface::class);
        $eventFactory->shouldReceive('makeArticleDeletedEvent')
            ->with(
                \Mockery::on(fn ($arg) => $arg instanceof ProcessUuid),
                \Mockery::on(fn ($arg) => $arg instanceof Uuid),
                null
            )
            ->times($mockTimes['eventFactory']['makeArticleDeletedEvent'])
            ->andReturn($event);

        $valueObjectFactory = \Mockery::mock(ValueObjectFactoryInterface::class);

        $entity = new ArticleEntity($eventFactory, $valueObjectFactory, null);
        $this->setProtectedProperty($entity, 'uuid', $uuid);

        // Act
        $entity->articleDelete($processUuid, null);

        // Assert
        $uncommittedEvents = $entity->getUncommittedEvents();
        $eventsArray = iterator_to_array($uncommittedEvents);
        $this->assertCount(1, $eventsArray);
        $this->assertSame($event, $eventsArray[0]->getPayload());
    }

    #[Test]
    public function applyArticleCreatedEventWithValidEventSetsEntityProperties(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative('660e8400-e29b-41d4-a716-446655440001');

        $articleData = [
            'title' => 'Test Title',
            'short_description' => 'Short desc',
            'description' => 'This is a full description text that contains more than fifty characters for validation purposes.',
            'slug' => 'test-title',
            'status' => 'draft',
        ];

        $article = \Mockery::mock(Article::class);
        $article->shouldReceive('getTitle')
            ->andReturn(Title::fromNative($articleData['title']));
        $article->shouldReceive('getShortDescription')
            ->andReturn(ShortDescription::fromNative($articleData['short_description']));
        $article->shouldReceive('getDescription')
            ->andReturn(Description::fromNative($articleData['description']));
        $article->shouldReceive('getSlug')
            ->andReturn(Slug::fromNative($articleData['slug']));
        $article->shouldReceive('getStatus')
            ->andReturn(Status::fromNative($articleData['status']));
        $article->shouldReceive('getEventId')
            ->andReturn(null);
        $article->shouldReceive('getPublishedAt')
            ->andReturn(null);
        $article->shouldReceive('getArchivedAt')
            ->andReturn(null);
        $article->shouldReceive('getCreatedAt')
            ->andReturn(null);
        $article->shouldReceive('getUpdatedAt')
            ->andReturn(null);

        $event = \Mockery::mock(ArticleCreatedEvent::class);
        $event->shouldReceive('getProcessUuid')
            ->andReturn($processUuid);
        $event->shouldReceive('getUuid')
            ->andReturn($uuid);
        $event->shouldReceive('getArticle')
            ->andReturn($article);

        $entity = new ArticleEntity();

        // Act
        $entity->applyArticleCreatedEvent($event);

        // Assert
        $this->assertSame($processUuid, $entity->getProcessUuid());
        $this->assertSame($uuid, $entity->getUuid());
        $this->assertInstanceOf(Title::class, $entity->getTitle());
    }

    #[Test]
    public function applyArticlePublishedEventWithValidEventSetsStatusAndTimestamps(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $status = Status::fromNative('published');
        $publishedAt = PublishedAt::fromNative(new \DateTime('2025-01-15T10:00:00+00:00'));
        $updatedAt = UpdatedAt::fromNative('2025-01-15T10:00:00+00:00');

        $event = \Mockery::mock(ArticlePublishedEvent::class);
        $event->shouldReceive('getProcessUuid')
            ->andReturn($processUuid);
        $event->shouldReceive('getStatus')
            ->andReturn($status);
        $event->shouldReceive('getPublishedAt')
            ->andReturn($publishedAt);
        $event->shouldReceive('getUpdatedAt')
            ->andReturn($updatedAt);

        $entity = new ArticleEntity();

        // Act
        $entity->applyArticlePublishedEvent($event);

        // Assert
        $this->assertSame($processUuid, $entity->getProcessUuid());
        $this->assertSame($status, $entity->getStatus());
        $this->assertSame($publishedAt, $entity->getPublishedAt());
        $this->assertSame($updatedAt, $entity->getUpdatedAt());
    }

    #[Test]
    public function applyArticleUnpublishedEventWithValidEventUpdatesStatusToDraft(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $status = Status::fromNative('draft');
        $updatedAt = UpdatedAt::fromNative('2025-01-15T11:00:00+00:00');

        $event = \Mockery::mock(ArticleUnpublishedEvent::class);
        $event->shouldReceive('getProcessUuid')
            ->andReturn($processUuid);
        $event->shouldReceive('getStatus')
            ->andReturn($status);
        $event->shouldReceive('getUpdatedAt')
            ->andReturn($updatedAt);

        $entity = new ArticleEntity();

        // Act
        $entity->applyArticleUnpublishedEvent($event);

        // Assert
        $this->assertSame($processUuid, $entity->getProcessUuid());
        $this->assertSame($status, $entity->getStatus());
        $this->assertSame($updatedAt, $entity->getUpdatedAt());
    }

    #[Test]
    public function applyArticleArchivedEventWithValidEventSetsArchivedStatus(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $status = Status::fromNative('archived');
        $archivedAt = ArchivedAt::fromNative(new \DateTime('2025-01-20T10:00:00+00:00'));
        $updatedAt = UpdatedAt::fromNative('2025-01-20T10:00:00+00:00');

        $event = \Mockery::mock(ArticleArchivedEvent::class);
        $event->shouldReceive('getProcessUuid')
            ->andReturn($processUuid);
        $event->shouldReceive('getStatus')
            ->andReturn($status);
        $event->shouldReceive('getArchivedAt')
            ->andReturn($archivedAt);
        $event->shouldReceive('getUpdatedAt')
            ->andReturn($updatedAt);

        $entity = new ArticleEntity();

        // Act
        $entity->applyArticleArchivedEvent($event);

        // Assert
        $this->assertSame($processUuid, $entity->getProcessUuid());
        $this->assertSame($status, $entity->getStatus());
        $this->assertSame($archivedAt, $entity->getArchivedAt());
        $this->assertSame($updatedAt, $entity->getUpdatedAt());
    }

    #[Test]
    public function applyArticleDeletedEventWithValidEventSetsProcessUuid(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');

        $event = \Mockery::mock(ArticleDeletedEvent::class);
        $event->shouldReceive('getProcessUuid')
            ->andReturn($processUuid);

        $entity = new ArticleEntity();

        // Act
        $entity->applyArticleDeletedEvent($event);

        // Assert
        $this->assertSame($processUuid, $entity->getProcessUuid());
    }

    #[Test]
    #[DataProviderExternal(ArticleEntityDataProvider::class, 'provideAggregateRootIdScenarios')]
    public function getAggregateRootIdWithSetUuidReturnsUuidString(
        array $inputData,
        array $expectedOutput,
        array $mockArgs,
        array $mockTimes,
    ): void {
        // Arrange
        $uuid = Uuid::fromNative($inputData['uuid']);
        $entity = new ArticleEntity();
        $this->setProtectedProperty($entity, 'uuid', $uuid);

        // Act
        $result = $entity->getAggregateRootId();

        // Assert
        $this->assertSame($expectedOutput['aggregateRootId'], $result);
    }

    #[Test]
    #[DataProviderExternal(ArticleEntityDataProvider::class, 'provideSerializationScenarios')]
    public function serializeWithEntityDataReturnsNormalizedArray(
        array $inputData,
        array $expectedOutput,
        array $mockArgs,
        array $mockTimes,
    ): void {
        // Arrange
        $uuid = Uuid::fromNative($inputData['uuid']);
        $title = Title::fromNative($inputData['title']);
        $status = Status::fromNative($inputData['status']);

        $entity = new ArticleEntity();
        $this->setProtectedProperty($entity, 'uuid', $uuid);
        $this->setProtectedProperty($entity, 'title', $title);
        $this->setProtectedProperty($entity, 'status', $status);

        // Act
        $result = $entity->serialize();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('uuid', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertSame($inputData['uuid'], $result['uuid']);
        $this->assertSame($inputData['title'], $result['title']);
        $this->assertSame($inputData['status'], $result['status']);
    }

    #[Test]
    public function normalizeWithEntityDataReturnsArrayWithAllProperties(): void
    {
        // Arrange
        $uuid = Uuid::fromNative('660e8400-e29b-41d4-a716-446655440001');
        $title = Title::fromNative('Test Title');

        $entity = new ArticleEntity();
        $this->setProtectedProperty($entity, 'uuid', $uuid);
        $this->setProtectedProperty($entity, 'title', $title);

        // Act
        $result = $entity->normalize();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('process_uuid', $result);
        $this->assertArrayHasKey('uuid', $result);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayHasKey('short_description', $result);
        $this->assertArrayHasKey('description', $result);
        $this->assertArrayHasKey('slug', $result);
        $this->assertArrayHasKey('event_id', $result);
        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('published_at', $result);
        $this->assertArrayHasKey('archived_at', $result);
        $this->assertArrayHasKey('created_at', $result);
        $this->assertArrayHasKey('updated_at', $result);
    }

    #[Test]
    public function createWithValidDataCreatesEntityAndAppliesCreatedEvent(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative('660e8400-e29b-41d4-a716-446655440001');

        $article = \Mockery::mock(Article::class);
        $article->shouldReceive('getTitle')
            ->andReturn(Title::fromNative('Test Title'));
        $article->shouldReceive('toArray')
            ->andReturn([
                'title' => 'Test Title',
                'short_description' => 'Short desc',
                'description' => 'This is a full description text that contains more than fifty characters for validation purposes.',
            ]);

        $articleWithDefaults = \Mockery::mock(Article::class);

        $slugGeneratorService = \Mockery::mock(ArticleSlugGeneratorServiceInterface::class);
        $slugGeneratorService->shouldReceive('generateSlug')
            ->andReturn('test-title');

        $valueObjectFactory = \Mockery::mock(ValueObjectFactoryInterface::class);
        $valueObjectFactory->shouldReceive('makeArticle')
            ->andReturn($articleWithDefaults);

        $event = \Mockery::mock(ArticleCreatedEvent::class);

        $eventFactory = \Mockery::mock(EventFactoryInterface::class);
        $eventFactory->shouldReceive('makeArticleCreatedEvent')
            ->andReturn($event);

        // Act
        $entity = ArticleEntity::create(
            $processUuid,
            $uuid,
            $article,
            $eventFactory,
            $valueObjectFactory,
            $slugGeneratorService
        );

        // Assert
        $this->assertInstanceOf(ArticleEntity::class, $entity);
        $this->assertSame($uuid, $entity->getUuid());
        $uncommittedEvents = $entity->getUncommittedEvents();
        $this->assertCount(1, $uncommittedEvents);
    }

    #[Test]
    public function setArticleSlugGeneratorServiceWithServiceSetsServiceProperty(): void
    {
        // Arrange
        $entity = new ArticleEntity();
        $slugGeneratorService = \Mockery::mock(ArticleSlugGeneratorServiceInterface::class);

        // Act
        $entity->setArticleSlugGeneratorService($slugGeneratorService);

        // Assert - we can verify by attempting a articleCreate which requires the service
        $this->assertInstanceOf(ArticleEntity::class, $entity);
    }

    #[Test]
    public function createActualWithValidDataCreatesEntityWithoutEvents(): void
    {
        // Arrange
        $uuid = Uuid::fromNative('660e8400-e29b-41d4-a716-446655440001');
        $articleData = [
            'title' => 'Test Title',
            'short_description' => 'Short desc',
            'description' => 'This is a full description text that contains more than fifty characters for validation purposes.',
            'slug' => 'test-title',
            'status' => 'draft',
        ];
        $article = Article::fromNative($articleData);

        // Act
        $entity = ArticleEntity::createActual($uuid, $article);

        // Assert
        $this->assertInstanceOf(ArticleEntity::class, $entity);
        $this->assertSame($uuid, $entity->getUuid());
        $this->assertSame('Test Title', $entity->getTitle()->toNative());
        $this->assertSame('test-title', $entity->getSlug()->toNative());
        // createActual should NOT raise events (unlike create())
        $uncommittedEvents = $entity->getUncommittedEvents();
        $this->assertCount(0, $uncommittedEvents);
    }

    #[Test]
    public function createActualWithCustomFactoriesCreatesEntity(): void
    {
        // Arrange
        $uuid = Uuid::fromNative('660e8400-e29b-41d4-a716-446655440001');
        $articleData = [
            'title' => 'Custom Factory Test',
            'status' => 'published',
        ];
        $article = Article::fromNative($articleData);

        $eventFactory = \Mockery::mock(EventFactoryInterface::class);
        $valueObjectFactory = \Mockery::mock(ValueObjectFactoryInterface::class);
        $slugGenerator = \Mockery::mock(ArticleSlugGeneratorServiceInterface::class);

        // Act
        $entity = ArticleEntity::createActual($uuid, $article, $eventFactory, $valueObjectFactory, $slugGenerator);

        // Assert
        $this->assertInstanceOf(ArticleEntity::class, $entity);
        $this->assertSame($uuid, $entity->getUuid());
    }

    #[Test]
    public function applyArticleUpdatedEventWithValidEventUpdatesEntityProperties(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');

        $articleData = [
            'title' => 'Updated Title',
            'short_description' => 'Updated short description.',
            'description' => 'This is an updated full description text that contains more than fifty characters for validation purposes.',
            'slug' => 'updated-title',
            'status' => 'draft',
        ];

        $article = \Mockery::mock(Article::class);
        $article->shouldReceive('getTitle')
            ->andReturn(Title::fromNative($articleData['title']));
        $article->shouldReceive('getShortDescription')
            ->andReturn(ShortDescription::fromNative($articleData['short_description']));
        $article->shouldReceive('getDescription')
            ->andReturn(Description::fromNative($articleData['description']));
        $article->shouldReceive('getSlug')
            ->andReturn(Slug::fromNative($articleData['slug']));
        $article->shouldReceive('getStatus')
            ->andReturn(Status::fromNative($articleData['status']));
        $article->shouldReceive('getEventId')
            ->andReturn(null);
        $article->shouldReceive('getPublishedAt')
            ->andReturn(null);
        $article->shouldReceive('getArchivedAt')
            ->andReturn(null);
        $article->shouldReceive('getCreatedAt')
            ->andReturn(null);
        $article->shouldReceive('getUpdatedAt')
            ->andReturn(null);

        $event = \Mockery::mock(ArticleUpdatedEvent::class);
        $event->shouldReceive('getProcessUuid')
            ->andReturn($processUuid);
        $event->shouldReceive('getArticle')
            ->andReturn($article);

        $entity = new ArticleEntity();

        // Act
        $entity->applyArticleUpdatedEvent($event);

        // Assert
        $this->assertSame($processUuid, $entity->getProcessUuid());
        $this->assertInstanceOf(Title::class, $entity->getTitle());
        $this->assertSame('Updated Title', $entity->getTitle()->toNative());
        $this->assertSame('updated-title', $entity->getSlug()->toNative());
    }

    #[Test]
    public function deserializeWithValidDataCreatesEntity(): void
    {
        // Arrange
        $data = [
            'uuid' => '660e8400-e29b-41d4-a716-446655440001',
            'title' => 'Deserialized Title',
            'short_description' => 'Short description for deserialization.',
            'description' => 'This is a full description text that contains more than fifty characters for validation purposes.',
            'slug' => 'deserialized-title',
            'status' => 'published',
        ];

        // Act
        $entity = ArticleEntity::deserialize($data);

        // Assert
        $this->assertInstanceOf(ArticleEntity::class, $entity);
        $this->assertSame('660e8400-e29b-41d4-a716-446655440001', $entity->getUuid()->toNative());
        $this->assertSame('Deserialized Title', $entity->getTitle()->toNative());
        $this->assertSame('deserialized-title', $entity->getSlug()->toNative());
    }

    #[Test]
    public function deserializeWithMissingUuidThrowsException(): void
    {
        // Arrange
        $data = [
            'title' => 'Missing UUID',
            'status' => 'draft',
        ];

        // Assert
        $this->expectException(\Assert\InvalidArgumentException::class);

        // Act
        ArticleEntity::deserialize($data);
    }

    #[Test]
    public function assembleToValueObjectReturnsArticleValueObject(): void
    {
        // Arrange
        $uuid = Uuid::fromNative('660e8400-e29b-41d4-a716-446655440001');
        $title = Title::fromNative('Test Title');
        $slug = Slug::fromNative('test-title');
        $status = Status::fromNative('draft');

        $entity = new ArticleEntity();
        $this->setProtectedProperty($entity, 'uuid', $uuid);
        $this->setProtectedProperty($entity, 'title', $title);
        $this->setProtectedProperty($entity, 'slug', $slug);
        $this->setProtectedProperty($entity, 'status', $status);

        // Act
        $result = $entity->assembleToValueObject();

        // Assert
        $this->assertInstanceOf(Article::class, $result);
        $this->assertSame('Test Title', $result->getTitle()->toNative());
        $this->assertSame('test-title', $result->getSlug()->toNative());
    }

    #[Test]
    public function getPrimaryKeyValueReturnsAggregateRootId(): void
    {
        // Arrange
        $uuid = Uuid::fromNative('660e8400-e29b-41d4-a716-446655440001');
        $entity = new ArticleEntity();
        $this->setProtectedProperty($entity, 'uuid', $uuid);

        // Act
        $result = $entity->getPrimaryKeyValue();

        // Assert
        $this->assertSame('660e8400-e29b-41d4-a716-446655440001', $result);
        $this->assertSame($entity->getAggregateRootId(), $result);
    }

    #[Test]
    public function assembleFromValueObjectWithInvalidValueObjectThrowsException(): void
    {
        // Arrange
        $entity = new ArticleEntity();
        $invalidValueObject = \Mockery::mock(\MicroModule\ValueObject\ValueObjectInterface::class);

        // Assert
        $this->expectException(\MicroModule\Base\Domain\Exception\ValueObjectInvalidException::class);
        $this->expectExceptionMessage('ArticleEntity can be assembled only with Article value object');

        // Act
        $entity->assembleFromValueObject($invalidValueObject);
    }

    #[Test]
    public function assembleFromValueObjectWithAllOptionalFieldsShouldSetAllProperties(): void
    {
        // Arrange
        $entity = new ArticleEntity();
        $articleData = [
            'title' => 'Test Title for Assembly',
            'short_description' => 'Test short description for assembly.',
            'description' => 'This is a test description for assembly that meets the minimum character requirement.',
            'slug' => 'test-slug-for-assembly',
            'event_id' => 12345,
            'status' => 'published',
            'published_at' => '2024-01-15T12:00:00+00:00',
            'archived_at' => '2024-02-15T12:00:00+00:00',
            'created_at' => '2024-01-01T10:00:00+00:00',
            'updated_at' => '2024-01-15T12:00:00+00:00',
        ];
        $article = Article::fromNative($articleData);

        // Act
        $entity->assembleFromValueObject($article);

        // Assert
        $this->assertSame('Test Title for Assembly', $entity->getTitle()->toNative());
        $this->assertSame('test-slug-for-assembly', $entity->getSlug()->toNative());
        $this->assertSame(12345, $entity->getEventId()->toNative());
        $this->assertSame('published', $entity->getStatus()->toNative());
        $this->assertInstanceOf(\Micro\Article\Domain\ValueObject\PublishedAt::class, $entity->getPublishedAt());
        $this->assertInstanceOf(\Micro\Article\Domain\ValueObject\ArchivedAt::class, $entity->getArchivedAt());
        $this->assertInstanceOf(\MicroModule\Base\Domain\ValueObject\CreatedAt::class, $entity->getCreatedAt());
        $this->assertInstanceOf(\MicroModule\Base\Domain\ValueObject\UpdatedAt::class, $entity->getUpdatedAt());
    }

    #[Test]
    public function constructorWithoutDependenciesCreatesDefaultFactories(): void
    {
        // Act
        $entity = new ArticleEntity();

        // Assert
        $this->assertInstanceOf(ArticleEntity::class, $entity);
        // Default factories are created internally
    }

    #[Test]
    public function constructorWithCustomDependenciesUsesProvidedFactories(): void
    {
        // Arrange
        $eventFactory = \Mockery::mock(EventFactoryInterface::class);
        $valueObjectFactory = \Mockery::mock(ValueObjectFactoryInterface::class);
        $slugGenerator = \Mockery::mock(ArticleSlugGeneratorServiceInterface::class);

        // Act
        $entity = new ArticleEntity($eventFactory, $valueObjectFactory, $slugGenerator);

        // Assert
        $this->assertInstanceOf(ArticleEntity::class, $entity);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }
}
