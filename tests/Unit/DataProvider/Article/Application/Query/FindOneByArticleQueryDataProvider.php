<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\Query;

/**
 * DataProvider for FindOneByArticleQuery tests.
 *
 * @see \Tests\Unit\Article\Application\Query\FindOneByArticleQueryTest
 */
final class FindOneByArticleQueryDataProvider
{
    /**
     * Provides valid construction data for FindOneByArticleQuery.
     *
     * @return iterable<string, array{processUuid: string, criteria: array}>
     */
    public static function provideValidConstructionData(): iterable
    {
        yield 'slug criteria' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'criteria' => [
                'slug' => 'test-article-article',
            ],
        ];

        yield 'event_id criteria' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'criteria' => [
                'event_id' => 12345,
            ],
        ];
    }
}
