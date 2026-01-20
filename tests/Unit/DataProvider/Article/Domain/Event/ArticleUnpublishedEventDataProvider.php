<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\Event;

/**
 * DataProvider for ArticleUnpublishedEvent tests.
 *
 * @see \Tests\Unit\Article\Domain\Event\ArticleUnpublishedEventTest
 */
final class ArticleUnpublishedEventDataProvider
{
    /**
     * Provides valid construction scenarios.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, status: string, updatedAt: string}>
     */
    public static function provideValidConstructionData(): iterable
    {
        yield 'standard unpublish event' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'status' => 'draft',
            'updatedAt' => '2024-01-15T10:30:00+00:00',
        ];

        yield 'unpublish event with different timestamp' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'status' => 'draft',
            'updatedAt' => '2024-06-20T14:45:30+00:00',
        ];
    }

    /**
     * Provides serialize/deserialize scenarios.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, status: string, updatedAt: string}>
     */
    public static function provideSerializationScenarios(): iterable
    {
        yield 'immediate unpublication' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'status' => 'draft',
            'updatedAt' => '2024-01-15T10:30:00+00:00',
        ];

        yield 'unpublication for review' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'status' => 'draft',
            'updatedAt' => '2024-12-25T00:00:00+00:00',
        ];
    }

    /**
     * Provides scenarios with payload.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, status: string, updatedAt: string, payload: array}>
     */
    public static function provideWithPayloadScenarios(): iterable
    {
        yield 'unpublish with reason payload' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'status' => 'draft',
            'updatedAt' => '2024-01-15T10:30:00+00:00',
            'payload' => [
                'unpublished_by' => 'moderator',
                'reason' => 'Content under review',
            ],
        ];
    }
}
