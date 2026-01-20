<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\Factory;

/**
 * DataProvider for EventFactory tests.
 *
 * @see \Tests\Unit\Article\Domain\Factory\EventFactoryTest
 */
final class EventFactoryDataProvider
{
    /**
     * Provides scenarios for creating ArticleCreatedEvent.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, articleData: array}>
     */
    public static function provideArticleCreatedEventScenarios(): iterable
    {
        yield 'standard article creation' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'articleData' => [
                'title' => 'New Article Title',
                'short_description' => 'Short description for the article.',
                'description' => 'This is a complete description that meets the minimum requirements for testing.',
                'slug' => 'new-article-title',
                'status' => 'draft',
            ],
        ];

        yield 'article with event id' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'articleData' => [
                'title' => 'Event Related Article',
                'short_description' => 'Article about an event.',
                'description' => 'This is a complete description about an event-related article article.',
                'slug' => 'event-related-article',
                'event_id' => 12345,
                'status' => 'draft',
            ],
        ];
    }

    /**
     * Provides scenarios for creating ArticleUpdatedEvent.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, articleData: array}>
     */
    public static function provideArticleUpdatedEventScenarios(): iterable
    {
        yield 'standard article update' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'articleData' => [
                'title' => 'Updated Article Title',
                'short_description' => 'Updated short description.',
                'description' => 'This is an updated description that meets the minimum requirements.',
                'slug' => 'updated-article-title',
                'status' => 'draft',
            ],
        ];
    }

    /**
     * Provides scenarios for creating ArticlePublishedEvent.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, status: string, publishedAt: string, updatedAt: string}>
     */
    public static function provideArticlePublishedEventScenarios(): iterable
    {
        yield 'standard publish' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'status' => 'published',
            'publishedAt' => '2024-01-15T10:30:00+00:00',
            'updatedAt' => '2024-01-15T10:30:00+00:00',
        ];
    }

    /**
     * Provides scenarios for creating ArticleUnpublishedEvent.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, status: string, updatedAt: string}>
     */
    public static function provideArticleUnpublishedEventScenarios(): iterable
    {
        yield 'standard unpublish' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'status' => 'draft',
            'updatedAt' => '2024-01-20T10:30:00+00:00',
        ];
    }

    /**
     * Provides scenarios for creating ArticleArchivedEvent.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, status: string, archivedAt: string, updatedAt: string}>
     */
    public static function provideArticleArchivedEventScenarios(): iterable
    {
        yield 'standard archive' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'status' => 'archived',
            'archivedAt' => '2024-02-01T00:00:00+00:00',
            'updatedAt' => '2024-02-01T00:00:00+00:00',
        ];
    }

    /**
     * Provides scenarios for creating ArticleDeletedEvent.
     *
     * @return iterable<string, array{processUuid: string, uuid: string}>
     */
    public static function provideArticleDeletedEventScenarios(): iterable
    {
        yield 'standard delete' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
        ];
    }
}
