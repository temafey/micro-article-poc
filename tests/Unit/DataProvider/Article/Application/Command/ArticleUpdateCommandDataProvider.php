<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\Command;

/**
 * DataProvider for ArticleUpdateCommand tests.
 *
 * @see \Tests\Unit\Article\Application\Command\ArticleUpdateCommandTest
 */
final class ArticleUpdateCommandDataProvider
{
    /**
     * Provides valid construction data for ArticleUpdateCommand.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, articleData: array}>
     */
    public static function provideValidConstructionData(): iterable
    {
        yield 'standard update command' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'articleData' => [
                'title' => 'Updated Article Title',
                'short_description' => 'This is an updated short description for testing.',
                'description' => 'This is an updated description that meets the minimum fifty character requirement for testing purposes.',
            ],
        ];

        yield 'update command with all fields' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'articleData' => [
                'title' => 'Complete Updated Article',
                'short_description' => 'Complete short description with all fields.',
                'description' => 'This is a complete updated description with all optional fields included for comprehensive testing.',
                'slug' => 'complete-updated-article',
                'status' => 'published',
                'event_id' => 99999,
            ],
        ];
    }

    /**
     * Provides scenarios for getArticle method.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, title: string, shortDescription: string, description: string}>
     */
    public static function provideGetArticleScenarios(): iterable
    {
        yield 'basic update' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'title' => 'Basic Updated Title',
            'shortDescription' => 'Basic updated short description.',
            'description' => 'This is a basic updated description that meets the minimum character requirement for testing.',
        ];

        yield 'comprehensive update' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'title' => 'Comprehensive Updated Title',
            'shortDescription' => 'Comprehensive updated short description.',
            'description' => 'This is a comprehensive updated description with all required fields properly populated for testing.',
        ];
    }

    /**
     * Provides scenarios for construction with payload.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, articleData: array, payload: array}>
     */
    public static function provideWithPayloadScenarios(): iterable
    {
        yield 'update with metadata payload' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'articleData' => [
                'title' => 'Update With Payload',
                'short_description' => 'Update with payload short description.',
                'description' => 'This is a description for an update command that includes additional payload metadata.',
            ],
            'payload' => [
                'editor_id' => 'user-123',
                'updated_by' => 'admin',
                'reason' => 'Content correction',
            ],
        ];

        yield 'update with tracking payload' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'articleData' => [
                'title' => 'Update With Tracking',
                'short_description' => 'Update with tracking payload short description.',
                'description' => 'This is a description for an update command that includes tracking and audit payload metadata.',
            ],
            'payload' => [
                'ip_address' => '192.168.1.1',
                'session_id' => 'sess_abc123',
                'user_agent' => 'Mozilla/5.0',
            ],
        ];
    }
}
