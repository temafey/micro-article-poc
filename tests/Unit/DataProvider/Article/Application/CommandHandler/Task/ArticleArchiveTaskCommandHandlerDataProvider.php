<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\CommandHandler\Task;

/**
 * DataProvider for ArticleArchiveTaskCommandHandler tests.
 *
 * Uses mockArgs/mockTimes pattern for mock configuration.
 *
 * @see Tests\Unit\Article\Application\CommandHandler\Task\ArticleArchiveTaskCommandHandlerTest
 */
final class ArticleArchiveTaskCommandHandlerDataProvider
{
    /**
     * @return iterable<string, array{commandData: array, mockArgs: array, mockTimes: array, expectedUuid: string}>
     */
    public static function provideSuccessScenarios(): iterable
    {
        yield 'archive published article' => [
            'commandData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440005',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430cd',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'entityArchiveSuccess' => true,
                'repositoryStoreSuccess' => true,
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'entityArchive' => 1,
                'repositoryStore' => 1,
                'eventBusPublish' => 1,
            ],
            'expectedUuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430cd',
        ];

        yield 'archive after expiration' => [
            'commandData' => [
                'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90aec',
                'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a16',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'entityArchiveSuccess' => true,
                'repositoryStoreSuccess' => true,
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'entityArchive' => 1,
                'repositoryStore' => 1,
                'eventBusPublish' => 1,
            ],
            'expectedUuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a16',
        ];

        yield 'archive old content' => [
            'commandData' => [
                'processUuid' => '3d6f2a88-5b1c-4e9d-8f3a-7c2b9e4a1f72',
                'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d63',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'entityArchiveSuccess' => true,
                'repositoryStoreSuccess' => true,
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'entityArchive' => 1,
                'repositoryStore' => 1,
                'eventBusPublish' => 1,
            ],
            'expectedUuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d63',
        ];

        yield 'archive seasonal article' => [
            'commandData' => [
                'processUuid' => '9f8e7d6c-5b4a-3c2d-1e0f-9a8b7c6d5e44',
                'uuid' => 'c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e84',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'entityArchiveSuccess' => true,
                'repositoryStoreSuccess' => true,
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'entityArchive' => 1,
                'repositoryStore' => 1,
                'eventBusPublish' => 1,
            ],
            'expectedUuid' => 'c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e84',
        ];
    }

    /**
     * @return iterable<string, array{commandData: array, mockArgs: array, mockTimes: array, expectedException: class-string<\Throwable>, expectedMessage?: string}>
     */
    public static function provideFailureScenarios(): iterable
    {
        yield 'article not found in repository' => [
            'commandData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440005',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430cd',
            ],
            'mockArgs' => [
                'repositoryFindException' => \RuntimeException::class,
                'repositoryFindExceptionMessage' => 'Article not found',
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'entityArchive' => 0,
                'repositoryStore' => 0,
                'eventBusPublish' => 0,
            ],
            'expectedException' => \RuntimeException::class,
            'expectedMessage' => 'Article not found',
        ];

        yield 'entity archive throws invalid state transition' => [
            'commandData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440005',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430cd',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'entityArchiveException' => \RuntimeException::class,
                'entityArchiveExceptionMessage' => 'Cannot archive draft article',
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'entityArchive' => 1,
                'repositoryStore' => 0,
                'eventBusPublish' => 0,
            ],
            'expectedException' => \RuntimeException::class,
            'expectedMessage' => 'Cannot archive draft article',
        ];

        yield 'repository storage fails' => [
            'commandData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440005',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430cd',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'entityArchiveSuccess' => true,
                'repositoryException' => \RuntimeException::class,
                'repositoryExceptionMessage' => 'Database archive failed',
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'entityArchive' => 1,
                'repositoryStore' => 1,
                'eventBusPublish' => 0,
            ],
            'expectedException' => \RuntimeException::class,
            'expectedMessage' => 'Database archive failed',
        ];
    }
}
