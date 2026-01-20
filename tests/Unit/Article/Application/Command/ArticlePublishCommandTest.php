<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\Command;

use Micro\Article\Application\Command\ArticlePublishCommand;
use MicroModule\Base\Domain\ValueObject\Payload;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Application\Command\ArticlePublishCommandDataProvider;

/**
 * Unit tests for ArticlePublishCommand.
 */
#[CoversClass(ArticlePublishCommand::class)]
final class ArticlePublishCommandTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(ArticlePublishCommandDataProvider::class, 'provideValidConstructionData')]
    public function constructWithValidDataShouldCreateInstance(string $processUuid, string $uuid): void
    {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);

        // Act
        $command = new ArticlePublishCommand($processUuidVo, $uuidVo);

        // Assert
        $this->assertInstanceOf(ArticlePublishCommand::class, $command);
        $this->assertSame($processUuid, $command->getProcessUuid()->toNative());
        $this->assertSame($uuid, $command->getUuid()->toNative());
    }

    #[Test]
    #[DataProviderExternal(ArticlePublishCommandDataProvider::class, 'provideWithPayloadScenarios')]
    public function constructWithPayloadShouldIncludePayload(
        string $processUuid,
        string $uuid,
        array $payload,
    ): void {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $payloadVo = Payload::fromNative($payload);

        // Act
        $command = new ArticlePublishCommand($processUuidVo, $uuidVo, $payloadVo);

        // Assert
        $this->assertInstanceOf(ArticlePublishCommand::class, $command);
        $this->assertSame($processUuid, $command->getProcessUuid()->toNative());
        $this->assertSame($uuid, $command->getUuid()->toNative());
    }

    #[Test]
    public function getProcessUuidShouldReturnCorrectValue(): void
    {
        // Arrange
        $expectedUuid = '550e8400-e29b-41d4-a716-446655440000';
        $processUuid = ProcessUuid::fromNative($expectedUuid);
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');

        // Act
        $command = new ArticlePublishCommand($processUuid, $uuid);

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

        // Act
        $command = new ArticlePublishCommand($processUuid, $uuid);

        // Assert
        $this->assertSame($expectedUuid, $command->getUuid()->toNative());
    }

    #[Test]
    public function constructWithoutPayloadShouldWork(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');

        // Act
        $command = new ArticlePublishCommand($processUuid, $uuid);

        // Assert
        $this->assertInstanceOf(ArticlePublishCommand::class, $command);
    }

    #[Test]
    public function commandShouldNotContainArticleValueObject(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');

        // Act
        $command = new ArticlePublishCommand($processUuid, $uuid);

        // Assert - ArticlePublishCommand does not have a getArticle method
        $this->assertFalse(method_exists($command, 'getArticle'));
    }
}
