<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Infrastructure\Subscriber;

use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Micro\Article\Domain\Event\ArticleArchivedEvent;
use Micro\Article\Domain\Event\ArticleCreatedEvent;
use Micro\Article\Domain\Event\ArticleDeletedEvent;
use Micro\Article\Domain\Event\ArticlePublishedEvent;
use Micro\Article\Domain\Event\ArticleUnpublishedEvent;
use Micro\Article\Domain\Event\ArticleUpdatedEvent;
use Micro\Article\Domain\ValueObject\Article;
use Micro\Article\Domain\ValueObject\Status;
use Micro\Article\Infrastructure\Subscriber\ArticleCacheInvalidationSubscriber;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Tests\Unit\DataProvider\Article\Infrastructure\Subscriber\ArticleCacheInvalidationSubscriberDataProvider;

/**
 * Unit tests for ArticleCacheInvalidationSubscriber.
 *
 * Tests cache invalidation behavior for Broadway domain events.
 */
#[CoversClass(ArticleCacheInvalidationSubscriber::class)]
final class ArticleCacheInvalidationSubscriberTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ArticleCacheInvalidationSubscriber $subscriber;
    private TagAwareCacheInterface&Mockery\MockInterface $readModelCacheMock;
    private TagAwareCacheInterface&Mockery\MockInterface $queryCacheMock;
    private LoggerInterface&Mockery\MockInterface $loggerMock;

    protected function setUp(): void
    {
        $this->readModelCacheMock = \Mockery::mock(TagAwareCacheInterface::class);
        $this->queryCacheMock = \Mockery::mock(TagAwareCacheInterface::class);
        $this->loggerMock = \Mockery::mock(LoggerInterface::class);

        $this->subscriber = new ArticleCacheInvalidationSubscriber(
            $this->readModelCacheMock,
            $this->queryCacheMock,
            $this->loggerMock
        );
    }

    #[Test]
    #[DataProviderExternal(ArticleCacheInvalidationSubscriberDataProvider::class, 'articleCreatedEventScenarios')]
    public function handleShouldInvalidateListCachesOnArticleCreated(
        ArticleCreatedEvent $event,
        array $expectedTags,
        string $uuid,
    ): void {
        // Arrange
        $domainMessage = $this->createDomainMessage($event, $uuid);

        $this->readModelCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with($expectedTags);

        $this->queryCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with($expectedTags);

        $this->loggerMock
            ->shouldReceive('info')
            ->once()
            ->with('Cache invalidated', \Mockery::type('array'));

        // Act
        $this->subscriber->handle($domainMessage);

        // Assert - Mockery verifies expectations in tearDown
        $this->assertTrue(true);
    }

    #[Test]
    #[DataProviderExternal(ArticleCacheInvalidationSubscriberDataProvider::class, 'articleUpdatedEventScenarios')]
    public function handleShouldInvalidateItemAndListCachesOnArticleUpdated(
        ArticleUpdatedEvent $event,
        array $expectedItemTags,
        array $expectedListTags,
        string $uuid,
    ): void {
        // Arrange
        $domainMessage = $this->createDomainMessage($event, $uuid);

        // Item cache invalidation
        $this->readModelCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with($expectedItemTags);

        $this->queryCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with($expectedItemTags);

        // List cache invalidation
        $this->readModelCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with($expectedListTags);

        $this->queryCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with($expectedListTags);

        $this->loggerMock
            ->shouldReceive('info')
            ->once()
            ->with('Cache invalidated', \Mockery::type('array'));

        // Act
        $this->subscriber->handle($domainMessage);

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    #[DataProviderExternal(ArticleCacheInvalidationSubscriberDataProvider::class, 'articlePublishedEventScenarios')]
    public function handleShouldInvalidateItemStatusAndListCachesOnArticlePublished(
        ArticlePublishedEvent $event,
        array $expectedItemTags,
        array $expectedStatusTags,
        array $expectedListTags,
        string $uuid,
    ): void {
        // Arrange
        $domainMessage = $this->createDomainMessage($event, $uuid);

        // Item cache invalidation
        $this->readModelCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with($expectedItemTags);

        $this->queryCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with($expectedItemTags);

        // Status cache invalidation (published and draft)
        foreach ($expectedStatusTags as $statusTag) {
            $this->readModelCacheMock
                ->shouldReceive('invalidateTags')
                ->once()
                ->with($statusTag);

            $this->queryCacheMock
                ->shouldReceive('invalidateTags')
                ->once()
                ->with($statusTag);
        }

        // List cache invalidation
        $this->readModelCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with($expectedListTags);

        $this->queryCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with($expectedListTags);

        $this->loggerMock
            ->shouldReceive('info')
            ->once()
            ->with('Cache invalidated', \Mockery::type('array'));

        // Act
        $this->subscriber->handle($domainMessage);

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    #[DataProviderExternal(ArticleCacheInvalidationSubscriberDataProvider::class, 'articleUnpublishedEventScenarios')]
    public function handleShouldInvalidateItemStatusAndListCachesOnArticleUnpublished(
        ArticleUnpublishedEvent $event,
        array $expectedItemTags,
        array $expectedStatusTags,
        array $expectedListTags,
        string $uuid,
    ): void {
        // Arrange
        $domainMessage = $this->createDomainMessage($event, $uuid);

        // Item cache invalidation
        $this->readModelCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with($expectedItemTags);

        $this->queryCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with($expectedItemTags);

        // Status cache invalidation
        foreach ($expectedStatusTags as $statusTag) {
            $this->readModelCacheMock
                ->shouldReceive('invalidateTags')
                ->once()
                ->with($statusTag);

            $this->queryCacheMock
                ->shouldReceive('invalidateTags')
                ->once()
                ->with($statusTag);
        }

        // List cache invalidation
        $this->readModelCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with($expectedListTags);

        $this->queryCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with($expectedListTags);

        $this->loggerMock
            ->shouldReceive('info')
            ->once()
            ->with('Cache invalidated', \Mockery::type('array'));

        // Act
        $this->subscriber->handle($domainMessage);

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    #[DataProviderExternal(ArticleCacheInvalidationSubscriberDataProvider::class, 'articleArchivedEventScenarios')]
    public function handleShouldInvalidateItemStatusAndListCachesOnArticleArchived(
        ArticleArchivedEvent $event,
        array $expectedItemTags,
        array $expectedStatusTags,
        array $expectedListTags,
        string $uuid,
    ): void {
        // Arrange
        $domainMessage = $this->createDomainMessage($event, $uuid);

        // Item cache invalidation
        $this->readModelCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with($expectedItemTags);

        $this->queryCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with($expectedItemTags);

        // Status cache invalidation (archived)
        foreach ($expectedStatusTags as $statusTag) {
            $this->readModelCacheMock
                ->shouldReceive('invalidateTags')
                ->once()
                ->with($statusTag);

            $this->queryCacheMock
                ->shouldReceive('invalidateTags')
                ->once()
                ->with($statusTag);
        }

        // List cache invalidation
        $this->readModelCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with($expectedListTags);

        $this->queryCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with($expectedListTags);

        $this->loggerMock
            ->shouldReceive('info')
            ->once()
            ->with('Cache invalidated', \Mockery::type('array'));

        // Act
        $this->subscriber->handle($domainMessage);

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    #[DataProviderExternal(ArticleCacheInvalidationSubscriberDataProvider::class, 'articleDeletedEventScenarios')]
    public function handleShouldInvalidateItemAndListCachesOnArticleDeleted(
        ArticleDeletedEvent $event,
        array $expectedItemTags,
        array $expectedListTags,
        string $uuid,
    ): void {
        // Arrange
        $domainMessage = $this->createDomainMessage($event, $uuid);

        // Item cache invalidation
        $this->readModelCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with($expectedItemTags);

        $this->queryCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with($expectedItemTags);

        // List cache invalidation
        $this->readModelCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with($expectedListTags);

        $this->queryCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with($expectedListTags);

        $this->loggerMock
            ->shouldReceive('info')
            ->once()
            ->with('Cache invalidated', \Mockery::type('array'));

        // Act
        $this->subscriber->handle($domainMessage);

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function handleShouldDoNothingForUnrecognizedEvent(): void
    {
        // Arrange
        $unrecognizedEvent = new \stdClass();
        $domainMessage = $this->createDomainMessage($unrecognizedEvent, '550e8400-e29b-41d4-a716-446655440000');

        // No cache invalidation should happen
        $this->readModelCacheMock->shouldNotReceive('invalidateTags');
        $this->queryCacheMock->shouldNotReceive('invalidateTags');
        $this->loggerMock->shouldNotReceive('info');

        // Act
        $this->subscriber->handle($domainMessage);

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function constructorShouldAcceptDependencies(): void
    {
        // Arrange & Act
        $subscriber = new ArticleCacheInvalidationSubscriber(
            $this->readModelCacheMock,
            $this->queryCacheMock,
            $this->loggerMock
        );

        // Assert
        $this->assertInstanceOf(ArticleCacheInvalidationSubscriber::class, $subscriber);
    }

    #[Test]
    public function handleShouldLogCacheInvalidationWithEventDetails(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $processUuid = 'a1234567-e89b-12d3-a456-426614174000';

        // Create event directly to avoid generator issues
        $article = Article::fromNative([
            'title' => 'Test Article Title',
            'slug' => 'test-article-title',
            'short_description' => 'Short description for test article.',
            'description' => 'Full description with at least fifty characters for validation testing purposes.',
            'status' => 'draft',
            'created_at' => new \DateTimeImmutable()
                ->format('Y-m-d H:i:s'),
            'updated_at' => new \DateTimeImmutable()
                ->format('Y-m-d H:i:s'),
        ]);

        $event = new ArticleCreatedEvent(ProcessUuid::fromNative($processUuid), Uuid::fromNative($uuid), $article);

        $domainMessage = $this->createDomainMessage($event, $uuid);

        $this->readModelCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with(['article.list']);

        $this->queryCacheMock
            ->shouldReceive('invalidateTags')
            ->once()
            ->with(['article.list']);

        $this->loggerMock
            ->shouldReceive('info')
            ->once()
            ->with('Cache invalidated', \Mockery::on(function (array $context) use ($uuid): bool {
                return $context['event'] === 'ArticleCreatedEvent'
                    && $context['uuid'] === $uuid
                    && $context['subscriber'] === ArticleCacheInvalidationSubscriber::class;
            }));

        // Act
        $this->subscriber->handle($domainMessage);

        // Assert
        $this->assertTrue(true);
    }

    /**
     * Create a DomainMessage for testing.
     *
     * @param object $event       The event payload
     * @param string $aggregateId The aggregate ID
     */
    private function createDomainMessage(object $event, string $aggregateId): DomainMessage
    {
        return DomainMessage::recordNow($aggregateId, 0, new Metadata([]), $event);
    }
}
