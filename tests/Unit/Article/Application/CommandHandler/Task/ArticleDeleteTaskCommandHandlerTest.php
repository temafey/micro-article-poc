<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\CommandHandler\Task;

use Micro\Article\Application\Command\Task\ArticleDeleteTaskCommand;
use Micro\Article\Application\CommandHandler\Task\ArticleDeleteTaskCommandHandler;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Application\CommandHandler\Task\ArticleDeleteTaskCommandHandlerDataProvider;
use Tests\Unit\Mock\Article\Domain\Repository\TaskRepositoryMockTrait;

/**
 * Unit tests for ArticleDeleteTaskCommandHandler.
 */
#[CoversClass(ArticleDeleteTaskCommandHandler::class)]
final class ArticleDeleteTaskCommandHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use TaskRepositoryMockTrait;

    private ArticleDeleteTaskCommandHandler $handler;

    protected function setUp(): void
    {
        $this->createTaskRepositoryMock();
        $this->handler = new ArticleDeleteTaskCommandHandler($this->taskRepositoryMock);
    }

    #[Test]
    #[DataProviderExternal(ArticleDeleteTaskCommandHandlerDataProvider::class, 'provideSuccessScenarios')]
    public function handleShouldDelegateToTaskRepository(
        array $commandData,
        array $mockArgs,
        array $mockTimes,
        string $expectedUuid,
    ): void {
        // Arrange
        $processUuid = ProcessUuid::fromNative($commandData['processUuid']);
        $uuid = Uuid::fromNative($commandData['uuid']);
        $command = new ArticleDeleteTaskCommand($processUuid, $uuid);

        $this->expectTaskRepositoryAddArticleDeleteTask($mockTimes['repositoryFind']);

        // Act
        $result = $this->handler->handle($command);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function constructorShouldAcceptDependencies(): void
    {
        // Arrange & Act
        $handler = new ArticleDeleteTaskCommandHandler($this->taskRepositoryMock);

        // Assert
        $this->assertInstanceOf(ArticleDeleteTaskCommandHandler::class, $handler);
    }
}
