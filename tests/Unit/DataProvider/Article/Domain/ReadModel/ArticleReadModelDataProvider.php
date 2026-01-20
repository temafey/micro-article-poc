<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\ReadModel;

/**
 * DataProvider for ArticleReadModel tests.
 *
 * Note: Description requires between 50 and 50,000 characters.
 *
 * @see \Tests\Unit\Article\Domain\ReadModel\ArticleReadModelTest
 */
final class ArticleReadModelDataProvider
{
    /**
     * Provides complete data for read model creation.
     *
     * @return iterable<string, array{uuid: string, articleData: array}>
     */
    public static function provideCompleteReadModelData(): iterable
    {
        yield 'published article' => [
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'articleData' => [
                'title' => 'Published Article Article',
                'short_description' => 'Short description of published article.',
                'description' => 'This is a complete description for the published article article that meets the minimum character requirements for testing.',
                'slug' => 'published-article-article',
                'event_id' => 12345,
                'status' => 'published',
                'published_at' => '2024-01-15T10:30:00+00:00',
                'created_at' => '2024-01-01T00:00:00+00:00',
                'updated_at' => '2024-01-15T10:30:00+00:00',
            ],
        ];

        yield 'draft article' => [
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'articleData' => [
                'title' => 'Draft Article Article',
                'short_description' => 'Short description of draft article.',
                'description' => 'This is a complete description for the draft article article that meets the minimum character requirements for testing.',
                'slug' => 'draft-article-article',
                'status' => 'draft',
                'created_at' => '2024-02-01T00:00:00+00:00',
                'updated_at' => '2024-02-01T00:00:00+00:00',
            ],
        ];

        yield 'archived article' => [
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'articleData' => [
                'title' => 'Archived Article Article',
                'short_description' => 'Short description of archived article.',
                'description' => 'This is a complete description for the archived article article that meets the minimum character requirements for testing.',
                'slug' => 'archived-article-article',
                'event_id' => 99999,
                'status' => 'archived',
                'published_at' => '2024-01-01T12:00:00+00:00',
                'archived_at' => '2024-03-01T00:00:00+00:00',
                'created_at' => '2023-12-01T00:00:00+00:00',
                'updated_at' => '2024-03-01T00:00:00+00:00',
            ],
        ];
    }

    /**
     * Provides minimal data for read model creation.
     *
     * @return iterable<string, array{uuid: string, articleData: array}>
     */
    public static function provideMinimalReadModelData(): iterable
    {
        yield 'title only' => [
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'articleData' => [
                'title' => 'Minimal Article',
            ],
        ];

        yield 'title and status' => [
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'articleData' => [
                'title' => 'Article with Status',
                'status' => 'draft',
            ],
        ];
    }

    /**
     * Provides scenarios for toArray conversion.
     *
     * @return iterable<string, array{uuid: string, articleData: array, expectedKeys: array}>
     */
    public static function provideToArrayScenarios(): iterable
    {
        yield 'complete data to array' => [
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'articleData' => [
                'title' => 'Complete Article',
                'short_description' => 'Short description.',
                'description' => 'Full description that meets the minimum requirements of fifty characters for validation testing purposes.',
                'slug' => 'complete-article',
                'status' => 'published',
            ],
            'expectedKeys' => ['uuid', 'title', 'short_description', 'description', 'slug', 'status'],
        ];
    }
}
