<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\ValueObject;

/**
 * DataProvider for Article ValueObject tests.
 *
 * Note: Description requires between 50 and 50,000 characters.
 *
 * @see \Tests\Unit\Article\Domain\ValueObject\ArticleTest
 */
final class ArticleDataProvider
{
    /**
     * Provides complete article data for construction.
     *
     * @return iterable<string, array{data: array}>
     */
    public static function provideCompleteArticleData(): iterable
    {
        yield 'full article with all fields' => [
            'data' => [
                'title' => 'Test Article Article',
                'short_description' => 'This is a short description for testing.',
                'description' => 'This is a complete article article description that provides comprehensive details about the topic at hand with sufficient length for testing.',
                'slug' => 'test-article-article',
                'event_id' => 12345,
                'status' => 'draft',
                'published_at' => '2024-01-15T10:30:00+00:00',
                'archived_at' => null,
                'created_at' => '2024-01-01T00:00:00+00:00',
                'updated_at' => '2024-01-15T10:30:00+00:00',
            ],
        ];

        yield 'published article' => [
            'data' => [
                'title' => 'Published Article Article',
                'short_description' => 'Short description of published article.',
                'description' => 'This is a published article article with a complete description that meets the minimum character requirements for proper testing.',
                'slug' => 'published-article-article',
                'event_id' => 67890,
                'status' => 'published',
                'published_at' => '2024-02-01T12:00:00+00:00',
                'archived_at' => null,
                'created_at' => '2024-01-20T00:00:00+00:00',
                'updated_at' => '2024-02-01T12:00:00+00:00',
            ],
        ];

        yield 'archived article' => [
            'data' => [
                'title' => 'Archived Article Article',
                'short_description' => 'Short description of archived article.',
                'description' => 'This is an archived article article with a complete description that meets the minimum character requirements for proper testing.',
                'slug' => 'archived-article-article',
                'event_id' => 11111,
                'status' => 'archived',
                'published_at' => '2024-01-01T12:00:00+00:00',
                'archived_at' => '2024-03-01T00:00:00+00:00',
                'created_at' => '2023-12-01T00:00:00+00:00',
                'updated_at' => '2024-03-01T00:00:00+00:00',
            ],
        ];
    }

    /**
     * Provides minimal article data for construction.
     *
     * @return iterable<string, array{data: array}>
     */
    public static function provideMinimalArticleData(): iterable
    {
        yield 'only title' => [
            'data' => [
                'title' => 'Minimal Article Article',
            ],
        ];

        yield 'title and short description' => [
            'data' => [
                'title' => 'Article With Short Description',
                'short_description' => 'Just a short description.',
            ],
        ];

        yield 'title, short description, and description' => [
            'data' => [
                'title' => 'Article With Descriptions',
                'short_description' => 'A short description here.',
                'description' => 'This is the full description of the article article that meets the minimum fifty character requirement for proper validation testing.',
            ],
        ];
    }

    /**
     * Provides scenarios for toArray conversion.
     *
     * @return iterable<string, array{data: array, expectedKeys: array}>
     */
    public static function provideToArrayScenarios(): iterable
    {
        yield 'full data to array' => [
            'data' => [
                'title' => 'Full Article',
                'short_description' => 'Short description.',
                'description' => 'Full description that meets the minimum requirements of fifty characters for validation testing purposes.',
                'slug' => 'full-article',
                'status' => 'draft',
            ],
            'expectedKeys' => ['title', 'short_description', 'description', 'slug', 'status'],
        ];

        yield 'partial data to array' => [
            'data' => [
                'title' => 'Partial Article',
                'status' => 'published',
            ],
            'expectedKeys' => ['title', 'status'],
        ];
    }

    /**
     * Provides scenarios for getter methods.
     *
     * @return iterable<string, array{data: array, field: string, expectedValue: mixed}>
     */
    public static function provideGetterScenarios(): iterable
    {
        yield 'get title' => [
            'data' => [
                'title' => 'Test Title',
            ],
            'field' => 'title',
            'expectedValue' => 'Test Title',
        ];

        yield 'get short description' => [
            'data' => [
                'short_description' => 'Short description text',
            ],
            'field' => 'shortDescription',
            'expectedValue' => 'Short description text',
        ];

        yield 'get status' => [
            'data' => [
                'status' => 'published',
            ],
            'field' => 'status',
            'expectedValue' => 'published',
        ];

        yield 'get event id' => [
            'data' => [
                'event_id' => 12345,
            ],
            'field' => 'eventId',
            'expectedValue' => 12345,
        ];

        yield 'get slug' => [
            'data' => [
                'slug' => 'test-slug',
            ],
            'field' => 'slug',
            'expectedValue' => 'test-slug',
        ];
    }
}
