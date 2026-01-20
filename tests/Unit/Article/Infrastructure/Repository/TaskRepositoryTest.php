<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Infrastructure\Repository;

use Enqueue\Client\ProducerInterface;
use Micro\Article\Application\Factory\CommandFactoryInterface;
use Micro\Article\Domain\ValueObject\Article;
use Micro\Article\Infrastructure\Repository\TaskRepository;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use MicroModule\Task\Application\Processor\JobCommandBusProcessor;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Infrastructure\Repository\TaskRepositoryDataProvider;

/**
 * Unit tests for TaskRepository.
 */
#[CoversClass(TaskRepository::class)]
final class TaskRepositoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private TaskRepository $repository;
    private ProducerInterface&Mockery\MockInterface $producerMock;

    protected function setUp(): void
    {
        $this->producerMock = \Mockery::mock(ProducerInterface::class);
        $this->repository = new TaskRepository($this->producerMock);
    }

    #[Test]
    #[DataProviderExternal(TaskRepositoryDataProvider::class, 'addArticleCreateTaskScenarios')]
    public function addArticleCreateTaskShouldProduceCommand(
        string $processUuid,
        array $articleData,
        string $expectedType,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $articleVo = Article::fromNative($articleData);

        $this->producerMock
            ->shouldReceive('sendCommand')
            ->once()
            ->with(
                JobCommandBusProcessor::getRoute(),
                \Mockery::on(function (array $message) use ($expectedType, $processUuid) {
                    return $message['type'] === $expectedType
                        && $message['args'][0] === $processUuid
                        && is_array($message['args'][1]);
                })
            );

        // Act
        $this->repository->addArticleCreateTask($processUuidVo, $articleVo);

        // Assert - implicit through Mockery expectations
        $this->assertTrue(true);
    }

    #[Test]
    #[DataProviderExternal(TaskRepositoryDataProvider::class, 'addArticleUpdateTaskScenarios')]
    public function addArticleUpdateTaskShouldProduceCommand(
        string $processUuid,
        string $uuid,
        array $articleData,
        string $expectedType,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $articleVo = Article::fromNative($articleData);

        $this->producerMock
            ->shouldReceive('sendCommand')
            ->once()
            ->with(
                JobCommandBusProcessor::getRoute(),
                \Mockery::on(function (array $message) use ($expectedType, $processUuid, $uuid) {
                    return $message['type'] === $expectedType
                        && $message['args'][0] === $processUuid
                        && $message['args'][1] === $uuid
                        && is_array($message['args'][2]);
                })
            );

        // Act
        $this->repository->addArticleUpdateTask($processUuidVo, $uuidVo, $articleVo);

        // Assert - implicit through Mockery expectations
        $this->assertTrue(true);
    }

    #[Test]
    #[DataProviderExternal(TaskRepositoryDataProvider::class, 'simpleTaskScenarios')]
    public function simpleTasksShouldProduceCommand(
        string $method,
        string $processUuid,
        string $uuid,
        string $expectedType,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);

        $this->producerMock
            ->shouldReceive('sendCommand')
            ->once()
            ->with(
                JobCommandBusProcessor::getRoute(),
                \Mockery::on(function (array $message) use ($expectedType, $processUuid, $uuid) {
                    return $message['type'] === $expectedType
                        && $message['args'][0] === $processUuid
                        && $message['args'][1] === $uuid;
                })
            );

        // Act
        $this->repository->{$method}($processUuidVo, $uuidVo);

        // Assert - implicit through Mockery expectations
        $this->assertTrue(true);
    }

    #[Test]
    public function addArticlePublishTaskShouldProduceCorrectCommand(): void
    {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuidVo = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');

        $this->producerMock
            ->shouldReceive('sendCommand')
            ->once()
            ->with(
                JobCommandBusProcessor::getRoute(),
                \Mockery::on(function (array $message) {
                    return $message['type'] === CommandFactoryInterface::ARTICLE_PUBLISH_COMMAND;
                })
            );

        // Act
        $this->repository->addArticlePublishTask($processUuidVo, $uuidVo);

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function addArticleUnpublishTaskShouldProduceCorrectCommand(): void
    {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuidVo = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');

        $this->producerMock
            ->shouldReceive('sendCommand')
            ->once()
            ->with(
                JobCommandBusProcessor::getRoute(),
                \Mockery::on(function (array $message) {
                    return $message['type'] === CommandFactoryInterface::ARTICLE_UNPUBLISH_COMMAND;
                })
            );

        // Act
        $this->repository->addArticleUnpublishTask($processUuidVo, $uuidVo);

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function addArticleArchiveTaskShouldProduceCorrectCommand(): void
    {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuidVo = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');

        $this->producerMock
            ->shouldReceive('sendCommand')
            ->once()
            ->with(
                JobCommandBusProcessor::getRoute(),
                \Mockery::on(function (array $message) {
                    return $message['type'] === CommandFactoryInterface::ARTICLE_ARCHIVE_COMMAND;
                })
            );

        // Act
        $this->repository->addArticleArchiveTask($processUuidVo, $uuidVo);

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function addArticleDeleteTaskShouldProduceCorrectCommand(): void
    {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuidVo = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');

        $this->producerMock
            ->shouldReceive('sendCommand')
            ->once()
            ->with(
                JobCommandBusProcessor::getRoute(),
                \Mockery::on(function (array $message) {
                    return $message['type'] === CommandFactoryInterface::ARTICLE_DELETE_COMMAND;
                })
            );

        // Act
        $this->repository->addArticleDeleteTask($processUuidVo, $uuidVo);

        // Assert
        $this->assertTrue(true);
    }

    #[Test]
    public function constructorShouldAcceptDependencies(): void
    {
        // Arrange & Act
        $repository = new TaskRepository($this->producerMock);

        // Assert
        $this->assertInstanceOf(TaskRepository::class, $repository);
    }
}
