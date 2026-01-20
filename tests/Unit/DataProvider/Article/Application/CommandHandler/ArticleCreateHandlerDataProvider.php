<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\CommandHandler;

/**
 * DataProvider for ArticleCreateHandler tests.
 *
 * Uses mockArgs/mockTimes pattern for mock configuration.
 *
 * @see \Tests\Unit\Article\Application\CommandHandler\ArticleCreateHandlerTest
 */
final class ArticleCreateHandlerDataProvider
{
    /**
     * Provides success scenarios for handler.
     *
     * @return iterable<string, array{articleData: array, mockArgs: array, mockTimes: array, expectedUuid: string}>
     */
    public static function provideSuccessScenarios(): iterable
    {
        yield 'standard article creation' => [
            'articleData' => [
                'title' => 'Standard Article Article',
                'short_description' => 'This is a standard short description for testing.',
                'description' => 'This is a standard description that meets the fifty character minimum requirement for proper testing of the handler.',
            ],
            'mockArgs' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'entityUuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            ],
            'mockTimes' => [
                'createEntity' => 1,
                'store' => 1,
            ],
            'expectedUuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
        ];

        yield 'article creation with all optional fields' => [
            'articleData' => [
                'title' => 'Full Article Article',
                'short_description' => 'Complete short description with all fields.',
                'description' => 'This is a complete description with all optional fields included to test full functionality of the handler.',
                'event_id' => 12345,
            ],
            'mockArgs' => [
                'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
                'entityUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            ],
            'mockTimes' => [
                'createEntity' => 1,
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
        yield 'factory throws exception on invalid data' => [
            'articleData' => [
                'title' => 'Test Title',
                'short_description' => 'Short description.',
                'description' => 'This is a test description that meets the minimum fifty character length requirement.',
            ],
            'mockArgs' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'factoryException' => \InvalidArgumentException::class,
                'factoryExceptionMessage' => 'Invalid article data provided',
            ],
            'mockTimes' => [
                'createEntity' => 1,
                'store' => 0,
            ],
            'expectedException' => \InvalidArgumentException::class,
            'expectedMessage' => 'Invalid article data provided',
        ];

        yield 'repository throws exception on store failure' => [
            'articleData' => [
                'title' => 'Valid Title',
                'short_description' => 'Valid short description.',
                'description' => 'This is a valid description that meets the minimum fifty character length requirement for testing.',
            ],
            'mockArgs' => [
                'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
                'entityUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
                'repositoryException' => \RuntimeException::class,
                'repositoryExceptionMessage' => 'Failed to store entity',
            ],
            'mockTimes' => [
                'createEntity' => 1,
                'store' => 1,
            ],
            'expectedException' => \RuntimeException::class,
            'expectedMessage' => 'Failed to store entity',
        ];
    }
}
