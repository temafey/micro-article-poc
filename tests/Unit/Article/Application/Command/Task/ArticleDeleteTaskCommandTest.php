<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\Command\Task;

use Micro\Article\Application\Command\ArticleDeleteCommand;
use Micro\Article\Application\Command\Task\ArticleDeleteTaskCommand;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Application\Command\Task\ArticleDeleteTaskCommandDataProvider;

/**
 * Unit tests for ArticleDeleteTaskCommand.
 */
#[CoversClass(ArticleDeleteTaskCommand::class)]
final class ArticleDeleteTaskCommandTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(ArticleDeleteTaskCommandDataProvider::class, 'provideValidConstructionData')]
    public function constructShouldCreateTaskCommand(string $processUuid, string $uuid): void
    {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);

        // Act
        $command = new ArticleDeleteTaskCommand($processUuidVo, $uuidVo);

        // Assert
        $this->assertInstanceOf(ArticleDeleteTaskCommand::class, $command);
        $this->assertInstanceOf(ArticleDeleteCommand::class, $command);
    }

    #[Test]
    #[DataProviderExternal(ArticleDeleteTaskCommandDataProvider::class, 'provideValidConstructionData')]
    public function getUuidShouldReturnUuid(string $processUuid, string $uuid): void
    {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $command = new ArticleDeleteTaskCommand($processUuidVo, $uuidVo);

        // Act
        $result = $command->getUuid();

        // Assert
        $this->assertSame($uuid, $result->toNative());
    }

    #[Test]
    #[DataProviderExternal(ArticleDeleteTaskCommandDataProvider::class, 'provideInvalidConstructionData')]
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
        new ArticleDeleteTaskCommand($processUuidVo, $uuidVo);
    }
}
