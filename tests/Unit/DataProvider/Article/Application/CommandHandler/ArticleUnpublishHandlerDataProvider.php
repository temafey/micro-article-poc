<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\CommandHandler;

/**
 * DataProvider for ArticleUnpublishHandler tests.
 *
 * Uses mockArgs/mockTimes pattern for mock configuration.
 *
 * @see \Tests\Unit\Article\Application\CommandHandler\ArticleUnpublishHandlerTest
 */
final class ArticleUnpublishHandlerDataProvider
{
    /**
     * Provides success scenarios for handler.
     *
     * @return iterable<string, array{mockArgs: array, mockTimes: array, expectedUuid: string}>
     */
    public static function provideSuccessScenarios(): iterable
    {
        yield 'standard article unpublication' => [
            'mockArgs' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'entityUuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            ],
            'mockTimes' => [
                'get' => 1,
                'articleUnpublish' => 1,
                'store' => 1,
            ],
            'expectedUuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
        ];

        yield 'unpublication with different uuid' => [
            'mockArgs' => [
                'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
                'entityUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            ],
            'mockTimes' => [
                'get' => 1,
                'articleUnpublish' => 1,
                'store' => 1,
            ],
            'expectedUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
        ];
    }

    /**
     * Provides failure scenarios for handler.
     *
     * @return iterable<string, array{mockArgs: array, mockTimes: array, expectedException: class-string<\Throwable>, expectedMessage: string}>
     */
    public static function provideFailureScenarios(): iterable
    {
        yield 'entity not found' => [
            'mockArgs' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'entityUuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'getException' => \RuntimeException::class,
                'getExceptionMessage' => 'Entity not found',
            ],
            'mockTimes' => [
                'get' => 1,
                'articleUnpublish' => 0,
                'store' => 0,
            ],
            'expectedException' => \RuntimeException::class,
            'expectedMessage' => 'Entity not found',
        ];

        yield 'repository store failure' => [
            'mockArgs' => [
                'processUuid' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
                'entityUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
                'storeException' => \RuntimeException::class,
                'storeExceptionMessage' => 'Failed to unpublish entity',
            ],
            'mockTimes' => [
                'get' => 1,
                'articleUnpublish' => 1,
                'store' => 1,
            ],
            'expectedException' => \RuntimeException::class,
            'expectedMessage' => 'Failed to unpublish entity',
        ];
    }
}
