<?php

declare(strict_types=1);

namespace Micro\Article\Domain\ValueObject;

use MicroModule\ValueObject\Enum\Enum as BaseEnumEnum;

/**
 * @class Status
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Status extends BaseEnumEnum
{
    public const DRAFT = 'draft';

    public const PUBLISHED = 'published';

    public const ARCHIVED = 'archived';

    public const DELETED = 'deleted';
}
