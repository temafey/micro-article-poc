<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\CommandHandler\Task;

use Micro\Article\Application\Command\Task\ArticleCreateTaskCommand;
use Micro\Article\Application\CommandHandler\Task\ArticleCreateTaskCommandHandler;
use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Application\CommandHandler\Task\ArticleCreateTaskCommandHandlerDataProvider;
use Tests\Unit\Mock\Article\Domain\Repository\TaskRepositoryMockTrait;

/**
 * Unit tests for ArticleCreateTaskCommandHandler.
 */
#[CoversClass(ArticleCreateTaskCommandHandler::class)]
final class ArticleCreateTaskCommandHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use TaskRepositoryMockTrait;

    private ArticleCreateTaskCommandHandler $handler;

    protected function setUp(): void
    {
        $this->createTaskRepositoryMock();
        $this->handler = new ArticleCreateTaskCommandHandler($this->taskRepositoryMock);
    }

    #[Test]
    #[DataProviderExternal(ArticleCreateTaskCommandHandlerDataProvider::class, 'provideSuccessScenarios')]
    public function handleShouldDelegateToTaskRepository(
        array $commandData,
        array $mockArgs,
        array $mockTimes,
        string $expectedUuid,
    ): void {
        // Arrange
        $processUuid = ProcessUuid::fromNative($commandData['processUuid']);
        $article = Article::fromArray([
            'title' => $commandData['title'],
        ]);
        $command = new ArticleCreateTaskCommand($processUuid, $article);

        $this->expectTaskRepositoryAddArticleCreateTask($mockTimes['factoryCreate']);

        // Act
        $result = $this->handler->handle($command);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function constructorShouldAcceptDependencies(): void
    {
        // Arrange & Act
        $handler = new ArticleCreateTaskCommandHandler($this->taskRepositoryMock);

        // Assert
        $this->assertInstanceOf(ArticleCreateTaskCommandHandler::class, $handler);
    }
}
