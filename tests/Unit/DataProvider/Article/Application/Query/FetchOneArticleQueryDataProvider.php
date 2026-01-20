<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\Query;

/**
 * DataProvider for FetchOneArticleQuery tests.
 *
 * @see \Tests\Unit\Article\Application\Query\FetchOneArticleQueryTest
 */
final class FetchOneArticleQueryDataProvider
{
    /**
     * Provides valid construction data for FetchOneArticleQuery.
     *
     * @return iterable<string, array{processUuid: string, uuid: string}>
     */
    public static function provideValidConstructionData(): iterable
    {
        yield 'standard uuid fetch' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
        ];

        yield 'different uuid fetch' => [
            'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
        ];
    }
}
