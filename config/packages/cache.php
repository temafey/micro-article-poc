<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * Cache configuration migrated from YAML to PHP for OPcache optimization.
 *
 * Domain-specific cache pools for DDD/Event Sourcing architecture:
 * - event_store.cache: Long TTL for immutable events (24h)
 * - read_model.cache: Medium TTL for projections (1h)
 * - query.cache: Short TTL for query results (5min)
 * - slug.cache: For generated slugs uniqueness checks (24h)
 * - api.cache: Very short TTL for API responses (1min)
 *
 * MsgPack marshaller is configured via service decoration in services.yaml
 *
 * @see Micro\Component\Common\Infrastructure\Cache\AdaptiveMsgPackMarshaller
 * @see docs/updates/phase-5-advanced/TASK-015-php-array-config.md
 */
return static function (ContainerConfigurator $container): void {
    // Build Redis provider URL from environment variables
    $redisProvider = 'redis://%env(APP_REDIS_HOST)%:%env(APP_REDIS_PORT)%';

    $container->extension('framework', [
        'cache' => [
            // Unique namespace prefix for cache keys
            'prefix_seed' => 'micro_article_system',

            // Use Redis as default cache adapter with tag support
            'app' => 'cache.adapter.redis_tag_aware',
            'default_redis_provider' => $redisProvider,

            // Domain-specific cache pools for DDD/Event Sourcing architecture
            'pools' => [
                // Event Store cache - long TTL for immutable events
                // Used by: Event Sourcing repositories, Broadway event stores
                'event_store.cache' => [
                    'adapter' => 'cache.adapter.redis_tag_aware',
                    'default_lifetime' => 86400, // 24 hours - events are immutable
                    'provider' => $redisProvider,
                ],

                // Read Model cache - medium TTL for projections
                // Used by: ArticleProjector, ReadModel repositories
                'read_model.cache' => [
                    'adapter' => 'cache.adapter.redis_tag_aware',
                    'default_lifetime' => 3600, // 1 hour
                    'provider' => $redisProvider,
                ],

                // Query cache - short TTL for query results
                // Used by: Query handlers, CQRS read side
                'query.cache' => [
                    'adapter' => 'cache.adapter.redis_tag_aware',
                    'default_lifetime' => 300, // 5 minutes
                    'provider' => $redisProvider,
                ],

                // Slug cache - for generated slugs (uniqueness checks)
                // Used by: ArticleSlugGeneratorService
                'slug.cache' => [
                    'adapter' => 'cache.adapter.redis_tag_aware',
                    'default_lifetime' => 86400, // 24 hours
                    'provider' => $redisProvider,
                ],

                // API response cache - very short TTL
                // Used by: REST controllers, API versioning
                'api.cache' => [
                    'adapter' => 'cache.adapter.redis_tag_aware',
                    'default_lifetime' => 60, // 1 minute
                    'provider' => $redisProvider,
                ],
            ],
        ],
    ]);
};
