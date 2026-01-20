<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\Event;

/**
 * DataProvider for ArticleUpdatedEvent tests.
 *
 * @see \Tests\Unit\Article\Domain\Event\ArticleUpdatedEventTest
 */
final class ArticleUpdatedEventDataProvider
{
    /**
     * Provides valid construction scenarios.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, articleData: array}>
     */
    public static function provideValidConstructionData(): iterable
    {
        yield 'standard update event' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'articleData' => [
                'title' => 'Updated Article Title',
                'short_description' => 'This is an updated short description for testing.',
                'description' => 'This is an updated description that meets the fifty character minimum for testing purposes and validation.',
            ],
        ];

        yield 'update event with all fields' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'articleData' => [
                'title' => 'Complete Updated Article',
                'short_description' => 'Complete updated short description.',
                'description' => 'This is a complete updated description with all optional fields for testing purposes and validation.',
                'slug' => 'complete-updated-article',
                'status' => 'draft',
            ],
        ];
    }

    /**
     * Provides serialize/deserialize scenarios.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, articleData: array}>
     */
    public static function provideSerializationScenarios(): iterable
    {
        yield 'minimal update data' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'articleData' => [
                'title' => 'Minimal Update Title',
                'short_description' => 'Minimal update short description.',
                'description' => 'This is a minimal update description that meets the minimum length requirement for testing.',
            ],
        ];

        yield 'full update data with payload' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'articleData' => [
                'title' => 'Full Update Article',
                'short_description' => 'Full update short description with details.',
                'description' => 'This is a comprehensive update description with all fields populated for testing purposes.',
                'slug' => 'full-update-article',
                'status' => 'published',
                'event_id' => 67890,
            ],
        ];
    }
}
