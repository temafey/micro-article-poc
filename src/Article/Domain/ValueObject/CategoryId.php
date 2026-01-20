<?php

declare(strict_types=1);

namespace Micro\Article\Domain\ValueObject;

use MicroModule\ValueObject\Identity\UUID as BaseIdentityUuid;

/**
 * @class CategoryId
 *
 * ValueObject representing a unique identifier for article categories.
 */
class CategoryId extends BaseIdentityUuid
{
}
