<?php

declare(strict_types=1);

namespace Micro\Article\Domain\ValueObject;

use MicroModule\ValueObject\Identity\UUID as BaseIdentityUuid;

/**
 * @class AuthorId
 *
 * AuthorId represents a unique identifier for article authors.
 * Extends the base UUID value object with domain-specific semantics.
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
final class AuthorId extends BaseIdentityUuid
{
}
