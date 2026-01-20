<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\Command\Task;

use Micro\Article\Application\Command\ArticleUnpublishCommand;
use Micro\Article\Application\Command\Task\ArticleUnpublishTaskCommand;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Application\Command\Task\ArticleUnpublishTaskCommandDataProvider;

/**
 * Unit tests for ArticleUnpublishTaskCommand.
 */
#[CoversClass(ArticleUnpublishTaskCommand::class)]
final class ArticleUnpublishTaskCommandTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(ArticleUnpublishTaskCommandDataProvider::class, 'provideValidConstructionData')]
    public function constructShouldCreateTaskCommand(string $processUuid, string $uuid): void
    {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);

        // Act
        $command = new ArticleUnpublishTaskCommand($processUuidVo, $uuidVo);

        // Assert
        $this->assertInstanceOf(ArticleUnpublishTaskCommand::class, $command);
        $this->assertInstanceOf(ArticleUnpublishCommand::class, $command);
    }

    #[Test]
    #[DataProviderExternal(ArticleUnpublishTaskCommandDataProvider::class, 'provideValidConstructionData')]
    public function getUuidShouldReturnUuid(string $processUuid, string $uuid): void
    {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);
        $command = new ArticleUnpublishTaskCommand($processUuidVo, $uuidVo);

        // Act
        $result = $command->getUuid();

        // Assert
        $this->assertSame($uuid, $result->toNative());
    }

    #[Test]
    #[DataProviderExternal(ArticleUnpublishTaskCommandDataProvider::class, 'provideInvalidConstructionData')]
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
        new ArticleUnpublishTaskCommand($processUuidVo, $uuidVo);
    }
}
