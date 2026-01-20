<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\Command;

use Micro\Article\Application\Command\ArticleUpdateCommand;
use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Domain\ValueObject\Payload;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Application\Command\ArticleUpdateCommandDataProvider;

/**
 * Unit tests for ArticleUpdateCommand.
 */
#[CoversClass(ArticleUpdateCommand::class)]
final class ArticleUpdateCommandTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(ArticleUpdateCommandDataProvider::class, 'provideValidConstructionData')]
    public function constructWithValidDataShouldCreateInstance(
        string $processUuid,
        string $uuid,
        array $articleData,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $articleVo = Article::fromNative($articleData);

        // Act
        $command = new ArticleUpdateCommand($processUuidVo, $uuidVo, $articleVo);

        // Assert
        $this->assertInstanceOf(ArticleUpdateCommand::class, $command);
        $this->assertSame($processUuid, $command->getProcessUuid()->toNative());
        $this->assertSame($uuid, $command->getUuid()->toNative());
        $this->assertInstanceOf(Article::class, $command->getArticle());
    }

    #[Test]
    #[DataProviderExternal(ArticleUpdateCommandDataProvider::class, 'provideGetArticleScenarios')]
    public function getArticleShouldReturnArticleValueObject(
        string $processUuid,
        string $uuid,
        string $title,
        string $shortDescription,
        string $description,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $articleData = [
            'title' => $title,
            'short_description' => $shortDescription,
            'description' => $description,
        ];
        $articleVo = Article::fromNative($articleData);
        $command = new ArticleUpdateCommand($processUuidVo, $uuidVo, $articleVo);

        // Act
        $result = $command->getArticle();

        // Assert
        $this->assertInstanceOf(Article::class, $result);
        $this->assertSame($title, $result->getTitle()->toNative());
    }

    #[Test]
    #[DataProviderExternal(ArticleUpdateCommandDataProvider::class, 'provideWithPayloadScenarios')]
    public function constructWithPayloadShouldIncludePayload(
        string $processUuid,
        string $uuid,
        array $articleData,
        array $payload,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $articleVo = Article::fromNative($articleData);
        $payloadVo = Payload::fromNative($payload);

        // Act
        $command = new ArticleUpdateCommand($processUuidVo, $uuidVo, $articleVo, $payloadVo);

        // Assert
        $this->assertInstanceOf(ArticleUpdateCommand::class, $command);
        $this->assertInstanceOf(Article::class, $command->getArticle());
    }

    #[Test]
    public function getProcessUuidShouldReturnCorrectValue(): void
    {
        // Arrange
        $expectedUuid = '550e8400-e29b-41d4-a716-446655440000';
        $processUuid = ProcessUuid::fromNative($expectedUuid);
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $article = Article::fromNative([
            'title' => 'Test Title',
            'short_description' => 'Test short description.',
            'description' => 'This is a test description that meets the minimum fifty character length requirement.',
        ]);

        // Act
        $command = new ArticleUpdateCommand($processUuid, $uuid, $article);

        // Assert
        $this->assertSame($expectedUuid, $command->getProcessUuid()->toNative());
    }

    #[Test]
    public function getUuidShouldReturnCorrectValue(): void
    {
        // Arrange
        $expectedUuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative($expectedUuid);
        $article = Article::fromNative([
            'title' => 'Test Title',
            'short_description' => 'Test short description.',
            'description' => 'This is a test description that meets the minimum fifty character length requirement.',
        ]);

        // Act
        $command = new ArticleUpdateCommand($processUuid, $uuid, $article);

        // Assert
        $this->assertSame($expectedUuid, $command->getUuid()->toNative());
    }

    #[Test]
    public function constructWithoutPayloadShouldWork(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $article = Article::fromNative([
            'title' => 'Test Title',
            'short_description' => 'Test short description.',
            'description' => 'This is a test description that meets the minimum fifty character length requirement.',
        ]);

        // Act
        $command = new ArticleUpdateCommand($processUuid, $uuid, $article);

        // Assert
        $this->assertInstanceOf(ArticleUpdateCommand::class, $command);
    }
}
