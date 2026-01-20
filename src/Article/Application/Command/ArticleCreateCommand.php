<?php

declare(strict_types=1);

namespace Micro\Article\Application\Command;

use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Application\Command\AbstractCommand;
use MicroModule\Base\Domain\ValueObject\Payload;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use Ramsey\Uuid\UuidInterface;

/**
 * @class ArticleCreateCommand
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ArticleCreateCommand extends AbstractCommand
{
    public function __construct(
        ProcessUuid $processUuid,
        protected Article $article,
        ?UuidInterface $uuid = null,
        ?Payload $payload = null,
    ) {
        parent::__construct($processUuid, $uuid, $payload);
    }

    /**
     * Return Article value object.
     */
    public function getArticle(): Article
    {
        return $this->article;
    }
}
