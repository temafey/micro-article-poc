<?php

declare(strict_types=1);

namespace Micro\Article\Domain\Repository;

use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;

/**
 * @interface TaskRepositoryInterface
 */
interface TaskRepositoryInterface
{
    /**
     * Send `ArticleCreate Command` into queue.
     */
    public function addArticleCreateTask(ProcessUuid $processUuid, Article $article): void;

    /**
     * Send `ArticleUpdate Command` into queue.
     */
    public function addArticleUpdateTask(ProcessUuid $processUuid, Uuid $uuid, Article $article): void;

    /**
     * Send `ArticlePublish Command` into queue.
     */
    public function addArticlePublishTask(ProcessUuid $processUuid, Uuid $uuid): void;

    /**
     * Send `ArticleUnpublish Command` into queue.
     */
    public function addArticleUnpublishTask(ProcessUuid $processUuid, Uuid $uuid): void;

    /**
     * Send `ArticleArchive Command` into queue.
     */
    public function addArticleArchiveTask(ProcessUuid $processUuid, Uuid $uuid): void;

    /**
     * Send `ArticleDelete Command` into queue.
     */
    public function addArticleDeleteTask(ProcessUuid $processUuid, Uuid $uuid): void;
}
