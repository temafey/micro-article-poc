<?php

declare(strict_types=1);

namespace Micro\Article\Application\Query;

use MicroModule\Base\Application\Query\AbstractQuery;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;

/**
 * @class FetchOneArticleQuery
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class FetchOneArticleQuery extends AbstractQuery
{
    public function __construct(
        ProcessUuid $processUuid,
        protected Uuid $uuid,
    ) {
        parent::__construct($processUuid);
    }

    /**
     * Return Uuid value object.
     */
    public function getUuid(): Uuid
    {
        return $this->uuid;
    }
}
