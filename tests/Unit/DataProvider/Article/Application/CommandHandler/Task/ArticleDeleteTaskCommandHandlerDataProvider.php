<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\CommandHandler\Task;

/**
 * DataProvider for ArticleDeleteTaskCommandHandler tests.
 *
 * Uses mockArgs/mockTimes pattern for mock configuration.
 *
 * @see Tests\Unit\Article\Application\CommandHandler\Task\ArticleDeleteTaskCommandHandlerTest
 */
final class ArticleDeleteTaskCommandHandlerDataProvider
{
    /**
     * @return iterable<string, array{commandData: array, mockArgs: array, mockTimes: array, expectedUuid: string}>
     */
    public static function provideSuccessScenarios(): iterable
    {
        yield 'delete draft article' => [
            'commandData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440002',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430ca',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'repositoryDeleteSuccess' => true,
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'repositoryDelete' => 1,
                'eventBusPublish' => 1,
            ],
            'expectedUuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430ca',
        ];

        yield 'delete published article' => [
            'commandData' => [
                'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae9',
                'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a13',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'repositoryDeleteSuccess' => true,
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'repositoryDelete' => 1,
                'eventBusPublish' => 1,
            ],
            'expectedUuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a13',
        ];

        yield 'delete archived article' => [
            'commandData' => [
                'processUuid' => '3d6f2a88-5b1c-4e9d-8f3a-7c2b9e4a1f6f',
                'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d60',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'repositoryDeleteSuccess' => true,
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'repositoryDelete' => 1,
                'eventBusPublish' => 1,
            ],
            'expectedUuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d60',
        ];

        yield 'delete with specific process' => [
            'commandData' => [
                'processUuid' => '9f8e7d6c-5b4a-3c2d-1e0f-9a8b7c6d5e41',
                'uuid' => 'c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e81',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'repositoryDeleteSuccess' => true,
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'repositoryDelete' => 1,
                'eventBusPublish' => 1,
            ],
            'expectedUuid' => 'c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e81',
        ];
    }

    /**
     * @return iterable<string, array{commandData: array, mockArgs: array, mockTimes: array, expectedException: class-string<\Throwable>, expectedMessage?: string}>
     */
    public static function provideFailureScenarios(): iterable
    {
        yield 'article not found in repository' => [
            'commandData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440002',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430ca',
            ],
            'mockArgs' => [
                'repositoryFindException' => \RuntimeException::class,
                'repositoryFindExceptionMessage' => 'Article not found',
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'repositoryDelete' => 0,
                'eventBusPublish' => 0,
            ],
            'expectedException' => \RuntimeException::class,
            'expectedMessage' => 'Article not found',
        ];

        yield 'repository delete operation fails' => [
            'commandData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440002',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430ca',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'repositoryDeleteException' => \RuntimeException::class,
                'repositoryDeleteExceptionMessage' => 'Database delete failed',
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'repositoryDelete' => 1,
                'eventBusPublish' => 0,
            ],
            'expectedException' => \RuntimeException::class,
            'expectedMessage' => 'Database delete failed',
        ];

        yield 'event bus fails to publish delete event' => [
            'commandData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440002',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430ca',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'repositoryDeleteSuccess' => true,
                'eventBusException' => \RuntimeException::class,
                'eventBusExceptionMessage' => 'Event publishing failed',
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'repositoryDelete' => 1,
                'eventBusPublish' => 1,
            ],
            'expectedException' => \RuntimeException::class,
            'expectedMessage' => 'Event publishing failed',
        ];
    }
}
