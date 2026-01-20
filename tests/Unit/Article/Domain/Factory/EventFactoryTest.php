<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\Factory;

use Micro\Article\Domain\Event\ArticleArchivedEvent;
use Micro\Article\Domain\Event\ArticleCreatedEvent;
use Micro\Article\Domain\Event\ArticleDeletedEvent;
use Micro\Article\Domain\Event\ArticlePublishedEvent;
use Micro\Article\Domain\Event\ArticleUnpublishedEvent;
use Micro\Article\Domain\Event\ArticleUpdatedEvent;
use Micro\Article\Domain\Factory\EventFactory;
use Micro\Article\Domain\ValueObject\ArchivedAt;
use Micro\Article\Domain\ValueObject\Article;
use Micro\Article\Domain\ValueObject\PublishedAt;
use Micro\Article\Domain\ValueObject\Status;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\UpdatedAt;
use MicroModule\Base\Domain\ValueObject\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\Factory\EventFactoryDataProvider;

/**
 * Unit tests for EventFactory.
 */
#[CoversClass(EventFactory::class)]
final class EventFactoryTest extends TestCase
{
    private EventFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new EventFactory();
    }

    #[Test]
    #[DataProviderExternal(EventFactoryDataProvider::class, 'provideArticleCreatedEventScenarios')]
    public function makeArticleCreatedEventShouldCreateEvent(
        string $processUuid,
        string $uuid,
        array $articleData,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $article = Article::fromArray($articleData);

        // Act
        $result = $this->factory->makeArticleCreatedEvent($processUuidVo, $uuidVo, $article);

        // Assert
        $this->assertInstanceOf(ArticleCreatedEvent::class, $result);
        $this->assertSame($processUuid, $result->getProcessUuid()->toNative());
        $this->assertSame($uuid, $result->getUuid()->toNative());
    }

    #[Test]
    #[DataProviderExternal(EventFactoryDataProvider::class, 'provideArticleUpdatedEventScenarios')]
    public function makeArticleUpdatedEventShouldCreateEvent(
        string $processUuid,
        string $uuid,
        array $articleData,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $article = Article::fromArray($articleData);

        // Act
        $result = $this->factory->makeArticleUpdatedEvent($processUuidVo, $uuidVo, $article);

        // Assert
        $this->assertInstanceOf(ArticleUpdatedEvent::class, $result);
        $this->assertSame($processUuid, $result->getProcessUuid()->toNative());
        $this->assertSame($uuid, $result->getUuid()->toNative());
    }

    #[Test]
    #[DataProviderExternal(EventFactoryDataProvider::class, 'provideArticlePublishedEventScenarios')]
    public function makeArticlePublishedEventShouldCreateEvent(
        string $processUuid,
        string $uuid,
        string $status,
        string $publishedAt,
        string $updatedAt,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $statusVo = Status::fromNative($status);
        $publishedAtVo = PublishedAt::fromNative($publishedAt);
        $updatedAtVo = UpdatedAt::fromNative($updatedAt);

        // Act
        $result = $this->factory->makeArticlePublishedEvent(
            $processUuidVo,
            $uuidVo,
            $statusVo,
            $publishedAtVo,
            $updatedAtVo
        );

        // Assert
        $this->assertInstanceOf(ArticlePublishedEvent::class, $result);
        $this->assertSame($status, $result->getStatus()->toNative());
    }

    #[Test]
    #[DataProviderExternal(EventFactoryDataProvider::class, 'provideArticleUnpublishedEventScenarios')]
    public function makeArticleUnpublishedEventShouldCreateEvent(
        string $processUuid,
        string $uuid,
        string $status,
        string $updatedAt,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $statusVo = Status::fromNative($status);
        $updatedAtVo = UpdatedAt::fromNative($updatedAt);

        // Act
        $result = $this->factory->makeArticleUnpublishedEvent($processUuidVo, $uuidVo, $statusVo, $updatedAtVo);

        // Assert
        $this->assertInstanceOf(ArticleUnpublishedEvent::class, $result);
        $this->assertSame($status, $result->getStatus()->toNative());
    }

    #[Test]
    #[DataProviderExternal(EventFactoryDataProvider::class, 'provideArticleArchivedEventScenarios')]
    public function makeArticleArchivedEventShouldCreateEvent(
        string $processUuid,
        string $uuid,
        string $status,
        string $archivedAt,
        string $updatedAt,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $statusVo = Status::fromNative($status);
        $archivedAtVo = ArchivedAt::fromNative($archivedAt);
        $updatedAtVo = UpdatedAt::fromNative($updatedAt);

        // Act
        $result = $this->factory->makeArticleArchivedEvent(
            $processUuidVo,
            $uuidVo,
            $statusVo,
            $archivedAtVo,
            $updatedAtVo
        );

        // Assert
        $this->assertInstanceOf(ArticleArchivedEvent::class, $result);
        $this->assertSame($status, $result->getStatus()->toNative());
    }

    #[Test]
    #[DataProviderExternal(EventFactoryDataProvider::class, 'provideArticleDeletedEventScenarios')]
    public function makeArticleDeletedEventShouldCreateEvent(string $processUuid, string $uuid): void
    {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);

        // Act
        $result = $this->factory->makeArticleDeletedEvent($processUuidVo, $uuidVo);

        // Assert
        $this->assertInstanceOf(ArticleDeletedEvent::class, $result);
        $this->assertSame($processUuid, $result->getProcessUuid()->toNative());
        $this->assertSame($uuid, $result->getUuid()->toNative());
    }

    #[Test]
    public function factoryShouldImplementEventFactoryInterface(): void
    {
        // Assert
        $this->assertInstanceOf(\Micro\Article\Domain\Factory\EventFactoryInterface::class, $this->factory);
    }
}
