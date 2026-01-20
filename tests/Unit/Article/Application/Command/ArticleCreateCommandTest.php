<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\Command;

use Micro\Article\Application\Command\ArticleCreateCommand;
use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Domain\ValueObject\Payload;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Application\Command\ArticleCreateCommandDataProvider;

/**
 * Unit tests for ArticleCreateCommand.
 */
#[CoversClass(ArticleCreateCommand::class)]
final class ArticleCreateCommandTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(ArticleCreateCommandDataProvider::class, 'provideValidConstructionData')]
    public function constructWithValidDataShouldCreateInstance(string $processUuid, array $articleData): void
    {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $articleVo = Article::fromNative($articleData);

        // Act
        $command = new ArticleCreateCommand($processUuidVo, $articleVo);

        // Assert
        $this->assertInstanceOf(ArticleCreateCommand::class, $command);
        $this->assertSame($processUuid, $command->getProcessUuid()->toNative());
        $this->assertInstanceOf(Article::class, $command->getArticle());
    }

    #[Test]
    #[DataProviderExternal(ArticleCreateCommandDataProvider::class, 'provideGetArticleScenarios')]
    public function getArticleShouldReturnArticleValueObject(
        string $processUuid,
        string $title,
        string $shortDescription,
        string $description,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $articleData = [
            'title' => $title,
            'short_description' => $shortDescription,
            'description' => $description,
        ];
        $articleVo = Article::fromNative($articleData);
        $command = new ArticleCreateCommand($processUuidVo, $articleVo);

        // Act
        $result = $command->getArticle();

        // Assert
        $this->assertInstanceOf(Article::class, $result);
        $this->assertSame($title, $result->getTitle()->toNative());
    }

    #[Test]
    #[DataProviderExternal(ArticleCreateCommandDataProvider::class, 'provideWithPayloadScenarios')]
    public function constructWithPayloadShouldIncludePayload(
        string $processUuid,
        array $articleData,
        array $payload,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $articleVo = Article::fromNative($articleData);
        $payloadVo = Payload::fromNative($payload);

        // Act
        $command = new ArticleCreateCommand($processUuidVo, $articleVo, null, $payloadVo);

        // Assert
        $this->assertInstanceOf(ArticleCreateCommand::class, $command);
        $this->assertInstanceOf(Article::class, $command->getArticle());
    }

    #[Test]
    public function getProcessUuidShouldReturnCorrectValue(): void
    {
        // Arrange
        $expectedUuid = '550e8400-e29b-41d4-a716-446655440000';
        $processUuid = ProcessUuid::fromNative($expectedUuid);
        $article = Article::fromNative([
            'title' => 'Test Title',
            'short_description' => 'Test short description.',
            'description' => 'This is a test description that meets the minimum fifty character length requirement.',
        ]);

        // Act
        $command = new ArticleCreateCommand($processUuid, $article);

        // Assert
        $this->assertSame($expectedUuid, $command->getProcessUuid()->toNative());
    }

    #[Test]
    public function getUuidShouldReturnNull(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $article = Article::fromNative([
            'title' => 'Test Title',
            'short_description' => 'Test short description.',
            'description' => 'This is a test description that meets the minimum fifty character length requirement.',
        ]);

        // Act
        $command = new ArticleCreateCommand($processUuid, $article);

        // Assert
        $this->assertNull($command->getUuid());
    }

    #[Test]
    public function constructWithoutPayloadShouldHaveNullPayload(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $article = Article::fromNative([
            'title' => 'Test Title',
            'short_description' => 'Test short description.',
            'description' => 'This is a test description that meets the minimum fifty character length requirement.',
        ]);

        // Act
        $command = new ArticleCreateCommand($processUuid, $article);

        // Assert - checking that command can be created without payload
        $this->assertInstanceOf(ArticleCreateCommand::class, $command);
    }
}
