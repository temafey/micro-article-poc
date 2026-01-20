<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Infrastructure\Repository\Query;

use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;

/**
 * DataProvider for CachedArticleQueryRepository tests.
 */
final class CachedArticleQueryRepositoryDataProvider
{
    private const string UUID_1 = '550e8400-e29b-41d4-a716-446655440000';
    private const string UUID_2 = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

    /**
     * Data for fetchOne cache scenarios.
     */
    public static function fetchOneCacheScenarios(): \Generator
    {
        yield 'fetch one - cache hit' => [
            'uuid' => self::UUID_1,
            'expectedCacheKey' => 'article.query.item.' . self::UUID_1,
            'expectedTags' => ['article', 'article.' . self::UUID_1],
            'cachedValue' => self::createMockReadModelData(self::UUID_1, 'Cached Article'),
        ];

        yield 'fetch one - cache miss' => [
            'uuid' => self::UUID_2,
            'expectedCacheKey' => 'article.query.item.' . self::UUID_2,
            'expectedTags' => ['article', 'article.' . self::UUID_2],
            'cachedValue' => null,
        ];
    }

    /**
     * Data for findByCriteria cache scenarios.
     */
    public static function findByCriteriaCacheScenarios(): \Generator
    {
        $statusCriteria = [
            'status' => 'published',
        ];
        $statusCriteriaHash = md5(serialize($statusCriteria));

        yield 'find by criteria - status published' => [
            'criteria' => $statusCriteria,
            'expectedCacheKeyPrefix' => 'article.query.criteria.',
            'expectedTags' => ['article', 'article.list'],
            'cachedValue' => [
                self::createMockReadModelData(self::UUID_1, 'Published Article 1'),
                self::createMockReadModelData(self::UUID_2, 'Published Article 2'),
            ],
        ];

        $eventCriteria = [
            'event_id' => 12345,
        ];

        yield 'find by criteria - event_id filter' => [
            'criteria' => $eventCriteria,
            'expectedCacheKeyPrefix' => 'article.query.criteria.',
            'expectedTags' => ['article', 'article.list'],
            'cachedValue' => [self::createMockReadModelData(self::UUID_1, 'Event Article')],
        ];

        yield 'find by criteria - empty result' => [
            'criteria' => [
                'status' => 'nonexistent',
            ],
            'expectedCacheKeyPrefix' => 'article.query.criteria.',
            'expectedTags' => ['article', 'article.list'],
            'cachedValue' => null,
        ];
    }

    /**
     * Data for findOneBy cache scenarios.
     */
    public static function findOneByCacheScenarios(): \Generator
    {
        $slugCriteria = [
            'slug' => 'test-article-slug',
        ];

        yield 'find one by - slug' => [
            'criteria' => $slugCriteria,
            'expectedCacheKeyPrefix' => 'article.query.one.',
            'expectedTags' => ['article'],
            'cachedValue' => self::createMockReadModelData(self::UUID_1, 'Found by Slug'),
        ];

        $uuidCriteria = [
            ArticleReadModelInterface::KEY_UUID => self::UUID_1,
        ];

        yield 'find one by - uuid' => [
            'criteria' => $uuidCriteria,
            'expectedCacheKeyPrefix' => 'article.query.one.',
            'expectedTags' => ['article'],
            'cachedValue' => self::createMockReadModelData(self::UUID_1, 'Found by UUID'),
        ];

        yield 'find one by - not found' => [
            'criteria' => [
                'slug' => 'nonexistent-slug',
            ],
            'expectedCacheKeyPrefix' => 'article.query.one.',
            'expectedTags' => ['article'],
            'cachedValue' => null,
        ];
    }

    /**
     * Data for TTL configuration scenarios.
     */
    public static function ttlConfigurationScenarios(): \Generator
    {
        yield 'item TTL is 15 minutes' => [
            'method' => 'fetchOne',
            'expectedTtl' => 900,
        ];

        yield 'criteria TTL is 5 minutes' => [
            'method' => 'findByCriteria',
            'expectedTtl' => 300,
        ];

        yield 'findOne TTL is 10 minutes' => [
            'method' => 'findOneBy',
            'expectedTtl' => 600,
        ];
    }

    /**
     * Create mock read model data array.
     *
     * @param string $uuid  The UUID
     * @param string $title The title
     *
     * @return array<string, mixed>
     */
    private static function createMockReadModelData(string $uuid, string $title): array
    {
        return [
            ArticleReadModelInterface::KEY_UUID => $uuid,
            'title' => $title,
            'slug' => strtolower(str_replace(' ', '-', $title)),
            'short_description' => 'Short description for ' . $title,
            'description' => 'Full description with at least fifty characters for ' . $title . ' validation testing.',
            'body' => '<p>Body content for ' . $title . '</p>',
            'status' => 'published',
            'active' => true,
            'deleted' => false,
        ];
    }
}
