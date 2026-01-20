<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\ValueObject;

/**
 * DataProvider for FindCriteria ValueObject tests.
 *
 * @see \Tests\Unit\Article\Domain\ValueObject\FindCriteriaTest
 */
final class FindCriteriaDataProvider
{
    /**
     * Provides valid criteria data for construction.
     *
     * @return iterable<string, array{criteria: array}>
     */
    public static function provideValidCriteria(): iterable
    {
        yield 'empty criteria' => [
            'criteria' => [],
        ];

        yield 'single field criteria' => [
            'criteria' => [
                'status' => 'published',
            ],
        ];

        yield 'multiple fields criteria' => [
            'criteria' => [
                'status' => 'published',
                'event_id' => 12345,
            ],
        ];

        yield 'criteria with pagination' => [
            'criteria' => [
                'status' => 'draft',
                'limit' => 10,
                'offset' => 0,
            ],
        ];

        yield 'criteria with sorting' => [
            'criteria' => [
                'status' => 'published',
                'order_by' => 'created_at',
                'order_direction' => 'DESC',
            ],
        ];
    }

    /**
     * Provides scenarios for fromNative method.
     *
     * @return iterable<string, array{criteria: array}>
     */
    public static function provideFromNativeScenarios(): iterable
    {
        yield 'create from empty array' => [
            'criteria' => [],
        ];

        yield 'create from status filter' => [
            'criteria' => [
                'status' => 'archived',
            ],
        ];

        yield 'create from complex filter' => [
            'criteria' => [
                'status' => 'published',
                'event_id' => 99999,
                'limit' => 50,
            ],
        ];
    }
}
