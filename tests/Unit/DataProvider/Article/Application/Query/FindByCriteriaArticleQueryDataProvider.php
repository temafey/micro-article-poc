<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\Query;

/**
 * DataProvider for FindByCriteriaArticleQuery tests.
 *
 * @see \Tests\Unit\Article\Application\Query\FindByCriteriaArticleQueryTest
 */
final class FindByCriteriaArticleQueryDataProvider
{
    /**
     * Provides valid construction data for FindByCriteriaArticleQuery.
     *
     * @return iterable<string, array{processUuid: string, criteria: array}>
     */
    public static function provideValidConstructionData(): iterable
    {
        yield 'status criteria' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'criteria' => [
                'status' => 'published',
            ],
        ];

        yield 'multiple criteria' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'criteria' => [
                'status' => 'draft',
                'title' => 'Test Article',
            ],
        ];

        yield 'empty criteria' => [
            'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'criteria' => [],
        ];
    }
}
