<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\Command\Task;

use Micro\Article\Application\Command\ArticleArchiveCommand;
use Micro\Article\Application\Command\Task\ArticleArchiveTaskCommand;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Application\Command\Task\ArticleArchiveTaskCommandDataProvider;

/**
 * Unit tests for ArticleArchiveTaskCommand.
 */
#[CoversClass(ArticleArchiveTaskCommand::class)]
final class ArticleArchiveTaskCommandTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(ArticleArchiveTaskCommandDataProvider::class, 'provideValidConstructionData')]
    public function constructShouldCreateTaskCommand(string $processUuid, string $uuid): void
    {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);

        // Act
        $command = new ArticleArchiveTaskCommand($processUuidVo, $uuidVo);

        // Assert
        $this->assertInstanceOf(ArticleArchiveTaskCommand::class, $command);
        $this->assertInstanceOf(ArticleArchiveCommand::class, $command);
    }

    #[Test]
    #[DataProviderExternal(ArticleArchiveTaskCommandDataProvider::class, 'provideValidConstructionData')]
    public function getUuidShouldReturnUuid(string $processUuid, string $uuid): void
    {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $command = new ArticleArchiveTaskCommand($processUuidVo, $uuidVo);

        // Act
        $result = $command->getUuid();

        // Assert
        $this->assertSame($uuid, $result->toNative());
    }

    #[Test]
    #[DataProviderExternal(ArticleArchiveTaskCommandDataProvider::class, 'provideInvalidConstructionData')]
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
        new ArticleArchiveTaskCommand($processUuidVo, $uuidVo);
    }
}
