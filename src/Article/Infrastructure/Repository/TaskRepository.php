<?php

declare(strict_types=1);

namespace Micro\Article\Infrastructure\Repository;

use Micro\Article\Application\Factory\CommandFactoryInterface;
use Micro\Article\Domain\Repository\TaskRepositoryInterface;
use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use MicroModule\Base\Infrastructure\Repository\TaskRepository as BaseTaskRepository;

/**
 * @class TaskRepository
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TaskRepository extends BaseTaskRepository implements TaskRepositoryInterface
{
    /**
     * Send `ArticleCreate Command` into queue.
     */
    public function addArticleCreateTask(ProcessUuid $processUuid, Article $article): void
    {
        $this->produce(CommandFactoryInterface::ARTICLE_CREATE_COMMAND, [$processUuid->toNative(), $article->toNative()]);
    }

    /**
     * Send `ArticleUpdate Command` into queue.
     */
    public function addArticleUpdateTask(ProcessUuid $processUuid, Uuid $uuid, Article $article): void
    {
        $this->produce(
            CommandFactoryInterface::ARTICLE_UPDATE_COMMAND,
            [$processUuid->toNative(), $uuid->toNative(), $article->toNative()]
        );
    }

    /**
     * Send `ArticlePublish Command` into queue.
     */
    public function addArticlePublishTask(ProcessUuid $processUuid, Uuid $uuid): void
    {
        $this->produce(
            CommandFactoryInterface::ARTICLE_PUBLISH_COMMAND,
            [$processUuid->toNative(), $uuid->toNative()]
        );
    }

    /**
     * Send `ArticleUnpublish Command` into queue.
     */
    public function addArticleUnpublishTask(ProcessUuid $processUuid, Uuid $uuid): void
    {
        $this->produce(
            CommandFactoryInterface::ARTICLE_UNPUBLISH_COMMAND,
            [$processUuid->toNative(), $uuid->toNative()]
        );
    }

    /**
     * Send `ArticleArchive Command` into queue.
     */
    public function addArticleArchiveTask(ProcessUuid $processUuid, Uuid $uuid): void
    {
        $this->produce(
            CommandFactoryInterface::ARTICLE_ARCHIVE_COMMAND,
            [$processUuid->toNative(), $uuid->toNative()]
        );
    }

    /**
     * Send `ArticleDelete Command` into queue.
     */
    public function addArticleDeleteTask(ProcessUuid $processUuid, Uuid $uuid): void
    {
        $this->produce(CommandFactoryInterface::ARTICLE_DELETE_COMMAND, [$processUuid->toNative(), $uuid->toNative()]);
    }
}
