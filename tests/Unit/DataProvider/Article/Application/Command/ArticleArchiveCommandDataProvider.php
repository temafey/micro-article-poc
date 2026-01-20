<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\Command;

/**
 * DataProvider for ArticleArchiveCommand tests.
 *
 * @see \Tests\Unit\Article\Application\Command\ArticleArchiveCommandTest
 */
final class ArticleArchiveCommandDataProvider
{
    /**
     * Provides valid construction data for ArticleArchiveCommand.
     *
     * @return iterable<string, array{processUuid: string, uuid: string}>
     */
    public static function provideValidConstructionData(): iterable
    {
        yield 'standard archive command' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
        ];

        yield 'archive command with different uuids' => [
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
        yield 'archive with expiration payload' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'payload' => [
                'archived_by' => 'system',
                'reason' => 'Event concluded',
                'preserve_url' => true,
            ],
        ];

        yield 'archive with retention payload' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'payload' => [
                'retention_period' => '5 years',
                'compliance_required' => true,
                'searchable' => true,
            ],
        ];
    }
}
