<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\CommandHandler\Task;

/**
 * DataProvider for ArticleCreateTaskCommandHandler tests.
 *
 * Uses mockArgs/mockTimes pattern for mock configuration.
 *
 * @see Tests\Unit\Article\Application\CommandHandler\Task\ArticleCreateTaskCommandHandlerTest
 */
final class ArticleCreateTaskCommandHandlerDataProvider
{
    /**
     * @return iterable<string, array{commandData: array, mockArgs: array, mockTimes: array, expectedUuid: string}>
     */
    public static function provideSuccessScenarios(): iterable
    {
        yield 'standard article creation' => [
            'commandData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Breaking Article',
                'description' => 'This is a comprehensive breaking article description that contains sufficient content to meet the minimum validation requirements.',
                'shortDescription' => 'Breaking article summary',
                'status' => 'draft',
            ],
            'mockArgs' => [
                'factoryReturns' => true,
                'repositoryStoreSuccess' => true,
            ],
            'mockTimes' => [
                'factoryCreate' => 1,
                'repositoryStore' => 1,
                'eventBusPublish' => 1,
            ],
            'expectedUuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
        ];

        yield 'article creation with published status' => [
            'commandData' => [
                'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
                'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
                'title' => 'Published Article',
                'description' => 'This article is published immediately upon creation with all necessary content and validation requirements satisfied.',
                'shortDescription' => 'Published immediately',
                'status' => 'published',
            ],
            'mockArgs' => [
                'factoryReturns' => true,
                'repositoryStoreSuccess' => true,
            ],
            'mockTimes' => [
                'factoryCreate' => 1,
                'repositoryStore' => 1,
                'eventBusPublish' => 1,
            ],
            'expectedUuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
        ];

        yield 'article with unicode content' => [
            'commandData' => [
                'processUuid' => '3d6f2a88-5b1c-4e9d-8f3a-7c2b9e4a1f6d',
                'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d5e',
                'title' => 'Новости дня',
                'description' => 'Это действительное описание новости на русском языке с необходимым минимумом символов для валидации системы управления.',
                'shortDescription' => 'Краткое описание',
                'status' => 'draft',
            ],
            'mockArgs' => [
                'factoryReturns' => true,
                'repositoryStoreSuccess' => true,
            ],
            'mockTimes' => [
                'factoryCreate' => 1,
                'repositoryStore' => 1,
                'eventBusPublish' => 1,
            ],
            'expectedUuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d5e',
        ];

        yield 'article with maximum length fields' => [
            'commandData' => [
                'processUuid' => '9f8e7d6c-5b4a-3c2d-1e0f-9a8b7c6d5e4f',
                'uuid' => 'c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e8f',
                'title' => str_repeat('A', 255),
                'description' => str_repeat(
                    'This is a test description segment that will be repeated to ensure minimum length requirement. ',
                    50
                ),
                'shortDescription' => str_repeat('Short', 100),
                'status' => 'draft',
            ],
            'mockArgs' => [
                'factoryReturns' => true,
                'repositoryStoreSuccess' => true,
            ],
            'mockTimes' => [
                'factoryCreate' => 1,
                'repositoryStore' => 1,
                'eventBusPublish' => 1,
            ],
            'expectedUuid' => 'c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e8f',
        ];
    }

    /**
     * @return iterable<string, array{commandData: array, mockArgs: array, mockTimes: array, expectedException: class-string<\Throwable>, expectedMessage?: string}>
     */
    public static function provideFailureScenarios(): iterable
    {
        yield 'factory throws exception on invalid data' => [
            'commandData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => '',
                'description' => 'This is a valid test description that contains at least fifty characters for validation purposes.',
                'shortDescription' => 'Valid short desc',
                'status' => 'draft',
            ],
            'mockArgs' => [
                'factoryException' => \InvalidArgumentException::class,
                'factoryExceptionMessage' => 'Title cannot be empty',
            ],
            'mockTimes' => [
                'factoryCreate' => 1,
                'repositoryStore' => 0,
                'eventBusPublish' => 0,
            ],
            'expectedException' => \InvalidArgumentException::class,
            'expectedMessage' => 'Title cannot be empty',
        ];

        yield 'repository throws exception on storage failure' => [
            'commandData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Valid Title',
                'description' => 'This is a valid description that satisfies all minimum length requirements for article content validation.',
                'shortDescription' => 'Valid summary',
                'status' => 'draft',
            ],
            'mockArgs' => [
                'factoryReturns' => true,
                'repositoryException' => \RuntimeException::class,
                'repositoryExceptionMessage' => 'Database storage failed',
            ],
            'mockTimes' => [
                'factoryCreate' => 1,
                'repositoryStore' => 1,
                'eventBusPublish' => 0,
            ],
            'expectedException' => \RuntimeException::class,
            'expectedMessage' => 'Database storage failed',
        ];

        yield 'event bus throws exception on publish failure' => [
            'commandData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Valid Title',
                'description' => 'This is a valid description that meets all necessary validation requirements for article article creation.',
                'shortDescription' => 'Valid summary',
                'status' => 'draft',
            ],
            'mockArgs' => [
                'factoryReturns' => true,
                'repositoryStoreSuccess' => true,
                'eventBusException' => \RuntimeException::class,
                'eventBusExceptionMessage' => 'Event publishing failed',
            ],
            'mockTimes' => [
                'factoryCreate' => 1,
                'repositoryStore' => 1,
                'eventBusPublish' => 1,
            ],
            'expectedException' => \RuntimeException::class,
            'expectedMessage' => 'Event publishing failed',
        ];
    }
}
