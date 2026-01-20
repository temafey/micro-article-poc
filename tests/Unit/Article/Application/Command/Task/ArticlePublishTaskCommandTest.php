<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\Command\Task;

use Micro\Article\Application\Command\ArticlePublishCommand;
use Micro\Article\Application\Command\Task\ArticlePublishTaskCommand;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Application\Command\Task\ArticlePublishTaskCommandDataProvider;

/**
 * Unit tests for ArticlePublishTaskCommand.
 */
#[CoversClass(ArticlePublishTaskCommand::class)]
final class ArticlePublishTaskCommandTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(ArticlePublishTaskCommandDataProvider::class, 'provideValidConstructionData')]
    public function constructShouldCreateTaskCommand(string $processUuid, string $uuid): void
    {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);

        // Act
        $command = new ArticlePublishTaskCommand($processUuidVo, $uuidVo);

        // Assert
        $this->assertInstanceOf(ArticlePublishTaskCommand::class, $command);
        $this->assertInstanceOf(ArticlePublishCommand::class, $command);
    }

    #[Test]
    #[DataProviderExternal(ArticlePublishTaskCommandDataProvider::class, 'provideValidConstructionData')]
    public function getUuidShouldReturnUuid(string $processUuid, string $uuid): void
    {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $command = new ArticlePublishTaskCommand($processUuidVo, $uuidVo);

        // Act
        $result = $command->getUuid();

        // Assert
        $this->assertSame($uuid, $result->toNative());
    }

    #[Test]
    #[DataProviderExternal(ArticlePublishTaskCommandDataProvider::class, 'provideInvalidConstructionData')]
    public function constructWithInvalidDataShouldThrowException(
        string $processUuid,
        string $uuid,
        string $expectedException,
    ): void {
        // Assert
        $this->expectException($expectedException);

        // Act
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        new ArticlePublishTaskCommand($processUuidVo, $uuidVo);
    }
}
