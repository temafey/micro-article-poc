<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\Event;

/**
 * DataProvider for ArticleCreatedEvent tests.
 *
 * @see \Tests\Unit\Article\Domain\Event\ArticleCreatedEventTest
 */
final class ArticleCreatedEventDataProvider
{
    /**
     * Provides valid construction data for ArticleCreatedEvent.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, articleData: array}>
     */
    public static function provideValidConstructionData(): iterable
    {
        yield 'minimal event data' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'articleData' => [
                'title' => 'Test Article Title',
                'short_description' => 'This is a short description for testing purposes.',
                'description' => 'This is a complete article article description that provides comprehensive details about the topic at hand and meets minimum length requirements.',
                'slug' => 'test-article-title',
                'status' => 'draft',
            ],
        ];

        yield 'full event data with all fields' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'articleData' => [
                'title' => 'Full Article Article',
                'short_description' => 'A complete short description with all necessary details.',
                'description' => 'This is a fully detailed article article description that contains all the necessary information for a complete article article with proper formatting.',
                'slug' => 'full-article-article',
                'status' => 'draft',
                'event_id' => 12345,
            ],
        ];
    }

    /**
     * Provides serialization test scenarios.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, title: string, description: string}>
     */
    public static function provideSerializationScenarios(): iterable
    {
        yield 'standard serialization' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'title' => 'Test Article',
            'description' => 'This is a valid test description that contains at least fifty characters for validation purposes and testing.',
        ];

        yield 'unicode content serialization' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'title' => 'Novosti dnya',
            'description' => 'Eto polnoe opisanie novostnoy stati na russkom yazyke s dostatochnym kolichestvom simvolov dlya prohozhdeniya validatsii.',
        ];
    }

    /**
     * Provides deserialization test scenarios.
     *
     * @return iterable<string, array{serializedData: array}>
     */
    public static function provideDeserializationScenarios(): iterable
    {
        yield 'minimal deserialization' => [
            'serializedData' => [
                'process_uuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'article' => [
                    'title' => 'Deserialized Title',
                    'short_description' => 'This is the short description for deserialization test.',
                    'description' => 'This is the full description that meets the minimum length requirement for proper deserialization testing.',
                    'slug' => 'deserialized-title',
                    'status' => 'draft',
                ],
            ],
        ];

        yield 'with payload deserialization' => [
            'serializedData' => [
                'process_uuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
                'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
                'article' => [
                    'title' => 'Full Deserialized Article',
                    'short_description' => 'Complete short description with payload test.',
                    'description' => 'This is a complete description for testing deserialization with payload data included in the event structure.',
                    'slug' => 'full-deserialized-article',
                    'status' => 'published',
                ],
                'payload' => [
                    'source' => 'api',
                    'user_id' => '123',
                ],
            ],
        ];
    }
}
