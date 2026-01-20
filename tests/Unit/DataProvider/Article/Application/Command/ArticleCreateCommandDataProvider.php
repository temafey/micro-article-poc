<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\Command;

/**
 * DataProvider for ArticleCreateCommand tests.
 *
 * @see \Tests\Unit\Article\Application\Command\ArticleCreateCommandTest
 */
final class ArticleCreateCommandDataProvider
{
    /**
     * Provides valid construction data for ArticleCreateCommand.
     *
     * @return iterable<string, array{processUuid: string, articleData: array}>
     */
    public static function provideValidConstructionData(): iterable
    {
        yield 'minimal command data' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'articleData' => [
                'title' => 'Test Article Title',
                'short_description' => 'This is a short description for testing purposes.',
                'description' => 'This is a complete article article description that provides comprehensive details about the topic at hand and meets minimum length requirements.',
            ],
        ];

        yield 'full command data with all fields' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'articleData' => [
                'title' => 'Full Article Article Title',
                'short_description' => 'A complete short description with all necessary details.',
                'description' => 'This is a fully detailed article article description that contains all the necessary information for a complete article article with proper formatting.',
                'event_id' => 12345,
            ],
        ];
    }

    /**
     * Provides scenarios for getArticle method testing.
     *
     * @return iterable<string, array{processUuid: string, title: string, shortDescription: string, description: string}>
     */
    public static function provideGetArticleScenarios(): iterable
    {
        yield 'standard article data' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'title' => 'Standard Title',
            'shortDescription' => 'Standard short description here.',
            'description' => 'This is a standard description that meets the minimum length requirement for proper validation testing.',
        ];

        yield 'unicode article data' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'title' => 'Zagolovok novosti',
            'shortDescription' => 'Kratkoe opisanie stati.',
            'description' => 'This is a full description of the article article in proper format with sufficient characters for validation testing.',
        ];
    }

    /**
     * Provides scenarios with payload.
     *
     * @return iterable<string, array{processUuid: string, articleData: array, payload: array}>
     */
    public static function provideWithPayloadScenarios(): iterable
    {
        yield 'command with api source payload' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'articleData' => [
                'title' => 'API Created Article',
                'short_description' => 'Article created via API endpoint.',
                'description' => 'This is an article created through the API endpoint with full details and proper formatting requirements met.',
            ],
            'payload' => [
                'source' => 'api',
                'user_id' => '123',
            ],
        ];

        yield 'command with cli source payload' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'articleData' => [
                'title' => 'CLI Created Article',
                'short_description' => 'Article created via command line.',
                'description' => 'This is an article created through the command line interface with full details and proper formatting.',
            ],
            'payload' => [
                'source' => 'cli',
                'admin_id' => 'admin-001',
            ],
        ];
    }
}
