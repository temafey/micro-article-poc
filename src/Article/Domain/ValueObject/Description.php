<?php

declare(strict_types=1);

namespace Micro\Article\Domain\ValueObject;

use MicroModule\ValueObject\Exception\InvalidNativeArgumentException;
use MicroModule\ValueObject\StringLiteral\StringLiteral as BaseStringLiteralStringLiteral;
use MicroModule\ValueObject\ValueObjectInterface;

/**
 * @class Description
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Description extends BaseStringLiteralStringLiteral
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
            throw new InvalidNativeArgumentException($value, [
                'Full description is required and must be between 50 and 50,000 characters',
            ]);
        }

        if (strlen((string) $value) < 50) {
            throw new InvalidNativeArgumentException($value, [
                'Full description is required and must be between 50 and 50,000 characters',
            ]);
        }

        if (strlen((string) $value) > 50000) {
            throw new InvalidNativeArgumentException($value, [
                'Full description is required and must be between 50 and 50,000 characters',
            ]);
        }
    }
}
