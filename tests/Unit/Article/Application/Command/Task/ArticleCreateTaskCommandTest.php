<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\Command\Task;

use Micro\Article\Application\Command\ArticleCreateCommand;
use Micro\Article\Application\Command\Task\ArticleCreateTaskCommand;
use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Application\Command\Task\ArticleCreateTaskCommandDataProvider;

/**
 * Unit tests for ArticleCreateTaskCommand.
 */
#[CoversClass(ArticleCreateTaskCommand::class)]
final class ArticleCreateTaskCommandTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(ArticleCreateTaskCommandDataProvider::class, 'provideValidConstructionData')]
    public function constructShouldCreateTaskCommand(
        string $processUuid,
        string $uuid,
        string $title,
        string $description,
        string $shortDescription,
        string $status,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $article = Article::fromArray([
            'title' => $title,
            'description' => $description,
            'short_description' => $shortDescription,
            'status' => $status,
        ]);

        // Act
        $command = new ArticleCreateTaskCommand($processUuidVo, $article);

        // Assert
        $this->assertInstanceOf(ArticleCreateTaskCommand::class, $command);
        $this->assertInstanceOf(ArticleCreateCommand::class, $command);
    }

    #[Test]
    #[DataProviderExternal(ArticleCreateTaskCommandDataProvider::class, 'provideValidConstructionData')]
    public function getArticleShouldReturnArticleValueObject(
        string $processUuid,
        string $uuid,
        string $title,
        string $description,
        string $shortDescription,
        string $status,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $articleData = [
            'title' => $title,
            'description' => $description,
            'short_description' => $shortDescription,
            'status' => $status,
        ];
        $article = Article::fromArray($articleData);
        $command = new ArticleCreateTaskCommand($processUuidVo, $article);

        // Act
        $result = $command->getArticle();

        // Assert
        $this->assertInstanceOf(Article::class, $result);
    }

    #[Test]
    #[DataProviderExternal(ArticleCreateTaskCommandDataProvider::class, 'provideValidConstructionData')]
    public function getProcessUuidShouldReturnProcessUuid(
        string $processUuid,
        string $uuid,
        string $title,
        string $description,
        string $shortDescription,
        string $status,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $article = Article::fromArray([
            'title' => $title,
        ]);
        $command = new ArticleCreateTaskCommand($processUuidVo, $article);

        // Act
        $result = $command->getProcessUuid();

        // Assert
        $this->assertSame($processUuid, $result->toNative());
    }
}
