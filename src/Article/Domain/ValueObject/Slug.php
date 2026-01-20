<?php

declare(strict_types=1);

namespace Micro\Article\Domain\ValueObject;

use MicroModule\ValueObject\Exception\InvalidNativeArgumentException;
use MicroModule\ValueObject\ValueObjectInterface;
use MicroModule\ValueObject\Web\Path as BaseWebPath;

/**
 * @class Slug
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Slug extends BaseWebPath
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
                'Slug must be URL-safe: lowercase alphanumeric with hyphens, 3-255 characters',
            ]);
        }

        if (strlen((string) $value) < 3) {
            throw new InvalidNativeArgumentException($value, [
                'Slug must be URL-safe: lowercase alphanumeric with hyphens, 3-255 characters',
            ]);
        }

        if (strlen((string) $value) > 255) {
            throw new InvalidNativeArgumentException($value, [
                'Slug must be URL-safe: lowercase alphanumeric with hyphens, 3-255 characters',
            ]);
        }

        if (! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', (string) $value)) {
            throw new InvalidNativeArgumentException($value, [
                'Slug must be URL-safe: lowercase alphanumeric with hyphens, 3-255 characters',
            ]);
        }
    }
}
