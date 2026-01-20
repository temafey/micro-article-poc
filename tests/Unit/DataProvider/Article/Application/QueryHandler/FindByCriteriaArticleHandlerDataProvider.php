<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\QueryHandler;

/**
 * DataProvider for FindByCriteriaArticleHandler tests.
 *
 * Format: [0 => mockArgs, 1 => mockTimes]
 *
 * @see \Tests\Unit\Article\Application\QueryHandler\FindByCriteriaArticleHandlerTest
 */
final class FindByCriteriaArticleHandlerDataProvider
{
    /**
     * Provides scenarios for successful find operations.
     *
     * @return iterable<string, array{mockArgs: array, mockTimes: array}>
     */
    public static function provideSuccessScenarios(): iterable
    {
        yield 'find published article' => [
            'mockArgs' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'criteria' => [
                    'status' => 'published',
                ],
                'resultCount' => 2,
            ],
            'mockTimes' => [
                'findByCriteria' => 1,
            ],
        ];

        yield 'find draft article' => [
            'mockArgs' => [
                'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
                'criteria' => [
                    'status' => 'draft',
                ],
                'resultCount' => 1,
            ],
            'mockTimes' => [
                'findByCriteria' => 1,
            ],
        ];
    }

    /**
     * Provides scenarios for empty result.
     *
     * @return iterable<string, array{mockArgs: array, mockTimes: array}>
     */
    public static function provideEmptyResultScenarios(): iterable
    {
        yield 'find with no matches' => [
            'mockArgs' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'criteria' => [
                    'status' => 'nonexistent',
                ],
                'resultCount' => 0,
            ],
            'mockTimes' => [
                'findByCriteria' => 1,
            ],
        ];
    }
}
