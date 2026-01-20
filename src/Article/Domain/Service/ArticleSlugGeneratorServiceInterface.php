<?php

declare(strict_types=1);

namespace Micro\Article\Domain\Service;

/**
 * @interface ArticleSlugGeneratorServiceInterface
 */
interface ArticleSlugGeneratorServiceInterface
{
    /**
     * Primary slug generation from title with transliteration, URL-safe formatting, and uniqueness validation.
     *
     * Business Rules:
     * - Convert non-ASCII to ASCII using transliteration rules (cocur/slugify library pattern)
     * - Lowercase, replace spaces/underscores with hyphens, trim leading/trailing hyphens
     * - Remove all non-alphanumeric except hyphens, collapse multiple hyphens to single
     * - Query repository for slug collision across all article statuses
     * - If collision, append -1, -2, etc. up to -10 attempts, fail if all collide
     *
     * @param string      $title        Article title to convert to slug
     * @param string|null $existingSlug Existing slug to preserve if title unchanged, null for new generation
     * @param string|null $excludeUuid  UUID to exclude from uniqueness check (for updates)
     */
    public function generateSlug(string $title, ?string $existingSlug = null, ?string $excludeUuid = null): string;

    /**
     * Validate slug conforms to URL-safe format requirements, SEO best practices, and length constraints.
     *
     * Business Rules:
     * - Alphanumeric + hyphens only, no spaces or special chars, no leading/trailing hyphens
     * - Max 255 characters, minimum 1 character, optimal 3-100 chars for SEO
     * - All characters must be lowercase, no uppercase allowed for URL consistency
     */
    public function validateSlugFormat(string $slug): bool;
}
