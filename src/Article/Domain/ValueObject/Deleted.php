<?php

declare(strict_types=1);

namespace Micro\Article\Domain\ValueObject;

use MicroModule\ValueObject\Logical\Boolean as BaseBoolean;

/**
 * @class Deleted
 *
 * Represents the deletion state of a article entity.
 * This is a boolean-like value object that encapsulates whether an entity is marked as deleted.
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class Deleted extends BaseBoolean
{
    /**
     * Check if the entity is marked as deleted.
     */
    public function isDeleted(): bool
    {
        return $this->toNative();
    }

    /**
     * Factory method to create a "deleted" state.
     */
    public static function deleted(): self
    {
        return new self(true);
    }

    /**
     * Factory method to create a "not deleted" state.
     */
    public static function notDeleted(): self
    {
        return new self(false);
    }
}
