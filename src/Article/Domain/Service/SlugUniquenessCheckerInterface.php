<?php

declare(strict_types=1);

namespace Micro\Article\Domain\Service;

/**
 * @interface SlugUniquenessCheckerInterface
 *
 * Domain service interface for checking slug uniqueness across all article articles.
 * Implementation should be in Infrastructure layer using repository access.
 */
interface SlugUniquenessCheckerInterface
{
    /**
     * Check if a slug already exists in the system.
     *
     * @param string      $slug        The slug to check for existence
     * @param string|null $excludeUuid Optional UUID to exclude from check (for updates)
     *
     * @return bool True if slug exists, false otherwise
     */
    public function slugExists(string $slug, ?string $excludeUuid = null): bool;
}
