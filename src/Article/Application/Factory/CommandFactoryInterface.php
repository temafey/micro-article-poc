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
use MicroModule\Base\Domain\Factory\CommandFactoryInterface as BaseCommandFactoryInterface;

/**
 * Factory interface for creating Article domain commands.
 *
 * Extends the Domain-layer CommandFactoryInterface (polyfill) which transitively
 * extends Application-layer interface for compatibility with micro-module/task.
 */
interface CommandFactoryInterface extends BaseCommandFactoryInterface
{
    public const ARTICLE_CREATE_COMMAND = 'ArticleCreateCommand';

    public const ARTICLE_CREATE_TASK_COMMAND = 'ArticleCreateTaskCommand';

    public const ARTICLE_UPDATE_COMMAND = 'ArticleUpdateCommand';

    public const ARTICLE_UPDATE_TASK_COMMAND = 'ArticleUpdateTaskCommand';

    public const ARTICLE_PUBLISH_COMMAND = 'ArticlePublishCommand';

    public const ARTICLE_PUBLISH_TASK_COMMAND = 'ArticlePublishTaskCommand';

    public const ARTICLE_UNPUBLISH_COMMAND = 'ArticleUnpublishCommand';

    public const ARTICLE_UNPUBLISH_TASK_COMMAND = 'ArticleUnpublishTaskCommand';

    public const ARTICLE_ARCHIVE_COMMAND = 'ArticleArchiveCommand';

    public const ARTICLE_ARCHIVE_TASK_COMMAND = 'ArticleArchiveTaskCommand';

    public const ARTICLE_DELETE_COMMAND = 'ArticleDeleteCommand';

    public const ARTICLE_DELETE_TASK_COMMAND = 'ArticleDeleteTaskCommand';

    /**
     * Create ArticleCreateCommand Command.
     */
    public function makeArticleCreateCommand(string $processUuid, array $article, ?array $payload = null): ArticleCreateCommand;

    /**
     * Create ArticleCreateTaskCommand Command.
     */
    public function makeArticleCreateTaskCommand(
        string $processUuid,
        array $article,
        ?array $payload = null,
    ): ArticleCreateTaskCommand;

    /**
     * Create ArticleUpdateCommand Command.
     */
    public function makeArticleUpdateCommand(
        string $processUuid,
        string $uuid,
        array $article,
        ?array $payload = null,
    ): ArticleUpdateCommand;

    /**
     * Create ArticleUpdateTaskCommand Command.
     */
    public function makeArticleUpdateTaskCommand(
        string $processUuid,
        string $uuid,
        array $article,
        ?array $payload = null,
    ): ArticleUpdateTaskCommand;

    /**
     * Create ArticlePublishCommand Command.
     */
    public function makeArticlePublishCommand(
        string $processUuid,
        string $uuid,
        ?array $payload = null,
    ): ArticlePublishCommand;

    /**
     * Create ArticlePublishTaskCommand Command.
     */
    public function makeArticlePublishTaskCommand(
        string $processUuid,
        string $uuid,
        ?array $payload = null,
    ): ArticlePublishTaskCommand;

    /**
     * Create ArticleUnpublishCommand Command.
     */
    public function makeArticleUnpublishCommand(
        string $processUuid,
        string $uuid,
        ?array $payload = null,
    ): ArticleUnpublishCommand;

    /**
     * Create ArticleUnpublishTaskCommand Command.
     */
    public function makeArticleUnpublishTaskCommand(
        string $processUuid,
        string $uuid,
        ?array $payload = null,
    ): ArticleUnpublishTaskCommand;

    /**
     * Create ArticleArchiveCommand Command.
     */
    public function makeArticleArchiveCommand(
        string $processUuid,
        string $uuid,
        ?array $payload = null,
    ): ArticleArchiveCommand;

    /**
     * Create ArticleArchiveTaskCommand Command.
     */
    public function makeArticleArchiveTaskCommand(
        string $processUuid,
        string $uuid,
        ?array $payload = null,
    ): ArticleArchiveTaskCommand;

    /**
     * Create ArticleDeleteCommand Command.
     */
    public function makeArticleDeleteCommand(string $processUuid, string $uuid, ?array $payload = null): ArticleDeleteCommand;

    /**
     * Create ArticleDeleteTaskCommand Command.
     */
    public function makeArticleDeleteTaskCommand(
        string $processUuid,
        string $uuid,
        ?array $payload = null,
    ): ArticleDeleteTaskCommand;
}
