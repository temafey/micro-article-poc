<?php

declare(strict_types=1);

namespace Micro\Article\Domain\Service;

use Cocur\Slugify\SlugifyInterface;
use MicroModule\Base\Domain\Exception\InvalidDataException;
use Symfony\Component\DependencyInjection\Attribute\Lazy;

/**
 * @class ArticleSlugGeneratorService
 *
 * Responsibilities:
 * - Slug generation and format normalization for SEO optimization
 * - Transliteration of international characters to ASCII equivalents
 * - Uniqueness validation via repository lookup across all article
 * - SEO-optimized slug formatting with keyword preservation
 *
 * Does NOT handle:
 * - URL redirect management for changed slugs (handled by infrastructure)
 * - SEO analytics or keyword optimization (separate analytics domain)
 * - Historical slug versioning (future enhancement)
 */
#[Lazy]
class ArticleSlugGeneratorService implements ArticleSlugGeneratorServiceInterface
{
    private const int MAX_SLUG_LENGTH = 255;

    private const int MAX_BASE_SLUG_LENGTH = 245; // Reserve space for counter suffix (-NN)

    private const int MIN_SLUG_LENGTH = 1;

    private const int MAX_UNIQUENESS_ATTEMPTS = 10;

    public function __construct(
        private readonly SlugifyInterface $slugify,
        private readonly SlugUniquenessCheckerInterface $uniquenessChecker,
    ) {
    }

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
     * @param string      $title        Article title to convert to slug, supports international characters
     * @param string|null $existingSlug Existing slug to preserve if title unchanged, null for new generation
     * @param string|null $excludeUuid  UUID to exclude from uniqueness check (for updates)
     */
    public function generateSlug(string $title, ?string $existingSlug = null, ?string $excludeUuid = null): string
    {
        $title = trim($title);

        if ($title === '') {
            throw new InvalidDataException('Cannot generate slug from empty title');
        }

        // Generate base slug using transliteration
        $baseSlug = $this->createBaseSlug($title);

        if ($baseSlug === '') {
            throw new InvalidDataException('Title contains no valid characters for slug generation');
        }

        // If existing slug matches the base slug pattern, preserve it if still unique
        if ($existingSlug !== null && $this->slugMatchesTitle(
            $existingSlug,
            $baseSlug
        ) && ! $this->uniquenessChecker->slugExists($existingSlug, $excludeUuid)) {
            return $existingSlug;
        }

        // Check uniqueness and append counter if needed
        return $this->ensureUniqueSlug($baseSlug, $excludeUuid);
    }

    /**
     * Validate slug conforms to URL-safe format requirements, SEO best practices, and length constraints.
     *
     * Business Rules:
     * - Alphanumeric + hyphens only, no spaces or special chars, no leading/trailing hyphens
     * - Max 255 characters, minimum 1 character, optimal 3-100 chars for SEO
     * - All characters must be lowercase, no uppercase allowed for URL consistency
     *
     * @param string $slug Slug to validate against format rules
     */
    public function validateSlugFormat(string $slug): bool
    {
        // Length validation
        $length = strlen($slug);
        if ($length < self::MIN_SLUG_LENGTH || $length > self::MAX_SLUG_LENGTH) {
            return false;
        }

        // Format validation: alphanumeric and hyphens only, no leading/trailing hyphens
        // Pattern: starts with alphanumeric, can have alphanumeric segments separated by single hyphens
        if (! preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $slug)) {
            return false;
        }

        // Lowercase validation (redundant with regex but explicit for clarity)
        return $slug === strtolower($slug);
    }

    /**
     * Create base slug from title using transliteration and normalization.
     */
    private function createBaseSlug(string $title): string
    {
        // Use slugify for transliteration and basic normalization
        $slug = $this->slugify->slugify($title);

        // Ensure lowercase
        $slug = strtolower($slug);

        // Remove any remaining special characters (safety net)
        $slug = preg_replace('/[^a-z0-9-]/', '', $slug);

        // Collapse multiple hyphens to single
        $slug = preg_replace('/-+/', '-', (string) $slug);

        // Trim leading/trailing hyphens
        $slug = trim((string) $slug, '-');

        // Truncate to max base length (reserve space for counter)
        if (strlen($slug) > self::MAX_BASE_SLUG_LENGTH) {
            $slug = substr($slug, 0, self::MAX_BASE_SLUG_LENGTH);
            // Ensure we don't end with a hyphen after truncation
            $slug = rtrim($slug, '-');
        }

        return $slug;
    }

    /**
     * Check if existing slug matches the base slug pattern.
     * Allows for existing slug to be base slug or base slug with counter suffix.
     */
    private function slugMatchesTitle(string $existingSlug, string $baseSlug): bool
    {
        // Exact match
        if ($existingSlug === $baseSlug) {
            return true;
        }

        // Match with counter suffix (e.g., "my-title-2")
        return (bool) preg_match('/^' . preg_quote($baseSlug, '/') . '-\d+$/', $existingSlug);
    }

    /**
     * Ensure slug uniqueness by appending counter if necessary.
     */
    private function ensureUniqueSlug(string $baseSlug, ?string $excludeUuid): string
    {
        // Try base slug first
        if (! $this->uniquenessChecker->slugExists($baseSlug, $excludeUuid)) {
            return $baseSlug;
        }

        // Try with counter suffix
        for ($counter = 1; $counter <= self::MAX_UNIQUENESS_ATTEMPTS; ++$counter) {
            $candidateSlug = $baseSlug . '-' . $counter;

            if (! $this->uniquenessChecker->slugExists($candidateSlug, $excludeUuid)) {
                return $candidateSlug;
            }
        }

        throw new InvalidDataException(sprintf(
            'Unable to generate unique slug for base "%s" after %d attempts',
            $baseSlug,
            self::MAX_UNIQUENESS_ATTEMPTS
        ));
    }
}
