<?php

declare(strict_types=1);

namespace Micro\Article\Application\Factory;

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
use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Application\Command\CommandInterface as BaseCommandInterface;
use MicroModule\Base\Application\Dto\DtoInterface;
use MicroModule\Base\Domain\Exception\FactoryException;
use MicroModule\Base\Domain\ValueObject\CommandName;
use MicroModule\Base\Domain\ValueObject\Payload;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;

/**
 * @class CommandFactory
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CommandFactory implements CommandFactoryInterface
{
    protected const ALLOWED_COMMANDS = [
        self::ARTICLE_CREATE_COMMAND,
        self::ARTICLE_CREATE_TASK_COMMAND,
        self::ARTICLE_UPDATE_COMMAND,
        self::ARTICLE_UPDATE_TASK_COMMAND,
        self::ARTICLE_PUBLISH_COMMAND,
        self::ARTICLE_PUBLISH_TASK_COMMAND,
        self::ARTICLE_UNPUBLISH_COMMAND,
        self::ARTICLE_UNPUBLISH_TASK_COMMAND,
        self::ARTICLE_ARCHIVE_COMMAND,
        self::ARTICLE_ARCHIVE_TASK_COMMAND,
        self::ARTICLE_DELETE_COMMAND,
        self::ARTICLE_DELETE_TASK_COMMAND,
    ];

    public function isCommandAllowed(string $commandType): bool
    {
        return in_array($commandType, static::ALLOWED_COMMANDS, true);
    }

    /**
     * Make command by command constant.
     *
     * Supports two invocation patterns:
     * 1. Direct args: makeCommandInstanceByType('type', $processUuid, $uuid, $article, $payload)
     * 2. Array format: makeCommandInstanceByType('type', ['uuid' => $uuid]) - auto-generates processUuid
     */
    public function makeCommandInstanceByType(...$args): BaseCommandInterface
    {
        $type = (string) array_shift($args);

        // Detect array-based invocation pattern from controller: ['uuid' => $uuid]
        // This is used by publish, unpublish, archive, delete endpoints
        if (count($args) === 1 && is_array($args[0]) && isset($args[0]['uuid'])) {
            $data = $args[0];
            $processUuid = $data['process_uuid'] ?? \Ramsey\Uuid\Uuid::uuid6()->toString();
            $uuid = $data['uuid'];
            $payload = $data['payload'] ?? null;

            // Route to appropriate method based on command type
            return match ($type) {
                self::ARTICLE_PUBLISH_COMMAND => $this->makeArticlePublishCommand($processUuid, $uuid, $payload),
                self::ARTICLE_PUBLISH_TASK_COMMAND => $this->makeArticlePublishTaskCommand($processUuid, $uuid, $payload),
                self::ARTICLE_UNPUBLISH_COMMAND => $this->makeArticleUnpublishCommand($processUuid, $uuid, $payload),
                self::ARTICLE_UNPUBLISH_TASK_COMMAND => $this->makeArticleUnpublishTaskCommand($processUuid, $uuid, $payload),
                self::ARTICLE_ARCHIVE_COMMAND => $this->makeArticleArchiveCommand($processUuid, $uuid, $payload),
                self::ARTICLE_ARCHIVE_TASK_COMMAND => $this->makeArticleArchiveTaskCommand($processUuid, $uuid, $payload),
                self::ARTICLE_DELETE_COMMAND => $this->makeArticleDeleteCommand($processUuid, $uuid, $payload),
                self::ARTICLE_DELETE_TASK_COMMAND => $this->makeArticleDeleteTaskCommand($processUuid, $uuid, $payload),
                default => throw new FactoryException(sprintf(
                    'Command type `%s` does not support array-based invocation with only UUID',
                    $type
                )),
            };
        }

        return match ($type) {
            self::ARTICLE_CREATE_COMMAND => $this->makeArticleCreateCommand(...$args),
            self::ARTICLE_CREATE_TASK_COMMAND => $this->makeArticleCreateTaskCommand(...$args),
            self::ARTICLE_UPDATE_COMMAND => $this->makeArticleUpdateCommand(...$args),
            self::ARTICLE_UPDATE_TASK_COMMAND => $this->makeArticleUpdateTaskCommand(...$args),
            self::ARTICLE_PUBLISH_COMMAND => $this->makeArticlePublishCommand(...$args),
            self::ARTICLE_PUBLISH_TASK_COMMAND => $this->makeArticlePublishTaskCommand(...$args),
            self::ARTICLE_UNPUBLISH_COMMAND => $this->makeArticleUnpublishCommand(...$args),
            self::ARTICLE_UNPUBLISH_TASK_COMMAND => $this->makeArticleUnpublishTaskCommand(...$args),
            self::ARTICLE_ARCHIVE_COMMAND => $this->makeArticleArchiveCommand(...$args),
            self::ARTICLE_ARCHIVE_TASK_COMMAND => $this->makeArticleArchiveTaskCommand(...$args),
            self::ARTICLE_DELETE_COMMAND => $this->makeArticleDeleteCommand(...$args),
            self::ARTICLE_DELETE_TASK_COMMAND => $this->makeArticleDeleteTaskCommand(...$args),
            default => throw new FactoryException(sprintf('Command for type `%s` not found!', $type)),
        };
    }

    /**
     * Make command from DTO.
     */
    public function makeCommandInstanceByTypeFromDto(string $commandType, DtoInterface $dto): BaseCommandInterface
    {
        $data = $dto->normalize();
        $arguments = [];

        // Process UUID is always required - generate if not provided
        if (array_key_exists(DtoInterface::KEY_PROCESS_UUID, $data)) {
            $arguments[] = $data[DtoInterface::KEY_PROCESS_UUID];
            unset($data[DtoInterface::KEY_PROCESS_UUID]);
        } else {
            $arguments[] = \Ramsey\Uuid\Uuid::uuid6()->toString();
        }

        // Determine if this command needs UUID as separate argument
        $commandsNeedingUuidArg = [
            self::ARTICLE_UPDATE_COMMAND,
            self::ARTICLE_UPDATE_TASK_COMMAND,
            self::ARTICLE_PUBLISH_COMMAND,
            self::ARTICLE_PUBLISH_TASK_COMMAND,
            self::ARTICLE_UNPUBLISH_COMMAND,
            self::ARTICLE_UNPUBLISH_TASK_COMMAND,
            self::ARTICLE_ARCHIVE_COMMAND,
            self::ARTICLE_ARCHIVE_TASK_COMMAND,
            self::ARTICLE_DELETE_COMMAND,
            self::ARTICLE_DELETE_TASK_COMMAND,
        ];

        // Commands that don't need article data array (only uuid and optional payload)
        $commandsWithoutArticleData = [
            self::ARTICLE_PUBLISH_COMMAND,
            self::ARTICLE_PUBLISH_TASK_COMMAND,
            self::ARTICLE_UNPUBLISH_COMMAND,
            self::ARTICLE_UNPUBLISH_TASK_COMMAND,
            self::ARTICLE_ARCHIVE_COMMAND,
            self::ARTICLE_ARCHIVE_TASK_COMMAND,
            self::ARTICLE_DELETE_COMMAND,
            self::ARTICLE_DELETE_TASK_COMMAND,
        ];

        $needsUuidArg = in_array($commandType, $commandsNeedingUuidArg, true);
        $needsArticleData = ! in_array($commandType, $commandsWithoutArticleData, true);

        if ($needsUuidArg && array_key_exists(DtoInterface::KEY_UUID, $data)) {
            $arguments[] = $data[DtoInterface::KEY_UUID];
            unset($data[DtoInterface::KEY_UUID]);
        }

        if ($needsArticleData) {
            $arguments[] = $data;
        }

        return $this->makeCommandInstanceByType($commandType, ...$arguments);
    }

    /**
     * Create ArticleCreateCommand Command.
     *
     * Supports client-generated UUIDs: if 'uuid' key is present in $article array,
     * it will be extracted and passed to the command for use by the handler.
     */
    public function makeArticleCreateCommand(string $processUuid, array $article, ?array $payload = null): ArticleCreateCommand
    {
        $article['created_at'] ??= date_create('now');
        $article['updated_at'] ??= date_create('now');

        // Extract UUID from article data if provided (for client-generated UUIDs)
        $uuid = null;
        if (isset($article['uuid']) && is_string($article['uuid']) && $article['uuid'] !== '') {
            $uuid = Uuid::fromNative($article['uuid']);
            unset($article['uuid']); // Remove from article data as it's passed separately
        }

        return new ArticleCreateCommand(
            ProcessUuid::fromNative($processUuid),
            Article::fromNative($article),
            $uuid,
            $payload ? Payload::fromNative($payload) : null
        );
    }

    /**
     * Create ArticleCreateTaskCommand Command.
     */
    public function makeArticleCreateTaskCommand(
        string $processUuid,
        array $article,
        ?array $payload = null,
    ): ArticleCreateTaskCommand {
        return new ArticleCreateTaskCommand(
            ProcessUuid::fromNative($processUuid),
            Article::fromNative($article),
            $payload ? Payload::fromNative($payload) : null
        );
    }

    /**
     * Create ArticleUpdateCommand Command.
     */
    public function makeArticleUpdateCommand(
        string $processUuid,
        string $uuid,
        array $article,
        ?array $payload = null,
    ): ArticleUpdateCommand {
        $article['updated_at'] ??= date_create('now');

        return new ArticleUpdateCommand(
            ProcessUuid::fromNative($processUuid),
            Uuid::fromNative($uuid),
            Article::fromNative($article),
            $payload ? Payload::fromNative($payload) : null
        );
    }

    /**
     * Create ArticleUpdateTaskCommand Command.
     */
    public function makeArticleUpdateTaskCommand(
        string $processUuid,
        string $uuid,
        array $article,
        ?array $payload = null,
    ): ArticleUpdateTaskCommand {
        return new ArticleUpdateTaskCommand(
            ProcessUuid::fromNative($processUuid),
            Uuid::fromNative($uuid),
            Article::fromNative($article),
            $payload ? Payload::fromNative($payload) : null
        );
    }

    /**
     * Create ArticlePublishCommand Command.
     */
    public function makeArticlePublishCommand(
        string $processUuid,
        string $uuid,
        ?array $payload = null,
    ): ArticlePublishCommand {
        return new ArticlePublishCommand(
            ProcessUuid::fromNative($processUuid),
            Uuid::fromNative($uuid),
            $payload ? Payload::fromNative($payload) : null
        );
    }

    /**
     * Create ArticlePublishTaskCommand Command.
     */
    public function makeArticlePublishTaskCommand(
        string $processUuid,
        string $uuid,
        ?array $payload = null,
    ): ArticlePublishTaskCommand {
        return new ArticlePublishTaskCommand(
            ProcessUuid::fromNative($processUuid),
            Uuid::fromNative($uuid),
            $payload ? Payload::fromNative($payload) : null
        );
    }

    /**
     * Create ArticleUnpublishCommand Command.
     */
    public function makeArticleUnpublishCommand(
        string $processUuid,
        string $uuid,
        ?array $payload = null,
    ): ArticleUnpublishCommand {
        return new ArticleUnpublishCommand(
            ProcessUuid::fromNative($processUuid),
            Uuid::fromNative($uuid),
            $payload ? Payload::fromNative($payload) : null
        );
    }

    /**
     * Create ArticleUnpublishTaskCommand Command.
     */
    public function makeArticleUnpublishTaskCommand(
        string $processUuid,
        string $uuid,
        ?array $payload = null,
    ): ArticleUnpublishTaskCommand {
        return new ArticleUnpublishTaskCommand(
            ProcessUuid::fromNative($processUuid),
            Uuid::fromNative($uuid),
            $payload ? Payload::fromNative($payload) : null
        );
    }

    /**
     * Create ArticleArchiveCommand Command.
     */
    public function makeArticleArchiveCommand(
        string $processUuid,
        string $uuid,
        ?array $payload = null,
    ): ArticleArchiveCommand {
        return new ArticleArchiveCommand(
            ProcessUuid::fromNative($processUuid),
            Uuid::fromNative($uuid),
            $payload ? Payload::fromNative($payload) : null
        );
    }

    /**
     * Create ArticleArchiveTaskCommand Command.
     */
    public function makeArticleArchiveTaskCommand(
        string $processUuid,
        string $uuid,
        ?array $payload = null,
    ): ArticleArchiveTaskCommand {
        return new ArticleArchiveTaskCommand(
            ProcessUuid::fromNative($processUuid),
            Uuid::fromNative($uuid),
            $payload ? Payload::fromNative($payload) : null
        );
    }

    /**
     * Create ArticleDeleteCommand Command.
     */
    public function makeArticleDeleteCommand(string $processUuid, string $uuid, ?array $payload = null): ArticleDeleteCommand
    {
        return new ArticleDeleteCommand(
            ProcessUuid::fromNative($processUuid),
            Uuid::fromNative($uuid),
            $payload ? Payload::fromNative($payload) : null
        );
    }

    /**
     * Create ArticleDeleteTaskCommand Command.
     */
    public function makeArticleDeleteTaskCommand(
        string $processUuid,
        string $uuid,
        ?array $payload = null,
    ): ArticleDeleteTaskCommand {
        return new ArticleDeleteTaskCommand(
            ProcessUuid::fromNative($processUuid),
            Uuid::fromNative($uuid),
            $payload ? Payload::fromNative($payload) : null
        );
    }

    /**
     * Create CommandName value object (Domain layer requirement).
     */
    public function makeCommandName(string $commandName): CommandName
    {
        return CommandName::fromNative($commandName);
    }

    /**
     * Create ProcessUuid for command execution (Domain layer requirement).
     */
    public function makeCommandProcessUuid(?string $processUuid = null): ProcessUuid
    {
        return ProcessUuid::fromNative($processUuid ?? \Ramsey\Uuid\Uuid::uuid6()->toString());
    }

    /**
     * Create Payload for command data (Domain layer requirement).
     */
    public function makeCommandPayload(array $payload): Payload
    {
        return Payload::fromNative($payload);
    }
}
