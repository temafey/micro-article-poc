<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\CommandHandler\Task;

/**
 * DataProvider for ArticleUpdateTaskCommandHandler tests.
 *
 * Uses mockArgs/mockTimes pattern for mock configuration.
 *
 * @see Tests\Unit\Article\Application\CommandHandler\Task\ArticleUpdateTaskCommandHandlerTest
 */
final class ArticleUpdateTaskCommandHandlerDataProvider
{
    /**
     * @return iterable<string, array{commandData: array, mockArgs: array, mockTimes: array, expectedUuid: string}>
     */
    public static function provideSuccessScenarios(): iterable
    {
        yield 'update article title and description' => [
            'commandData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440001',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c9',
                'title' => 'Updated Title',
                'description' => 'This is an updated description with comprehensive content that meets all validation requirements for article updates.',
                'shortDescription' => 'Updated summary',
                'status' => 'draft',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'repositoryStoreSuccess' => true,
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'entityUpdate' => 1,
                'repositoryStore' => 1,
                'eventBusPublish' => 1,
            ],
            'expectedUuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c9',
        ];

        yield 'update article to published status' => [
            'commandData' => [
                'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae8',
                'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a12',
                'title' => 'Ready to Publish',
                'description' => 'This article article is ready for publication with all necessary content validation requirements successfully satisfied.',
                'shortDescription' => 'Ready for publication',
                'status' => 'published',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'repositoryStoreSuccess' => true,
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'entityUpdate' => 1,
                'repositoryStore' => 1,
                'eventBusPublish' => 1,
            ],
            'expectedUuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a12',
        ];

        yield 'update with unicode content' => [
            'commandData' => [
                'processUuid' => '3d6f2a88-5b1c-4e9d-8f3a-7c2b9e4a1f6e',
                'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d5f',
                'title' => 'Обновленные новости',
                'description' => 'Обновленное описание новости на русском языке с необходимым количеством символов для успешной валидации контента.',
                'shortDescription' => 'Краткое описание',
                'status' => 'draft',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'repositoryStoreSuccess' => true,
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'entityUpdate' => 1,
                'repositoryStore' => 1,
                'eventBusPublish' => 1,
            ],
            'expectedUuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d5f',
        ];

        yield 'update article to archived status' => [
            'commandData' => [
                'processUuid' => '9f8e7d6c-5b4a-3c2d-1e0f-9a8b7c6d5e40',
                'uuid' => 'c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e80',
                'title' => 'Archived Article',
                'description' => 'This article article is being archived and will no longer be displayed in the active article feed for end users.',
                'shortDescription' => 'Archived',
                'status' => 'archived',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'repositoryStoreSuccess' => true,
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'entityUpdate' => 1,
                'repositoryStore' => 1,
                'eventBusPublish' => 1,
            ],
            'expectedUuid' => 'c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e80',
        ];
    }

    /**
     * @return iterable<string, array{commandData: array, mockArgs: array, mockTimes: array, expectedException: class-string<\Throwable>, expectedMessage?: string}>
     */
    public static function provideFailureScenarios(): iterable
    {
        yield 'article not found in repository' => [
            'commandData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440001',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c9',
                'title' => 'Valid Title',
                'description' => 'This is a valid description that meets all validation requirements for article content updates.',
                'shortDescription' => 'Valid summary',
                'status' => 'draft',
            ],
            'mockArgs' => [
                'repositoryFindException' => \RuntimeException::class,
                'repositoryFindExceptionMessage' => 'Article not found',
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'entityUpdate' => 0,
                'repositoryStore' => 0,
                'eventBusPublish' => 0,
            ],
            'expectedException' => \RuntimeException::class,
            'expectedMessage' => 'Article not found',
        ];

        yield 'entity update throws validation exception' => [
            'commandData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440001',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c9',
                'title' => '',
                'description' => 'This is a valid description that meets all validation requirements for article content.',
                'shortDescription' => 'Valid summary',
                'status' => 'draft',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'entityUpdateException' => \InvalidArgumentException::class,
                'entityUpdateExceptionMessage' => 'Title cannot be empty',
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'entityUpdate' => 1,
                'repositoryStore' => 0,
                'eventBusPublish' => 0,
            ],
            'expectedException' => \InvalidArgumentException::class,
            'expectedMessage' => 'Title cannot be empty',
        ];

        yield 'repository storage fails' => [
            'commandData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440001',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c9',
                'title' => 'Valid Title',
                'description' => 'This is a valid description that meets all validation requirements for article content updates.',
                'shortDescription' => 'Valid summary',
                'status' => 'draft',
            ],
            'mockArgs' => [
                'repositoryFindReturns' => true,
                'entityUpdateSuccess' => true,
                'repositoryException' => \RuntimeException::class,
                'repositoryExceptionMessage' => 'Database update failed',
            ],
            'mockTimes' => [
                'repositoryFind' => 1,
                'entityUpdate' => 1,
                'repositoryStore' => 1,
                'eventBusPublish' => 0,
            ],
            'expectedException' => \RuntimeException::class,
            'expectedMessage' => 'Database update failed',
        ];
    }
}
