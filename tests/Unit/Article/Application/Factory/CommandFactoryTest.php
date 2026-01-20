<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\Factory;

use Micro\Article\Application\Command\ArticleArchiveCommand;
use Micro\Article\Application\Command\ArticleCreateCommand;
use Micro\Article\Application\Command\ArticleDeleteCommand;
use Micro\Article\Application\Command\ArticlePublishCommand;
use Micro\Article\Application\Command\ArticleUnpublishCommand;
use Micro\Article\Application\Command\ArticleUpdateCommand;
use Micro\Article\Application\Command\Task\ArticleArchiveTaskCommand;
use Micro\Article\Application\Command\Task\ArticleCreateTaskCommand;
use Micro\Article\Application\Command\Task\ArticleDeleteTaskCommand;
use Micro\Article\Application\Command\Task\ArticlePublishTaskCommand;
use Micro\Article\Application\Command\Task\ArticleUnpublishTaskCommand;
use Micro\Article\Application\Command\Task\ArticleUpdateTaskCommand;
use Micro\Article\Application\Dto\ArticleDto;
use Micro\Article\Application\Factory\CommandFactory;
use Micro\Article\Application\Factory\CommandFactoryInterface;
use MicroModule\Base\Domain\Exception\FactoryException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CommandFactory.
 */
