<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\Event;

/**
 * DataProvider for ArticleArchivedEvent tests.
 *
 * @see \Tests\Unit\Article\Domain\Event\ArticleArchivedEventTest
 */
final class ArticleArchivedEventDataProvider
{
    /**
     * Provides valid construction scenarios.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, status: string, archivedAt: string, updatedAt: string}>
     */
    public static function provideValidConstructionData(): iterable
    {
        yield 'standard archive event' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'status' => 'archived',
            'archivedAt' => '2024-01-15T10:30:00+00:00',
            'updatedAt' => '2024-01-15T10:30:00+00:00',
        ];

        yield 'archive event with different timestamps' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'status' => 'archived',
            'archivedAt' => '2024-06-20T14:45:30+00:00',
            'updatedAt' => '2024-06-20T14:45:30+00:00',
        ];
    }

    /**
     * Provides serialize/deserialize scenarios.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, status: string, archivedAt: string, updatedAt: string}>
     */
    public static function provideSerializationScenarios(): iterable
    {
        yield 'event concluded archival' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'status' => 'archived',
            'archivedAt' => '2024-01-15T10:30:00+00:00',
            'updatedAt' => '2024-01-15T10:30:00+00:00',
        ];

        yield 'scheduled archival' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'status' => 'archived',
            'archivedAt' => '2024-12-31T23:59:59+00:00',
            'updatedAt' => '2024-12-31T23:59:59+00:00',
        ];
    }

    /**
     * Provides scenarios with payload.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, status: string, archivedAt: string, updatedAt: string, payload: array}>
     */
    public static function provideWithPayloadScenarios(): iterable
    {
        yield 'archive with retention payload' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'status' => 'archived',
            'archivedAt' => '2024-01-15T10:30:00+00:00',
            'updatedAt' => '2024-01-15T10:30:00+00:00',
            'payload' => [
                'archived_by' => 'system',
                'retention_period' => '5 years',
            ],
        ];
    }
}
