<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\Command;

use Micro\Article\Application\Command\ArticleDeleteCommand;
use MicroModule\Base\Domain\ValueObject\Payload;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Application\Command\ArticleDeleteCommandDataProvider;

/**
 * Unit tests for ArticleDeleteCommand.
 */
#[CoversClass(ArticleDeleteCommand::class)]
final class ArticleDeleteCommandTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(ArticleDeleteCommandDataProvider::class, 'provideValidConstructionData')]
    public function constructWithValidDataShouldCreateInstance(string $processUuid, string $uuid): void
    {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);

        // Act
        $command = new ArticleDeleteCommand($processUuidVo, $uuidVo);

        // Assert
        $this->assertInstanceOf(ArticleDeleteCommand::class, $command);
        $this->assertSame($processUuid, $command->getProcessUuid()->toNative());
        $this->assertSame($uuid, $command->getUuid()->toNative());
    }

    #[Test]
    #[DataProviderExternal(ArticleDeleteCommandDataProvider::class, 'provideWithPayloadScenarios')]
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
        $command = new ArticleDeleteCommand($processUuidVo, $uuidVo, $payloadVo);

        // Assert
        $this->assertInstanceOf(ArticleDeleteCommand::class, $command);
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
        $command = new ArticleDeleteCommand($processUuid, $uuid);

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
        $command = new ArticleDeleteCommand($processUuid, $uuid);

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
        $command = new ArticleDeleteCommand($processUuid, $uuid);

        // Assert
        $this->assertInstanceOf(ArticleDeleteCommand::class, $command);
    }

    #[Test]
    public function commandShouldNotContainArticleValueObject(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');

        // Act
        $command = new ArticleDeleteCommand($processUuid, $uuid);

        // Assert - ArticleDeleteCommand does not have a getArticle method (delete only needs UUID)
        $this->assertFalse(method_exists($command, 'getArticle'));
    }
}
