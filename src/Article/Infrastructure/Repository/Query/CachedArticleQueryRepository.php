<?php

declare(strict_types=1);

namespace Micro\Article\Infrastructure\Repository\Query;

use Micro\Component\Common\Infrastructure\Cache\AbstractCachedRepository;
use Micro\Article\Domain\Factory\ReadModelFactoryInterface;
use Micro\Article\Domain\Factory\ValueObjectFactoryInterface;
use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use Micro\Article\Domain\Repository\Query\ArticleRepositoryInterface;
use MicroModule\Base\Domain\ValueObject\FindCriteria;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Cached decorator for Article Query Repository.
 *
 * Implements cache stampede prevention using the XFetch algorithm
 * with tag-based invalidation driven by Broadway domain events.
 *
 * IMPORTANT: Caches primitive array data, NOT objects, to avoid PHP serialization
 * issues with Value Objects (UUID's internal property becomes uninitialized after unserialize).
 *
 * @see ADR-007 Cache Stampede Prevention
 */
#[AsDecorator(decorates: ArticleRepositoryInterface::class, priority: 10)]
final class CachedArticleQueryRepository extends AbstractCachedRepository implements ArticleRepositoryInterface
{
    /**
     * TTL for single item queries (15 minutes - article rarely changes after publish).
     */
    private const int ITEM_TTL = 900;

    /**
     * TTL for criteria-based list queries (5 minutes - lists change with new articles).
     */
    private const int CRITERIA_TTL = 300;

    /**
     * TTL for findOneBy queries (10 minutes - moderate change frequency).
     */
    private const int FIND_ONE_TTL = 600;

    /**
     * Cache key prefix for Article queries.
     */
    private const string KEY_PREFIX = 'article.query';

    public function __construct(
        #[AutowireDecorated]
        private readonly ArticleRepositoryInterface $inner,
        #[Autowire(service: 'query.cache')]
        TagAwareCacheInterface $cache,
        LoggerInterface $logger,
        private readonly ReadModelFactoryInterface $readModelFactory,
        private readonly ValueObjectFactoryInterface $valueObjectFactory,
    ) {
        parent::__construct($cache, $logger);
    }

    public function fetchOne(Uuid $uuid): ?ArticleReadModelInterface
    {
        $uuidString = $uuid->toString();
        $key = $this->generateCacheKey(self::KEY_PREFIX . '.item', $uuidString);
        $tags = $this->generateCacheTags('article', $uuidString);

        // Cache array data, not objects (avoids UUID serialization issues)
        $cachedData = $this->fetchWithStampedePrevention(
            $this->cache,
            $key,
            function () use ($uuid, $uuidString): ?array {
                $this->logCacheMiss($uuidString, 'fetchOne');

                $result = $this->inner->fetchOne($uuid);

                return $result?->toArray();
            },
            $tags,
            self::ITEM_TTL,
            self::BETA
        );

        return $this->reconstructReadModel($cachedData);
    }

    public function findByCriteria(FindCriteria $findCriteria): ?array
    {
        $criteriaHash = $this->generateCriteriaHash($findCriteria->toNative());
        $key = $this->generateCacheKey(self::KEY_PREFIX . '.criteria', $criteriaHash);
        $tags = ['article', 'article.list'];

        // Cache array data, not objects (avoids UUID serialization issues)
        $cachedData = $this->fetchWithStampedePrevention(
            $this->cache,
            $key,
            function () use ($findCriteria, $criteriaHash): ?array {
                $this->logCacheMiss($criteriaHash, 'findByCriteria');

                $results = $this->inner->findByCriteria($findCriteria);

                if ($results === null) {
                    return null;
                }

                // Convert each ReadModel to array for safe caching
                return array_map(
                    static fn (ArticleReadModelInterface $readModel): array => $readModel->toArray(),
                    $results
                );
            },
            $tags,
            self::CRITERIA_TTL,
            self::BETA
        );

        if ($cachedData === null) {
            return null;
        }

        // Reconstruct ReadModel objects from cached arrays
        return array_map(
            fn (array $data): ArticleReadModelInterface => $this->reconstructReadModel($data),
            $cachedData
        );
    }

    public function findOneBy(FindCriteria $findCriteria): ?ArticleReadModelInterface
    {
        $criteriaHash = $this->generateCriteriaHash($findCriteria->toNative());
        $key = $this->generateCacheKey(self::KEY_PREFIX . '.one', $criteriaHash);
        $tags = ['article'];

        // Cache array data, not objects (avoids UUID serialization issues)
        $cachedData = $this->fetchWithStampedePrevention(
            $this->cache,
            $key,
            function () use ($findCriteria, $criteriaHash): ?array {
                $this->logCacheMiss($criteriaHash, 'findOneBy');

                $result = $this->inner->findOneBy($findCriteria);

                return $result?->toArray();
            },
            $tags,
            self::FIND_ONE_TTL,
            self::BETA
        );

        return $this->reconstructReadModel($cachedData);
    }

    /**
     * Reconstruct ArticleReadModel from cached array data.
     *
     * This ensures Value Objects (especially UUID) are properly constructed
     * with their constructors called, avoiding serialization issues.
     *
     * @param array<string, mixed>|null $data Cached array data
     *
     * @return ArticleReadModelInterface|null Reconstructed ReadModel or null
     */
    private function reconstructReadModel(?array $data): ?ArticleReadModelInterface
    {
        if ($data === null) {
            return null;
        }

        $uuid = $data[ArticleReadModelInterface::KEY_UUID] ?? null;

        if ($uuid === null) {
            return null;
        }

        unset($data[ArticleReadModelInterface::KEY_UUID]);

        return $this->readModelFactory->makeArticleActualInstance(
            $this->valueObjectFactory->makeArticle($data),
            $this->valueObjectFactory->makeUuid($uuid)
        );
    }
}
