<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\Event;

/**
 * DataProvider for ArticleDeletedEvent tests.
 *
 * @see \Tests\Unit\Article\Domain\Event\ArticleDeletedEventTest
 */
final class ArticleDeletedEventDataProvider
{
    /**
     * Provides valid construction scenarios.
     *
     * @return iterable<string, array{processUuid: string, uuid: string}>
     */
    public static function provideValidConstructionData(): iterable
    {
        yield 'standard delete event' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
        ];

        yield 'delete event with different uuids' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
        ];

        yield 'delete event with zero-padded uuid' => [
            'processUuid' => '00000000-0000-0000-0000-000000000001',
            'uuid' => '00000000-0000-0000-0000-000000000002',
        ];
    }

    /**
     * Provides serialize/deserialize scenarios.
     *
     * @return iterable<string, array{processUuid: string, uuid: string}>
     */
    public static function provideSerializationScenarios(): iterable
    {
        yield 'simple deletion' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
        ];

        yield 'deletion with payload' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
        ];
    }

    /**
     * Provides scenarios with payload.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, payload: array}>
     */
    public static function provideWithPayloadScenarios(): iterable
    {
        yield 'delete with audit payload' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'payload' => [
                'deleted_by' => 'admin',
                'reason' => 'Content policy violation',
            ],
        ];

        yield 'delete with gdpr payload' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'payload' => [
                'gdpr_request' => true,
                'request_id' => 'GDPR-2024-001',
            ],
        ];
    }
}
