<?php

declare(strict_types=1);

namespace Micro\Article\Domain\ValueObject;

use MicroModule\ValueObject\Exception\InvalidNativeArgumentException;
use MicroModule\ValueObject\StringLiteral\StringLiteral as BaseStringLiteralStringLiteral;
use MicroModule\ValueObject\ValueObjectInterface;

/**
 * @class Body
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Body extends BaseStringLiteralStringLiteral
{
    /**
     * Constructor with validation.
     */
    public function __construct(string $value)
    {
        $this->validate($value);

        parent::__construct($value);
    }

    /**
     * Validate the value according to business rules.
     */
    public function validate($value): void
    {
        if ($value instanceof ValueObjectInterface) {
            $value = $value->toNative();
        }

        if (empty($value)) {
            throw new InvalidNativeArgumentException($value, ['Article body cannot be empty']);
        }

        if (strlen((string) $value) < 10) {
            throw new InvalidNativeArgumentException($value, ['Article body must be at least 10 characters']);
        }

        if (strlen((string) $value) > 65535) {
            throw new InvalidNativeArgumentException($value, ['Article body cannot exceed 65535 characters']);
        }
    }
}
