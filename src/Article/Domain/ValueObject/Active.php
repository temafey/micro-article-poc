<?php

declare(strict_types=1);

namespace Micro\Article\Domain\ValueObject;

use MicroModule\ValueObject\Logical\Boolean;

/**
 * Active ValueObject - represents the active/inactive state of a Article entity.
 *
 * Extends Boolean to provide boolean-like construction from:
 * - bool (true/false)
 */
final class Active extends Boolean
{
    /**
     * Check if the article item is active.
     *
     * @return bool True if active, false otherwise
     */
    public function isActive(): bool
    {
        return $this->toNative();
    }
}
