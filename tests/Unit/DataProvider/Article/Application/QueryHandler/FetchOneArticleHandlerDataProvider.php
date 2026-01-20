<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\QueryHandler;

/**
 * DataProvider for FetchOneArticleHandler tests.
 *
 * Format: [0 => mockArgs, 1 => mockTimes]
 *
 * @see \Tests\Unit\Article\Application\QueryHandler\FetchOneArticleHandlerTest
 */
final class FetchOneArticleHandlerDataProvider
{
    /**
     * Provides scenarios for successful fetch operations.
     *
     * @return iterable<string, array{mockArgs: array, mockTimes: array}>
     */
    public static function provideSuccessScenarios(): iterable
    {
        yield 'fetch existing article' => [
            'mockArgs' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'readModel' => [
                    'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                    'title' => 'Fetched Article',
                    'status' => 'published',
                ],
            ],
            'mockTimes' => [
                'fetchOne' => 1,
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
        yield 'fetch non-existing article' => [
            'mockArgs' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
                'readModel' => null,
            ],
            'mockTimes' => [
                'fetchOne' => 1,
            ],
        ];
    }
}
