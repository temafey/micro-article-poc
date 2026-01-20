<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\Factory;

/**
 * DataProvider for ReadModelFactory tests.
 *
 * Note: Description requires between 50 and 50,000 characters.
 *
 * @see \Tests\Unit\Article\Domain\Factory\ReadModelFactoryTest
 */
final class ReadModelFactoryDataProvider
{
    /**
     * Provides scenarios for creating ArticleReadModel from value object.
     *
     * @return iterable<string, array{uuid: string, articleData: array}>
     */
    public static function provideMakeArticleActualInstanceScenarios(): iterable
    {
        yield 'minimal article read model' => [
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'articleData' => [
                'title' => 'Test Article',
                'short_description' => 'Short description.',
                'description' => 'Full description that meets the minimum requirements of fifty characters for validation testing purposes.',
            ],
        ];

        yield 'complete article read model' => [
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'articleData' => [
                'title' => 'Complete Article Article',
                'short_description' => 'Complete short description here.',
                'description' => 'This is a complete description that meets all the requirements for testing purposes and validation.',
                'slug' => 'complete-article-article',
                'event_id' => 12345,
                'status' => 'published',
                'published_at' => '2024-01-15T10:30:00+00:00',
                'created_at' => '2024-01-01T00:00:00+00:00',
                'updated_at' => '2024-01-15T10:30:00+00:00',
            ],
        ];
    }

    /**
     * Provides scenarios for creating ArticleReadModel from entity.
     *
     * @return iterable<string, array{uuid: string, articleData: array}>
     */
    public static function provideMakeArticleActualInstanceByEntityScenarios(): iterable
    {
        yield 'entity with basic data' => [
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'articleData' => [
                'title' => 'Entity Article',
                'short_description' => 'Entity short description.',
                'description' => 'This is the entity description that meets the requirements for proper testing and validation.',
                'status' => 'draft',
            ],
        ];

        yield 'published entity' => [
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'articleData' => [
                'title' => 'Published Entity Article',
                'short_description' => 'Published entity short description.',
                'description' => 'This is a published entity description with all required fields populated for testing purposes.',
                'slug' => 'published-entity-article',
                'status' => 'published',
                'published_at' => '2024-02-01T12:00:00+00:00',
            ],
        ];
    }
}
