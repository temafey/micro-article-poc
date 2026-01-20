<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\Command\Task;

use Micro\Article\Application\Command\ArticleUpdateCommand;
use Micro\Article\Application\Command\Task\ArticleUpdateTaskCommand;
use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Application\Command\Task\ArticleUpdateTaskCommandDataProvider;

/**
 * Unit tests for ArticleUpdateTaskCommand.
 */
#[CoversClass(ArticleUpdateTaskCommand::class)]
final class ArticleUpdateTaskCommandTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(ArticleUpdateTaskCommandDataProvider::class, 'provideValidConstructionData')]
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
        $uuidVo = Uuid::fromNative($uuid);
        $article = Article::fromArray([
            'title' => $title,
            'description' => $description,
            'short_description' => $shortDescription,
            'status' => $status,
        ]);

        // Act
        $command = new ArticleUpdateTaskCommand($processUuidVo, $uuidVo, $article);

        // Assert
        $this->assertInstanceOf(ArticleUpdateTaskCommand::class, $command);
        $this->assertInstanceOf(ArticleUpdateCommand::class, $command);
    }

    #[Test]
    #[DataProviderExternal(ArticleUpdateTaskCommandDataProvider::class, 'provideValidConstructionData')]
    public function getUuidShouldReturnUuid(
        string $processUuid,
        string $uuid,
        string $title,
        string $description,
        string $shortDescription,
        string $status,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $article = Article::fromArray([
            'title' => $title,
        ]);
        $command = new ArticleUpdateTaskCommand($processUuidVo, $uuidVo, $article);

        // Act
        $result = $command->getUuid();

        // Assert
        $this->assertSame($uuid, $result->toNative());
    }

    #[Test]
    #[DataProviderExternal(ArticleUpdateTaskCommandDataProvider::class, 'provideValidConstructionData')]
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
        $uuidVo = Uuid::fromNative($uuid);
        $article = Article::fromArray([
            'title' => $title,
        ]);
        $command = new ArticleUpdateTaskCommand($processUuidVo, $uuidVo, $article);

        // Act
        $result = $command->getArticle();

        // Assert
        $this->assertInstanceOf(Article::class, $result);
    }
}
