<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\Command;

/**
 * DataProvider for ArticlePublishCommand tests.
 *
 * @see \Tests\Unit\Article\Application\Command\ArticlePublishCommandTest
 */
final class ArticlePublishCommandDataProvider
{
    /**
     * Provides valid construction data for ArticlePublishCommand.
     *
     * @return iterable<string, array{processUuid: string, uuid: string}>
     */
    public static function provideValidConstructionData(): iterable
    {
        yield 'standard publish command' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
        ];

        yield 'publish command with different uuids' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
        ];
    }

    /**
     * Provides scenarios for construction with payload.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, payload: array}>
     */
    public static function provideWithPayloadScenarios(): iterable
    {
        yield 'publish with approval payload' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'payload' => [
                'approved_by' => 'editor',
                'approval_date' => '2024-01-15T10:30:00+00:00',
                'quality_score' => 95,
            ],
        ];

        yield 'publish with scheduling payload' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'payload' => [
                'scheduled' => false,
                'immediate' => true,
                'notify_subscribers' => true,
            ],
        ];
    }
}