#[CoversClass(CommandFactory::class)]
final class CommandFactoryTest extends TestCase
{
    private CommandFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new CommandFactory();
    }

    #[Test]
    public function isCommandAllowedShouldReturnTrueForValidCommand(): void
    {
        // Assert
        $this->assertTrue($this->factory->isCommandAllowed(CommandFactoryInterface::ARTICLE_CREATE_COMMAND));
        $this->assertTrue($this->factory->isCommandAllowed(CommandFactoryInterface::ARTICLE_UPDATE_COMMAND));
        $this->assertTrue($this->factory->isCommandAllowed(CommandFactoryInterface::ARTICLE_DELETE_COMMAND));
    }

    #[Test]
    public function isCommandAllowedShouldReturnFalseForInvalidCommand(): void
    {
        // Assert
        $this->assertFalse($this->factory->isCommandAllowed('invalid_command'));
    }

    #[Test]
    public function makeArticleCreateCommandShouldCreateCommand(): void
    {
        // Arrange
        $processUuid = '550e8400-e29b-41d4-a716-446655440000';
        $article = [
            'title' => 'Test Article',
        ];

        // Act
        $result = $this->factory->makeArticleCreateCommand($processUuid, $article);

        // Assert
        $this->assertInstanceOf(ArticleCreateCommand::class, $result);
    }

    #[Test]
    public function makeArticleCreateTaskCommandShouldCreateCommand(): void
    {
        // Arrange
        $processUuid = '550e8400-e29b-41d4-a716-446655440000';
        $article = [
            'title' => 'Task Article',
        ];

        // Act
        $result = $this->factory->makeArticleCreateTaskCommand($processUuid, $article);

        // Assert
        $this->assertInstanceOf(ArticleCreateTaskCommand::class, $result);
    }

    #[Test]
    public function makeArticleUpdateCommandShouldCreateCommand(): void
    {
        // Arrange
        $processUuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
        $article = [
            'title' => 'Updated Article',
        ];

        // Act
        $result = $this->factory->makeArticleUpdateCommand($processUuid, $uuid, $article);

        // Assert
        $this->assertInstanceOf(ArticleUpdateCommand::class, $result);
    }

    #[Test]
    public function makeArticleDeleteCommandShouldCreateCommand(): void
    {
        // Arrange
        $processUuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

        // Act
        $result = $this->factory->makeArticleDeleteCommand($processUuid, $uuid);

        // Assert
        $this->assertInstanceOf(ArticleDeleteCommand::class, $result);
    }

    #[Test]
    public function makeArticlePublishCommandShouldCreateCommand(): void
    {
        // Arrange
        $processUuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

        // Act
        $result = $this->factory->makeArticlePublishCommand($processUuid, $uuid);

        // Assert
        $this->assertInstanceOf(ArticlePublishCommand::class, $result);
    }

    #[Test]
    public function makeArticleUnpublishCommandShouldCreateCommand(): void
    {
        // Arrange
        $processUuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

        // Act
        $result = $this->factory->makeArticleUnpublishCommand($processUuid, $uuid);

        // Assert
        $this->assertInstanceOf(ArticleUnpublishCommand::class, $result);
    }

    #[Test]
    public function makeArticleArchiveCommandShouldCreateCommand(): void
    {
        // Arrange
        $processUuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

        // Act
        $result = $this->factory->makeArticleArchiveCommand($processUuid, $uuid);

        // Assert
        $this->assertInstanceOf(ArticleArchiveCommand::class, $result);
    }

    #[Test]
    public function makeCommandInstanceByTypeShouldCreateCorrectCommand(): void
    {
        // Arrange
        $processUuid = '550e8400-e29b-41d4-a716-446655440000';
        $article = [
            'title' => 'Test',
        ];

        // Act
        $result = $this->factory->makeCommandInstanceByType(
            CommandFactoryInterface::ARTICLE_CREATE_COMMAND,
            $processUuid,
            $article
        );

        // Assert
        $this->assertInstanceOf(ArticleCreateCommand::class, $result);
    }

    #[Test]
    public function makeCommandInstanceByTypeShouldThrowExceptionForInvalidType(): void
    {
        // Assert
        $this->expectException(FactoryException::class);

        // Act
        $this->factory->makeCommandInstanceByType('invalid_type');
    }

    #[Test]
    public function factoryShouldImplementInterface(): void
    {
        // Assert
        $this->assertInstanceOf(CommandFactoryInterface::class, $this->factory);
    }

    #[Test]
    public function makeArticleUpdateTaskCommandShouldCreateCommand(): void
    {
        // Arrange
        $processUuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
        $article = ['title' => 'Update Task Article'];

        // Act
        $result = $this->factory->makeArticleUpdateTaskCommand($processUuid, $uuid, $article);

        // Assert
        $this->assertInstanceOf(ArticleUpdateTaskCommand::class, $result);
    }

    #[Test]
    public function makeArticlePublishTaskCommandShouldCreateCommand(): void
    {
        // Arrange
        $processUuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

        // Act
        $result = $this->factory->makeArticlePublishTaskCommand($processUuid, $uuid);

        // Assert
        $this->assertInstanceOf(ArticlePublishTaskCommand::class, $result);
    }

    #[Test]
    public function makeArticleUnpublishTaskCommandShouldCreateCommand(): void
    {
        // Arrange
        $processUuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

        // Act
        $result = $this->factory->makeArticleUnpublishTaskCommand($processUuid, $uuid);

        // Assert
        $this->assertInstanceOf(ArticleUnpublishTaskCommand::class, $result);
    }

    #[Test]
    public function makeArticleArchiveTaskCommandShouldCreateCommand(): void
    {
        // Arrange
        $processUuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

        // Act
        $result = $this->factory->makeArticleArchiveTaskCommand($processUuid, $uuid);

        // Assert
        $this->assertInstanceOf(ArticleArchiveTaskCommand::class, $result);
    }

    #[Test]
    public function makeArticleDeleteTaskCommandShouldCreateCommand(): void
    {
        // Arrange
        $processUuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

        // Act
        $result = $this->factory->makeArticleDeleteTaskCommand($processUuid, $uuid);

        // Assert
        $this->assertInstanceOf(ArticleDeleteTaskCommand::class, $result);
    }

    #[Test]
    public function makeArticleCreateCommandWithUuidInArticleArrayShouldExtractUuid(): void
    {
        // Arrange
        $processUuid = '550e8400-e29b-41d4-a716-446655440000';
        $clientGeneratedUuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
        $article = [
            'uuid' => $clientGeneratedUuid,
            'title' => 'Test Article With UUID',
        ];

        // Act
        $result = $this->factory->makeArticleCreateCommand($processUuid, $article);

        // Assert
        $this->assertInstanceOf(ArticleCreateCommand::class, $result);
        $this->assertSame($clientGeneratedUuid, $result->getUuid()->toNative());
    }

    #[Test]
    public function makeArticleCreateCommandWithPayloadShouldIncludePayload(): void
    {
        // Arrange
        $processUuid = '550e8400-e29b-41d4-a716-446655440000';
        $article = ['title' => 'Test Article'];
        $payload = ['source' => 'api', 'user_id' => 123];

        // Act
        $result = $this->factory->makeArticleCreateCommand($processUuid, $article, $payload);

        // Assert
        $this->assertInstanceOf(ArticleCreateCommand::class, $result);
        $this->assertNotNull($result->getPayload());
    }

    #[Test]
    public function makeArticleUpdateCommandWithPayloadShouldIncludePayload(): void
    {
        // Arrange
        $processUuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
        $article = ['title' => 'Updated Article'];
        $payload = ['editor' => 'admin'];

        // Act
        $result = $this->factory->makeArticleUpdateCommand($processUuid, $uuid, $article, $payload);

        // Assert
        $this->assertInstanceOf(ArticleUpdateCommand::class, $result);
        $this->assertNotNull($result->getPayload());
    }

    #[Test]
    public function makeArticlePublishCommandWithPayloadShouldIncludePayload(): void
    {
        // Arrange
        $processUuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
        $payload = ['published_by' => 'moderator'];

        // Act
        $result = $this->factory->makeArticlePublishCommand($processUuid, $uuid, $payload);

        // Assert
        $this->assertInstanceOf(ArticlePublishCommand::class, $result);
        $this->assertNotNull($result->getPayload());
    }

    #[Test]
    public function makeCommandInstanceByTypeWithArrayPatternShouldCreatePublishCommand(): void
    {
        // Arrange - Array-based invocation: ['uuid' => $uuid]
        $data = ['uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8'];

        // Act
        $result = $this->factory->makeCommandInstanceByType(
            CommandFactoryInterface::ARTICLE_PUBLISH_COMMAND,
            $data
        );

        // Assert
        $this->assertInstanceOf(ArticlePublishCommand::class, $result);
    }

    #[Test]
    public function makeCommandInstanceByTypeWithArrayPatternShouldCreateUnpublishCommand(): void
    {
        // Arrange
        $data = ['uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8'];

        // Act
        $result = $this->factory->makeCommandInstanceByType(
            CommandFactoryInterface::ARTICLE_UNPUBLISH_COMMAND,
            $data
        );

        // Assert
        $this->assertInstanceOf(ArticleUnpublishCommand::class, $result);
    }

    #[Test]
    public function makeCommandInstanceByTypeWithArrayPatternShouldCreateArchiveCommand(): void
    {
        // Arrange
        $data = ['uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8'];

        // Act
        $result = $this->factory->makeCommandInstanceByType(
            CommandFactoryInterface::ARTICLE_ARCHIVE_COMMAND,
            $data
        );

        // Assert
        $this->assertInstanceOf(ArticleArchiveCommand::class, $result);
    }

    #[Test]
    public function makeCommandInstanceByTypeWithArrayPatternShouldCreateDeleteCommand(): void
    {
        // Arrange
        $data = ['uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8'];

        // Act
        $result = $this->factory->makeCommandInstanceByType(
            CommandFactoryInterface::ARTICLE_DELETE_COMMAND,
            $data
        );

        // Assert
        $this->assertInstanceOf(ArticleDeleteCommand::class, $result);
    }

    #[Test]
    public function makeCommandInstanceByTypeWithArrayPatternShouldCreateTaskCommands(): void
    {
        // Arrange
        $data = ['uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8'];

        // Act & Assert
        $this->assertInstanceOf(
            ArticlePublishTaskCommand::class,
            $this->factory->makeCommandInstanceByType(CommandFactoryInterface::ARTICLE_PUBLISH_TASK_COMMAND, $data)
        );
        $this->assertInstanceOf(
            ArticleUnpublishTaskCommand::class,
            $this->factory->makeCommandInstanceByType(CommandFactoryInterface::ARTICLE_UNPUBLISH_TASK_COMMAND, $data)
        );
        $this->assertInstanceOf(
            ArticleArchiveTaskCommand::class,
            $this->factory->makeCommandInstanceByType(CommandFactoryInterface::ARTICLE_ARCHIVE_TASK_COMMAND, $data)
        );
        $this->assertInstanceOf(
            ArticleDeleteTaskCommand::class,
            $this->factory->makeCommandInstanceByType(CommandFactoryInterface::ARTICLE_DELETE_TASK_COMMAND, $data)
        );
    }

    #[Test]
    public function makeCommandInstanceByTypeWithArrayPatternAndPayloadShouldIncludePayload(): void
    {
        // Arrange
        $data = [
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'payload' => ['action' => 'publish', 'triggered_by' => 'scheduler'],
        ];

        // Act
        $result = $this->factory->makeCommandInstanceByType(
            CommandFactoryInterface::ARTICLE_PUBLISH_COMMAND,
            $data
        );

        // Assert
        $this->assertInstanceOf(ArticlePublishCommand::class, $result);
        $this->assertNotNull($result->getPayload());
    }

    #[Test]
    public function makeCommandInstanceByTypeWithArrayPatternShouldThrowForUnsupportedType(): void
    {
        // Arrange
        $data = ['uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8'];

        // Assert
        $this->expectException(FactoryException::class);
        $this->expectExceptionMessage('does not support array-based invocation');

        // Act
        $this->factory->makeCommandInstanceByType(
            CommandFactoryInterface::ARTICLE_CREATE_COMMAND,
            $data
        );
    }

    #[Test]
    public function makeCommandInstanceByTypeShouldCreateAllCommandTypes(): void
    {
        // Arrange
        $processUuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';
        $article = ['title' => 'Test'];

        // Assert create commands
        $this->assertInstanceOf(
            ArticleCreateCommand::class,
            $this->factory->makeCommandInstanceByType(CommandFactoryInterface::ARTICLE_CREATE_COMMAND, $processUuid, $article)
        );
        $this->assertInstanceOf(
            ArticleCreateTaskCommand::class,
            $this->factory->makeCommandInstanceByType(CommandFactoryInterface::ARTICLE_CREATE_TASK_COMMAND, $processUuid, $article)
        );

        // Assert update commands
        $this->assertInstanceOf(
            ArticleUpdateCommand::class,
            $this->factory->makeCommandInstanceByType(CommandFactoryInterface::ARTICLE_UPDATE_COMMAND, $processUuid, $uuid, $article)
        );
        $this->assertInstanceOf(
            ArticleUpdateTaskCommand::class,
            $this->factory->makeCommandInstanceByType(CommandFactoryInterface::ARTICLE_UPDATE_TASK_COMMAND, $processUuid, $uuid, $article)
        );

        // Assert action commands
        $this->assertInstanceOf(
            ArticlePublishCommand::class,
            $this->factory->makeCommandInstanceByType(CommandFactoryInterface::ARTICLE_PUBLISH_COMMAND, $processUuid, $uuid)
        );
        $this->assertInstanceOf(
            ArticleUnpublishCommand::class,
            $this->factory->makeCommandInstanceByType(CommandFactoryInterface::ARTICLE_UNPUBLISH_COMMAND, $processUuid, $uuid)
        );
        $this->assertInstanceOf(
            ArticleArchiveCommand::class,
            $this->factory->makeCommandInstanceByType(CommandFactoryInterface::ARTICLE_ARCHIVE_COMMAND, $processUuid, $uuid)
        );
        $this->assertInstanceOf(
            ArticleDeleteCommand::class,
            $this->factory->makeCommandInstanceByType(CommandFactoryInterface::ARTICLE_DELETE_COMMAND, $processUuid, $uuid)
        );
    }

    #[Test]
    public function makeCommandInstanceByTypeFromDtoShouldCreateCreateCommand(): void
    {
        // Arrange
        $dto = new ArticleDto(
            title: 'Test Article',
            shortDescription: 'Short description.',
            description: 'This is a full description that meets the minimum fifty character length requirement for validation.',
            status: 'draft'
        );

        // Act
        $result = $this->factory->makeCommandInstanceByTypeFromDto(
            CommandFactoryInterface::ARTICLE_CREATE_COMMAND,
            $dto
        );

        // Assert
        $this->assertInstanceOf(ArticleCreateCommand::class, $result);
    }

    #[Test]
    public function makeCommandInstanceByTypeFromDtoShouldCreateUpdateCommand(): void
    {
        // Arrange
        $dto = new ArticleDto(
            uuid: '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            title: 'Updated Article',
            shortDescription: 'Updated short description.',
            status: 'draft'
        );

        // Act
        $result = $this->factory->makeCommandInstanceByTypeFromDto(
            CommandFactoryInterface::ARTICLE_UPDATE_COMMAND,
            $dto
        );

        // Assert
        $this->assertInstanceOf(ArticleUpdateCommand::class, $result);
    }

    #[Test]
    public function makeCommandInstanceByTypeFromDtoShouldCreatePublishCommand(): void
    {
        // Arrange
        $dto = new ArticleDto(uuid: '6ba7b810-9dad-11d1-80b4-00c04fd430c8');

        // Act
        $result = $this->factory->makeCommandInstanceByTypeFromDto(
            CommandFactoryInterface::ARTICLE_PUBLISH_COMMAND,
            $dto
        );

        // Assert
        $this->assertInstanceOf(ArticlePublishCommand::class, $result);
    }

    #[Test]
    public function makeCommandInstanceByTypeFromDtoShouldCreateDeleteCommand(): void
    {
        // Arrange
        $dto = new ArticleDto(uuid: '6ba7b810-9dad-11d1-80b4-00c04fd430c8');

        // Act
        $result = $this->factory->makeCommandInstanceByTypeFromDto(
            CommandFactoryInterface::ARTICLE_DELETE_COMMAND,
            $dto
        );

        // Assert
        $this->assertInstanceOf(ArticleDeleteCommand::class, $result);
    }

    #[Test]
    public function isCommandAllowedShouldReturnTrueForAllTaskCommands(): void
    {
        // Assert
        $this->assertTrue($this->factory->isCommandAllowed(CommandFactoryInterface::ARTICLE_CREATE_TASK_COMMAND));
        $this->assertTrue($this->factory->isCommandAllowed(CommandFactoryInterface::ARTICLE_UPDATE_TASK_COMMAND));
        $this->assertTrue($this->factory->isCommandAllowed(CommandFactoryInterface::ARTICLE_PUBLISH_TASK_COMMAND));
        $this->assertTrue($this->factory->isCommandAllowed(CommandFactoryInterface::ARTICLE_UNPUBLISH_TASK_COMMAND));
        $this->assertTrue($this->factory->isCommandAllowed(CommandFactoryInterface::ARTICLE_ARCHIVE_TASK_COMMAND));
        $this->assertTrue($this->factory->isCommandAllowed(CommandFactoryInterface::ARTICLE_DELETE_TASK_COMMAND));
    }

    #[Test]
    public function isCommandAllowedShouldReturnTrueForAllActionCommands(): void
    {
        // Assert
        $this->assertTrue($this->factory->isCommandAllowed(CommandFactoryInterface::ARTICLE_PUBLISH_COMMAND));
        $this->assertTrue($this->factory->isCommandAllowed(CommandFactoryInterface::ARTICLE_UNPUBLISH_COMMAND));
        $this->assertTrue($this->factory->isCommandAllowed(CommandFactoryInterface::ARTICLE_ARCHIVE_COMMAND));
    }

    #[Test]
    public function makeCommandInstanceByTypeFromDtoWithProcessUuidShouldUseProvidedUuid(): void
    {
        // Arrange - Create a mock DTO that includes process_uuid in normalized data
        $expectedProcessUuid = '550e8400-e29b-41d4-a716-446655440000';
        $dto = $this->createMock(\MicroModule\Base\Application\Dto\DtoInterface::class);
        $dto->method('normalize')->willReturn([
            'process_uuid' => $expectedProcessUuid,
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
        ]);

        // Act
        $result = $this->factory->makeCommandInstanceByTypeFromDto(
            CommandFactoryInterface::ARTICLE_PUBLISH_COMMAND,
            $dto
        );

        // Assert
        $this->assertInstanceOf(ArticlePublishCommand::class, $result);
        $this->assertSame($expectedProcessUuid, $result->getProcessUuid()->toNative());
    }
}
