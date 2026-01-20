<?php

declare(strict_types=1);

namespace Micro\Article\Application\Query;

use MicroModule\Base\Application\Query\AbstractQuery;
use MicroModule\Base\Domain\ValueObject\FindCriteria;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;

/**
 * @class FindByCriteriaArticleQuery
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class FindByCriteriaArticleQuery extends AbstractQuery
{
    public function __construct(
        ProcessUuid $processUuid,
        protected FindCriteria $findCriteria,
    ) {
        parent::__construct($processUuid);
    }

    /**
     * Return FindCriteria value object.
     */
    public function getFindCriteria(): FindCriteria
    {
        return $this->findCriteria;
    }
}
