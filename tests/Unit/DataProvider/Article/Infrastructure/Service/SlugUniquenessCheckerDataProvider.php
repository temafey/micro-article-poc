<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Infrastructure\Service;

/**
 * DataProvider for SlugUniquenessChecker tests.
 */
final class SlugUniquenessCheckerDataProvider
{
    /**
     * Data for slug exists scenarios when read model is found.
     */
    public static function slugExistsFoundScenarios(): \Generator
    {
        yield 'slug exists without exclude uuid' => [
            'slug' => 'test-article-slug',
            'excludeUuid' => null,
            'foundUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'expectedResult' => true,
        ];

        yield 'slug exists with different exclude uuid' => [
            'slug' => 'another-slug',
            'excludeUuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'foundUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'expectedResult' => true,
        ];
    }

    /**
     * Data for slug does not exist scenarios.
     */
    public static function slugNotFoundScenarios(): \Generator
    {
        yield 'slug not found' => [
            'slug' => 'unique-slug-that-does-not-exist',
            'excludeUuid' => null,
            'expectedResult' => false,
        ];

        yield 'slug not found with exclude uuid' => [
            'slug' => 'another-unique-slug',
            'excludeUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'expectedResult' => false,
        ];
    }

    /**
     * Data for slug exists but same uuid excluded scenarios.
     */
    public static function slugExistsSameUuidExcludedScenarios(): \Generator
    {
        yield 'slug exists but same uuid excluded' => [
            'slug' => 'existing-slug',
            'excludeUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'foundUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'expectedResult' => false,
        ];
    }
}
