<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\CommandHandler\Task;

use Micro\Article\Application\Command\Task\ArticleArchiveTaskCommand;
use Micro\Article\Application\CommandHandler\Task\ArticleArchiveTaskCommandHandler;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Application\CommandHandler\Task\ArticleArchiveTaskCommandHandlerDataProvider;
use Tests\Unit\Mock\Article\Domain\Repository\TaskRepositoryMockTrait;

/**
 * Unit tests for ArticleArchiveTaskCommandHandler.
 */
#[CoversClass(ArticleArchiveTaskCommandHandler::class)]
final class ArticleArchiveTaskCommandHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use TaskRepositoryMockTrait;

    private ArticleArchiveTaskCommandHandler $handler;

    protected function setUp(): void
    {
        $this->createTaskRepositoryMock();
        $this->handler = new ArticleArchiveTaskCommandHandler($this->taskRepositoryMock);
    }

    #[Test]
    #[DataProviderExternal(ArticleArchiveTaskCommandHandlerDataProvider::class, 'provideSuccessScenarios')]
    public function handleShouldDelegateToTaskRepository(
        array $commandData,
        array $mockArgs,
        array $mockTimes,
        string $expectedUuid,
    ): void {
        // Arrange
        $processUuid = ProcessUuid::fromNative($commandData['processUuid']);
        $uuid = Uuid::fromNative($commandData['uuid']);
        $command = new ArticleArchiveTaskCommand($processUuid, $uuid);

        $this->expectTaskRepositoryAddArticleArchiveTask($mockTimes['repositoryFind']);

        // Act
        $result = $this->handler->handle($command);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function constructorShouldAcceptDependencies(): void
    {
        // Arrange & Act
        $handler = new ArticleArchiveTaskCommandHandler($this->taskRepositoryMock);

        // Assert
        $this->assertInstanceOf(ArticleArchiveTaskCommandHandler::class, $handler);
    }
}
