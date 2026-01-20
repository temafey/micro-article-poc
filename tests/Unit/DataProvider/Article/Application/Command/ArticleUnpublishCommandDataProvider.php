<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\Command;

/**
 * DataProvider for ArticleUnpublishCommand tests.
 *
 * @see \Tests\Unit\Article\Application\Command\ArticleUnpublishCommandTest
 */
final class ArticleUnpublishCommandDataProvider
{
    /**
     * Provides valid construction data for ArticleUnpublishCommand.
     *
     * @return iterable<string, array{processUuid: string, uuid: string}>
     */
    public static function provideValidConstructionData(): iterable
    {
        yield 'standard unpublish command' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
        ];

        yield 'unpublish command with different uuids' => [
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
        yield 'unpublish with reason payload' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'payload' => [
                'unpublished_by' => 'moderator',
                'reason' => 'Content under review',
                'temporary' => true,
            ],
        ];

        yield 'unpublish with event cancellation payload' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'payload' => [
                'event_cancelled' => true,
                'cancellation_notice' => 'Event has been cancelled',
                'republish_expected' => false,
            ],
        ];
    }
}
