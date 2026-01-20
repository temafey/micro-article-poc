<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\CommandHandler\Task;

/**
 * DataProvider for ArticleUnpublishTaskCommandHandler tests.
 *
 * Uses mockArgs/mockTimes pattern for mock configuration.
 *
 * @see Tests\Unit\Article\Application\CommandHandler\Task\ArticleUnpublishTaskCommandHandlerTest
 */
final class ArticleUnpublishTaskCommandHandlerDataProvider
{
    /**
     * @return iterable<string, array{commandData: array, mockArgs: array, mockTimes: array, expectedUuid: string}>
     */
    public static function provideSuccessScenarios(): iterable
    {
        yield 'unpublish published article' => [
            'commandData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440004',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430cc',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'entityUnpublishSuccess' => true,
                'repositoryStoreSuccess' => true,
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'entityUnpublish' => 1,
                'repositoryStore' => 1,
                'eventBusPublish' => 1,
            ],
            'expectedUuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430cc',
        ];

        yield 'unpublish for corrections' => [
            'commandData' => [
                'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90aeb',
                'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a15',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'entityUnpublishSuccess' => true,
                'repositoryStoreSuccess' => true,
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'entityUnpublish' => 1,
                'repositoryStore' => 1,
                'eventBusPublish' => 1,
            ],
            'expectedUuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a15',
        ];

        yield 'unpublish temporarily' => [
            'commandData' => [
                'processUuid' => '3d6f2a88-5b1c-4e9d-8f3a-7c2b9e4a1f71',
                'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d62',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'entityUnpublishSuccess' => true,
                'repositoryStoreSuccess' => true,
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'entityUnpublish' => 1,
                'repositoryStore' => 1,
                'eventBusPublish' => 1,
            ],
            'expectedUuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d62',
        ];

        yield 'unpublish due to policy violation' => [
            'commandData' => [
                'processUuid' => '9f8e7d6c-5b4a-3c2d-1e0f-9a8b7c6d5e43',
                'uuid' => 'c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e83',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'entityUnpublishSuccess' => true,
                'repositoryStoreSuccess' => true,
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'entityUnpublish' => 1,
                'repositoryStore' => 1,
                'eventBusPublish' => 1,
            ],
            'expectedUuid' => 'c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e83',
        ];
    }

    /**
     * @return iterable<string, array{commandData: array, mockArgs: array, mockTimes: array, expectedException: class-string<\Throwable>, expectedMessage?: string}>
     */
    public static function provideFailureScenarios(): iterable
    {
        yield 'article not found in repository' => [
            'commandData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440004',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430cc',
            ],
            'mockArgs' => [
                'repositoryFindException' => \RuntimeException::class,
                'repositoryFindExceptionMessage' => 'Article not found',
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'entityUnpublish' => 0,
                'repositoryStore' => 0,
                'eventBusPublish' => 0,
            ],
            'expectedException' => \RuntimeException::class,
            'expectedMessage' => 'Article not found',
        ];

        yield 'entity unpublish throws invalid state transition' => [
            'commandData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440004',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430cc',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'entityUnpublishException' => \RuntimeException::class,
                'entityUnpublishExceptionMessage' => 'Cannot unpublish draft article',
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'entityUnpublish' => 1,
                'repositoryStore' => 0,
                'eventBusPublish' => 0,
            ],
            'expectedException' => \RuntimeException::class,
            'expectedMessage' => 'Cannot unpublish draft article',
        ];

        yield 'repository storage fails' => [
            'commandData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440004',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430cc',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'entityUnpublishSuccess' => true,
                'repositoryException' => \RuntimeException::class,
                'repositoryExceptionMessage' => 'Database unpublish failed',
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'entityUnpublish' => 1,
                'repositoryStore' => 1,
                'eventBusPublish' => 0,
            ],
            'expectedException' => \RuntimeException::class,
            'expectedMessage' => 'Database unpublish failed',
        ];
    }
}
