<?php

declare(strict_types=1);

namespace Micro\Article\Domain\ValueObject;

use MicroModule\ValueObject\Exception\InvalidNativeArgumentException;
use MicroModule\ValueObject\StringLiteral\StringLiteral as BaseStringLiteralStringLiteral;
use MicroModule\ValueObject\ValueObjectInterface;

/**
 * @class Title
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Title extends BaseStringLiteralStringLiteral
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
                'Article title is required and must be between 3 and 255 characters',
            ]);
        }

        if (strlen((string) $value) < 3) {
            throw new InvalidNativeArgumentException($value, [
                'Article title is required and must be between 3 and 255 characters',
            ]);
        }

        if (strlen((string) $value) > 255) {
            throw new InvalidNativeArgumentException($value, [
                'Article title is required and must be between 3 and 255 characters',
            ]);
        }
    }
}
