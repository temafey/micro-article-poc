<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\Command;

/**
 * DataProvider for ArticleDeleteCommand tests.
 *
 * @see \Tests\Unit\Article\Application\Command\ArticleDeleteCommandTest
 */
final class ArticleDeleteCommandDataProvider
{
    /**
     * Provides valid construction data for ArticleDeleteCommand.
     *
     * @return iterable<string, array{processUuid: string, uuid: string}>
     */
    public static function provideValidConstructionData(): iterable
    {
        yield 'standard delete command' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
        ];

        yield 'delete command with different uuids' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
        ];

        yield 'delete command with zero-padded uuid' => [
            'processUuid' => '00000000-0000-0000-0000-000000000001',
            'uuid' => '00000000-0000-0000-0000-000000000002',
        ];
    }

    /**
     * Provides scenarios for construction with payload.
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
                'approved_by' => 'supervisor',
            ],
        ];

        yield 'delete with compliance payload' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'payload' => [
                'gdpr_request' => true,
                'request_id' => 'GDPR-2024-001',
                'requester_email' => 'user@example.com',
            ],
        ];
    }
}
