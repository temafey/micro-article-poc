<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\CommandHandler;

/**
 * DataProvider for ArticleUpdateHandler tests.
 *
 * Uses mockArgs/mockTimes pattern for mock configuration.
 *
 * @see \Tests\Unit\Article\Application\CommandHandler\ArticleUpdateHandlerTest
 */
final class ArticleUpdateHandlerDataProvider
{
    /**
     * Provides success scenarios for handler.
     *
     * @return iterable<string, array{articleData: array, mockArgs: array, mockTimes: array, expectedUuid: string}>
     */
    public static function provideSuccessScenarios(): iterable
    {
        yield 'standard article update' => [
            'articleData' => [
                'title' => 'Updated Article Article',
                'short_description' => 'This is an updated short description for testing.',
                'description' => 'This is an updated description that meets the minimum fifty character requirement for testing purposes.',
            ],
            'mockArgs' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'entityUuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            ],
            'mockTimes' => [
                'get' => 1,
                'articleUpdate' => 1,
                'store' => 1,
            ],
            'expectedUuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
        ];

        yield 'article update with all fields' => [
            'articleData' => [
                'title' => 'Comprehensive Updated Article',
                'short_description' => 'Complete updated short description with all fields.',
                'description' => 'This is a comprehensive updated description with all optional fields included for testing handler behavior.',
                'slug' => 'comprehensive-updated-article',
                'status' => 'draft',
            ],
            'mockArgs' => [
                'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
                'entityUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            ],
            'mockTimes' => [
                'get' => 1,
                'articleUpdate' => 1,
                'store' => 1,
            ],
            'expectedUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
        ];
    }

    /**
     * Provides failure scenarios for handler.
     *
     * @return iterable<string, array{articleData: array, mockArgs: array, mockTimes: array, expectedException: class-string<\Throwable>, expectedMessage: string}>
     */
    public static function provideFailureScenarios(): iterable
    {
        yield 'entity not found' => [
            'articleData' => [
                'title' => 'Update Title',
                'short_description' => 'Update short description.',
                'description' => 'This is an update description that meets the minimum fifty character length requirement.',
            ],
            'mockArgs' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'entityUuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'getException' => \RuntimeException::class,
                'getExceptionMessage' => 'Entity not found',
            ],
            'mockTimes' => [
                'get' => 1,
                'articleUpdate' => 0,
                'store' => 0,
            ],
            'expectedException' => \RuntimeException::class,
            'expectedMessage' => 'Entity not found',
        ];

        yield 'repository store failure' => [
            'articleData' => [
                'title' => 'Valid Update Title',
                'short_description' => 'Valid update short description.',
                'description' => 'This is a valid update description that meets the minimum fifty character length requirement.',
            ],
            'mockArgs' => [
                'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
                'entityUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
                'storeException' => \RuntimeException::class,
                'storeExceptionMessage' => 'Failed to store updated entity',
            ],
            'mockTimes' => [
                'get' => 1,
                'articleUpdate' => 1,
                'store' => 1,
            ],
            'expectedException' => \RuntimeException::class,
            'expectedMessage' => 'Failed to store updated entity',
        ];
    }
}
