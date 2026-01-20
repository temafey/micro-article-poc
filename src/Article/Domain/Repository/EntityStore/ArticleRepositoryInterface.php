<?php

declare(strict_types=1);

namespace Micro\Article\Domain\Repository\EntityStore;

use Micro\Article\Domain\Entity\ArticleEntityInterface;
use Ramsey\Uuid\UuidInterface;

/**
 * @interface ArticleRepositoryInterface
 */
interface ArticleRepositoryInterface
{
    /**
     * Retrieve ArticleEntity with applied events.
     */
    public function get(UuidInterface $uuid): ArticleEntityInterface;

    /**
     * Save ArticleEntity last uncommitted events.
     */
    public function store(ArticleEntityInterface $entity): void;
}
