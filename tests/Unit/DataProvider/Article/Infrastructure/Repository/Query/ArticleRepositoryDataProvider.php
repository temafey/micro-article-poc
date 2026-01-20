<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Infrastructure\Repository\Query;

use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;

/**
 * DataProvider for Query/ArticleRepository tests.
 */
final class ArticleRepositoryDataProvider
{
    /**
     * Data for fetchOne success scenarios.
     */
    public static function fetchOneSuccessScenarios(): \Generator
    {
        yield 'fetch one with all fields' => [
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'storeResult' => [
                ArticleReadModelInterface::KEY_UUID => '550e8400-e29b-41d4-a716-446655440000',
                'title' => 'Test Article Title',
                'slug' => 'test-article-title',
                'short_description' => 'Short description of the article.',
                'description' => 'Full description that meets the minimum requirements of fifty characters for validation testing purposes.',
                'event_id' => 12345,
                'status' => 'published',
            ],
        ];

        yield 'fetch one with draft status' => [
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'storeResult' => [
                ArticleReadModelInterface::KEY_UUID => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Draft Article',
                'slug' => 'draft-article',
                'short_description' => 'Draft short description.',
                'description' => 'Draft full description that meets the minimum requirements of fifty characters for validation testing.',
                'event_id' => 67890,
                'status' => 'draft',
            ],
        ];
    }

    /**
     * Data for findByCriteria success scenarios.
     */
    public static function findByCriteriaSuccessScenarios(): \Generator
    {
        yield 'find by status criteria' => [
            'criteria' => [
                'status' => 'published',
            ],
            'storeResult' => [
                [
                    ArticleReadModelInterface::KEY_UUID => '550e8400-e29b-41d4-a716-446655440000',
                    'title' => 'First Published Article',
                    'slug' => 'first-published-article',
                    'status' => 'published',
                ],
                [
                    ArticleReadModelInterface::KEY_UUID => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                    'title' => 'Second Published Article',
                    'slug' => 'second-published-article',
                    'status' => 'published',
                ],
            ],
            'expectedCount' => 2,
        ];

        yield 'find by event_id criteria' => [
            'criteria' => [
                'event_id' => 12345,
            ],
            'storeResult' => [
                [
                    ArticleReadModelInterface::KEY_UUID => '550e8400-e29b-41d4-a716-446655440000',
                    'title' => 'Event Article',
                    'slug' => 'event-article',
                    'event_id' => 12345,
                ],
            ],
            'expectedCount' => 1,
        ];
    }

    /**
     * Data for findOneBy success scenarios.
     */
    public static function findOneBySuccessScenarios(): \Generator
    {
        yield 'find one by slug' => [
            'criteria' => [
                'slug' => 'test-article-slug',
            ],
            'storeResult' => [
                ArticleReadModelInterface::KEY_UUID => '550e8400-e29b-41d4-a716-446655440000',
                'title' => 'Test Article',
                'slug' => 'test-article-slug',
                'status' => 'published',
            ],
        ];

        yield 'find one by uuid' => [
            'criteria' => [
                ArticleReadModelInterface::KEY_UUID => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            ],
            'storeResult' => [
                ArticleReadModelInterface::KEY_UUID => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Another Article',
                'slug' => 'another-article',
                'status' => 'draft',
            ],
        ];
    }
}
