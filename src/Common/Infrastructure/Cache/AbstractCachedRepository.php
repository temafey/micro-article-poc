<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Cache;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

/**
 * Abstract base class for cached repository decorators.
 *
 * Provides common caching infrastructure with stampede prevention
 * and logging capabilities for DDD repository decorators.
 */
abstract class AbstractCachedRepository
{
    use CacheStampedeHelper;

    /**
     * Default beta value for XFetch algorithm (1.0 is mathematically optimal).
     */
    protected const float BETA = 1.0;

    /**
     * Default TTL in seconds (5 minutes).
     */
    protected const int DEFAULT_TTL = 300;

    public function __construct(
        protected readonly TagAwareCacheInterface $cache,
        protected readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Log a cache miss for debugging and metrics.
     *
     * @param string $key     The cache key that missed
     * @param string $context Additional context (e.g., method name)
     */
    protected function logCacheMiss(string $key, string $context = ''): void
    {
        $this->logger->debug('Cache MISS', [
            'key' => $key,
            'context' => $context,
            'repository' => static::class,
        ]);
    }

    /**
     * Log a cache hit for debugging and metrics.
     *
     * @param string $key     The cache key that hit
     * @param string $context Additional context (e.g., method name)
     */
    protected function logCacheHit(string $key, string $context = ''): void
    {
        $this->logger->debug('Cache HIT', [
            'key' => $key,
            'context' => $context,
            'repository' => static::class,
        ]);
    }

    /**
     * Log cache invalidation.
     *
     * @param array<string> $tags The tags that were invalidated
     */
    protected function logCacheInvalidation(array $tags): void
    {
        $this->logger->debug('Cache invalidated', [
            'tags' => $tags,
            'repository' => static::class,
        ]);
    }
}
