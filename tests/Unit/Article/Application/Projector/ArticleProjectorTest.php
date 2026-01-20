<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\Projector;

use Micro\Article\Application\Projector\ArticleProjector;
use Micro\Article\Domain\Entity\ArticleEntity;
use Micro\Article\Domain\Event\ArticleArchivedEvent;
use Micro\Article\Domain\Event\ArticleCreatedEvent;
use Micro\Article\Domain\Event\ArticleDeletedEvent;
use Micro\Article\Domain\Event\ArticlePublishedEvent;
use Micro\Article\Domain\Event\ArticleUnpublishedEvent;
use Micro\Article\Domain\Event\ArticleUpdatedEvent;
use Micro\Article\Domain\Factory\ReadModelFactoryInterface;
use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use Micro\Article\Domain\Repository\ReadModel\ArticleRepositoryInterface;
use Micro\Article\Infrastructure\Repository\EntityStore\ArticleRepository;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ArticleProjector.
 */
#[CoversClass(ArticleProjector::class)]
final class ArticleProjectorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ArticleProjector $projector;
    private ArticleRepository&Mockery\MockInterface $entityStoreMock;
    private ArticleRepositoryInterface&Mockery\MockInterface $readModelStoreMock;
    private ReadModelFactoryInterface&Mockery\MockInterface $readModelFactoryMock;

    protected function setUp(): void
    {
        $this->entityStoreMock = \Mockery::mock(ArticleRepository::class);
        $this->readModelStoreMock = \Mockery::mock(ArticleRepositoryInterface::class);
        $this->readModelFactoryMock = \Mockery::mock(ReadModelFactoryInterface::class);

        $this->projector = new ArticleProjector(
            $this->entityStoreMock,
            $this->readModelStoreMock,
            $this->readModelFactoryMock
        );
    }

    #[Test]
    public function applyArticleCreatedEventShouldAddReadModel(): void
    {
        // Arrange
        $uuid = Uuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $eventMock = \Mockery::mock(ArticleCreatedEvent::class);
        $eventMock->shouldReceive('getUuid')
            ->andReturn($uuid);

        $entityMock = \Mockery::mock(ArticleEntity::class);
        $readModelMock = \Mockery::mock(ArticleReadModelInterface::class);

        $this->entityStoreMock
            ->shouldReceive('get')
            ->once()
            ->with($uuid)
            ->andReturn($entityMock);

        $this->readModelFactoryMock
            ->shouldReceive('makeArticleActualInstanceByEntity')
            ->once()
            ->with($entityMock)
            ->andReturn($readModelMock);

        $this->readModelStoreMock
            ->shouldReceive('add')
            ->once()
            ->with($readModelMock);

        // Act
        $this->projector->applyArticleCreatedEvent($eventMock);

        // Assert - implicit through Mockery expectations
        $this->assertTrue(true);
    }

    #[Test]
    public function applyArticleUpdatedEventShouldUpdateReadModel(): void
    {
        // Arrange
        $uuid = Uuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $eventMock = \Mockery::mock(ArticleUpdatedEvent::class);
        $eventMock->shouldReceive('getUuid')
            ->andReturn($uuid);

        $entityMock = \Mockery::mock(ArticleEntity::class);
        $readModelMock = \Mockery::mock(ArticleReadModelInterface::class);

        $this->entityStoreMock
            ->shouldReceive('get')
            ->once()
            ->with($uuid)
            ->andReturn($entityMock);

        $this->readModelFactoryMock
            ->shouldReceive('makeArticleActualInstanceByEntity')
            ->once()
            ->with($entityMock)
            ->andReturn($readModelMock);

        $this->readModelStoreMock
            ->shouldReceive('update')
            ->once()
            ->with($readModelMock);

        // Act
        $this->projector->applyArticleUpdatedEvent($eventMock);

        // Assert - implicit through Mockery expectations
        $this->assertTrue(true);
    }

    #[Test]
    public function applyArticlePublishedEventShouldUpdateReadModel(): void
    {
        // Arrange
        $uuid = Uuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $eventMock = \Mockery::mock(ArticlePublishedEvent::class);
        $eventMock->shouldReceive('getUuid')
            ->andReturn($uuid);

        $entityMock = \Mockery::mock(ArticleEntity::class);
        $readModelMock = \Mockery::mock(ArticleReadModelInterface::class);

        $this->entityStoreMock
            ->shouldReceive('get')
            ->once()
            ->with($uuid)
            ->andReturn($entityMock);

        $this->readModelFactoryMock
            ->shouldReceive('makeArticleActualInstanceByEntity')
            ->once()
            ->with($entityMock)
            ->andReturn($readModelMock);

        $this->readModelStoreMock
            ->shouldReceive('update')
            ->once()
            ->with($readModelMock);

        // Act
        $this->projector->applyArticlePublishedEvent($eventMock);

        // Assert - implicit through Mockery expectations
        $this->assertTrue(true);
    }

    #[Test]
    public function applyArticleUnpublishedEventShouldUpdateReadModel(): void
    {
        // Arrange
        $uuid = Uuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $eventMock = \Mockery::mock(ArticleUnpublishedEvent::class);
        $eventMock->shouldReceive('getUuid')
            ->andReturn($uuid);

        $entityMock = \Mockery::mock(ArticleEntity::class);
        $readModelMock = \Mockery::mock(ArticleReadModelInterface::class);

        $this->entityStoreMock
            ->shouldReceive('get')
            ->once()
            ->with($uuid)
            ->andReturn($entityMock);

        $this->readModelFactoryMock
            ->shouldReceive('makeArticleActualInstanceByEntity')
            ->once()
            ->with($entityMock)
            ->andReturn($readModelMock);

        $this->readModelStoreMock
            ->shouldReceive('update')
            ->once()
            ->with($readModelMock);

        // Act
        $this->projector->applyArticleUnpublishedEvent($eventMock);

        // Assert - implicit through Mockery expectations
        $this->assertTrue(true);
    }

    #[Test]
    public function applyArticleArchivedEventShouldUpdateReadModel(): void
    {
        // Arrange
        $uuid = Uuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $eventMock = \Mockery::mock(ArticleArchivedEvent::class);
        $eventMock->shouldReceive('getUuid')
            ->andReturn($uuid);

        $entityMock = \Mockery::mock(ArticleEntity::class);
        $readModelMock = \Mockery::mock(ArticleReadModelInterface::class);

        $this->entityStoreMock
            ->shouldReceive('get')
            ->once()
            ->with($uuid)
            ->andReturn($entityMock);

        $this->readModelFactoryMock
            ->shouldReceive('makeArticleActualInstanceByEntity')
            ->once()
            ->with($entityMock)
            ->andReturn($readModelMock);

        $this->readModelStoreMock
            ->shouldReceive('update')
            ->once()
            ->with($readModelMock);

        // Act
        $this->projector->applyArticleArchivedEvent($eventMock);

        // Assert - implicit through Mockery expectations
        $this->assertTrue(true);
    }

    #[Test]
    public function applyArticleDeletedEventShouldDeleteReadModel(): void
    {
        // Arrange
        $uuid = Uuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $eventMock = \Mockery::mock(ArticleDeletedEvent::class);
        $eventMock->shouldReceive('getUuid')
            ->andReturn($uuid);

        $readModelMock = \Mockery::mock(ArticleReadModelInterface::class);

        $this->readModelStoreMock
            ->shouldReceive('get')
            ->once()
            ->with($uuid)
            ->andReturn($readModelMock);

        $this->readModelStoreMock
            ->shouldReceive('delete')
            ->once()
            ->with($readModelMock);

        // Act
        $this->projector->applyArticleDeletedEvent($eventMock);

        // Assert - implicit through Mockery expectations
        $this->assertTrue(true);
    }

    #[Test]
    public function applyArticleDeletedEventShouldThrowExceptionWhenReadModelNotFound(): void
    {
        // Arrange
        $uuid = Uuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $eventMock = \Mockery::mock(ArticleDeletedEvent::class);
        $eventMock->shouldReceive('getUuid')
            ->andReturn($uuid);

        $this->readModelStoreMock
            ->shouldReceive('get')
            ->once()
            ->with($uuid)
            ->andReturn(null);

        // Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("ReadModel with id '550e8400-e29b-41d4-a716-446655440000' not found");

        // Act
        $this->projector->applyArticleDeletedEvent($eventMock);
    }

    #[Test]
    public function constructorShouldAcceptDependencies(): void
    {
        // Arrange & Act
        $projector = new ArticleProjector(
            $this->entityStoreMock,
            $this->readModelStoreMock,
            $this->readModelFactoryMock
        );

        // Assert
        $this->assertInstanceOf(ArticleProjector::class, $projector);
    }
}
