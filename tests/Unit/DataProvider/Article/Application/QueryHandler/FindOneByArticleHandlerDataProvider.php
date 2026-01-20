<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\QueryHandler;

/**
 * DataProvider for FindOneByArticleHandler tests.
 *
 * Format: [0 => mockArgs, 1 => mockTimes]
 *
 * @see \Tests\Unit\Article\Application\QueryHandler\FindOneByArticleHandlerTest
 */
final class FindOneByArticleHandlerDataProvider
{
    /**
     * Provides scenarios for successful find one operations.
     *
     * @return iterable<string, array{mockArgs: array, mockTimes: array}>
     */
    public static function provideSuccessScenarios(): iterable
    {
        yield 'find by slug' => [
            'mockArgs' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'criteria' => [
                    'slug' => 'test-article',
                ],
                'readModel' => [
                    'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                    'title' => 'Test Article',
                    'slug' => 'test-article',
                    'status' => 'published',
                ],
            ],
            'mockTimes' => [
                'findOneBy' => 1,
            ],
        ];

        yield 'find by event_id' => [
            'mockArgs' => [
                'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
                'criteria' => [
                    'event_id' => 12345,
                ],
                'readModel' => [
                    'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
                    'title' => 'Event Article',
                    'event_id' => 12345,
                    'status' => 'draft',
                ],
            ],
            'mockTimes' => [
                'findOneBy' => 1,
            ],
        ];
    }

    /**
     * Provides scenarios for null result (not found).
     *
     * @return iterable<string, array{mockArgs: array, mockTimes: array}>
     */
    public static function provideNotFoundScenarios(): iterable
    {
        yield 'find non-existing by slug' => [
            'mockArgs' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'criteria' => [
                    'slug' => 'non-existing-slug',
                ],
                'readModel' => null,
            ],
            'mockTimes' => [
                'findOneBy' => 1,
            ],
        ];
    }
}
