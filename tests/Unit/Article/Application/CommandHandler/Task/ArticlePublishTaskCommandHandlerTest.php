<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\CommandHandler\Task;

use Micro\Article\Application\Command\Task\ArticlePublishTaskCommand;
use Micro\Article\Application\CommandHandler\Task\ArticlePublishTaskCommandHandler;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Application\CommandHandler\Task\ArticlePublishTaskCommandHandlerDataProvider;
use Tests\Unit\Mock\Article\Domain\Repository\TaskRepositoryMockTrait;

/**
 * Unit tests for ArticlePublishTaskCommandHandler.
 */
#[CoversClass(ArticlePublishTaskCommandHandler::class)]
final class ArticlePublishTaskCommandHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use TaskRepositoryMockTrait;

    private ArticlePublishTaskCommandHandler $handler;

    protected function setUp(): void
    {
        $this->createTaskRepositoryMock();
        $this->handler = new ArticlePublishTaskCommandHandler($this->taskRepositoryMock);
    }

    #[Test]
    #[DataProviderExternal(ArticlePublishTaskCommandHandlerDataProvider::class, 'provideSuccessScenarios')]
    public function handleShouldDelegateToTaskRepository(
        array $commandData,
        array $mockArgs,
        array $mockTimes,
        string $expectedUuid,
    ): void {
        // Arrange
        $processUuid = ProcessUuid::fromNative($commandData['processUuid']);
        $uuid = Uuid::fromNative($commandData['uuid']);
        $command = new ArticlePublishTaskCommand($processUuid, $uuid);

        $this->expectTaskRepositoryAddArticlePublishTask($mockTimes['repositoryFind']);

        // Act
        $result = $this->handler->handle($command);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function constructorShouldAcceptDependencies(): void
    {
        // Arrange & Act
        $handler = new ArticlePublishTaskCommandHandler($this->taskRepositoryMock);

        // Assert
        $this->assertInstanceOf(ArticlePublishTaskCommandHandler::class, $handler);
    }
}
