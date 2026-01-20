<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\Factory;

/**
 * DataProvider for EntityFactory tests.
 *
 * @see \Tests\Unit\Article\Domain\Factory\EntityFactoryTest
 */
final class EntityFactoryDataProvider
{
    /**
     * Provides scenarios for creating ArticleEntity.
     *
     * @return iterable<string, array{processUuid: string, articleData: array}>
     */
    public static function provideCreateArticleInstanceScenarios(): iterable
    {
        yield 'standard article creation' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'articleData' => [
                'title' => 'Test Article Article',
                'short_description' => 'Short description for testing.',
                'description' => 'This is a complete article article description that provides comprehensive details.',
                'slug' => 'test-article-article',
                'status' => 'draft',
            ],
        ];

        yield 'article with event id' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'articleData' => [
                'title' => 'Event Article Article',
                'short_description' => 'Article about an event.',
                'description' => 'This is a complete description of event-related article with sufficient length.',
                'slug' => 'event-article-article',
                'event_id' => 12345,
                'status' => 'draft',
            ],
        ];
    }

    /**
     * Provides scenarios for creating actual ArticleEntity.
     *
     * @return iterable<string, array{uuid: string, articleData: array}>
     */
    public static function provideMakeActualArticleInstanceScenarios(): iterable
    {
        yield 'standard actual instance' => [
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'articleData' => [
                'title' => 'Actual Article Article',
                'short_description' => 'Short description for actual article.',
                'description' => 'This is a complete description for an actual article entity instance.',
                'slug' => 'actual-article-article',
                'status' => 'published',
            ],
        ];

        yield 'actual instance with all fields' => [
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'articleData' => [
                'title' => 'Complete Actual Article',
                'short_description' => 'Complete short description.',
                'description' => 'This is a complete description with all fields for the actual article entity.',
                'slug' => 'complete-actual-article',
                'event_id' => 99999,
                'status' => 'archived',
                'created_at' => '2024-01-01T00:00:00+00:00',
                'updated_at' => '2024-01-15T10:30:00+00:00',
            ],
        ];
    }
}
