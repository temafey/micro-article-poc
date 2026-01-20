<?php

declare(strict_types=1);

namespace Micro\Article\Infrastructure\Service;

use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use Micro\Article\Domain\Repository\Query\ArticleRepositoryInterface;
use Micro\Article\Domain\Service\SlugUniquenessCheckerInterface;
use MicroModule\Base\Domain\ValueObject\FindCriteria;

/**
 * @class SlugUniquenessChecker
 *
 * Infrastructure implementation of slug uniqueness checking.
 * Uses the Query repository to check if a slug already exists.
 */
class SlugUniquenessChecker implements SlugUniquenessCheckerInterface
{
    public function __construct(
        protected ArticleRepositoryInterface $articleRepository,
    ) {
    }

    /**
     * Check if a slug already exists in the system.
     *
     * @param string      $slug        The slug to check for existence
     * @param string|null $excludeUuid Optional UUID to exclude from check (for updates)
     *
     * @return bool True if slug exists, false otherwise
     */
    public function slugExists(string $slug, ?string $excludeUuid = null): bool
    {
        $criteria = [
            'slug' => $slug,
        ];

        $result = $this->articleRepository->findOneBy(FindCriteria::fromNative($criteria));

        if (! $result instanceof ArticleReadModelInterface) {
            return false;
        }

        // If we're excluding a specific UUID (for updates), check if the found result is the same entity
        if ($excludeUuid !== null && $result->getUuid()->toNative() === $excludeUuid) {
            return false;
        }

        return true;
    }
}
