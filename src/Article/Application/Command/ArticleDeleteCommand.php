<?php

declare(strict_types=1);

namespace Micro\Article\Application\Command;

use MicroModule\Base\Application\Command\AbstractCommand;
use MicroModule\Base\Domain\ValueObject\Payload;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;

/**
 * @class ArticleDeleteCommand
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ArticleDeleteCommand extends AbstractCommand
{
    public function __construct(ProcessUuid $processUuid, Uuid $uuid, ?Payload $payload = null)
    {
        parent::__construct($processUuid, $uuid, $payload);
    }
}
