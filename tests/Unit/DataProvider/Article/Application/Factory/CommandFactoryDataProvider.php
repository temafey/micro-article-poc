<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\Factory;

/**
 * DataProvider for CommandFactory tests.
 *
 * @see Tests\Unit\Article\Application\Factory\CommandFactoryTest
 */
final class CommandFactoryDataProvider
{
    /**
     * @return iterable<string, array{commandType: string, inputData: array, expectedCommandClass: class-string}>
     */
    public static function provideValidCommandCreation(): iterable
    {
        yield 'create article creation task command' => [
            'commandType' => 'create',
            'inputData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Factory Article Title',
                'description' => 'This is a comprehensive factory article description that contains sufficient content to meet validation requirements.',
                'shortDescription' => 'Factory summary',
                'status' => 'draft',
            ],
            'expectedCommandClass' => 'Micro\\Article\\Application\\Command\\Task\\ArticleCreateTaskCommand',
        ];

        yield 'create article update task command' => [
            'commandType' => 'update',
            'inputData' => [
                'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
                'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
                'title' => 'Updated Factory Title',
                'description' => 'This is an updated factory description with comprehensive content meeting all validation requirements.',
                'shortDescription' => 'Updated factory',
                'status' => 'published',
            ],
            'expectedCommandClass' => 'Micro\\Article\\Application\\Command\\Task\\ArticleUpdateTaskCommand',
        ];

        yield 'create article publish task command' => [
            'commandType' => 'publish',
            'inputData' => [
                'processUuid' => '3d6f2a88-5b1c-4e9d-8f3a-7c2b9e4a1f6d',
                'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d5e',
            ],
            'expectedCommandClass' => 'Micro\\Article\\Application\\Command\\Task\\ArticlePublishTaskCommand',
        ];

        yield 'create article unpublish task command' => [
            'commandType' => 'unpublish',
            'inputData' => [
                'processUuid' => '9f8e7d6c-5b4a-3c2d-1e0f-9a8b7c6d5e4f',
                'uuid' => 'c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e8f',
            ],
            'expectedCommandClass' => 'Micro\\Article\\Application\\Command\\Task\\ArticleUnpublishTaskCommand',
        ];

        yield 'create article archive task command' => [
            'commandType' => 'archive',
            'inputData' => [
                'processUuid' => 'd4e5f6a7-b8c9-0d1e-2f3a-4b5c6d7e8f9a',
                'uuid' => 'e5f6a7b8-c9d0-1e2f-3a4b-5c6d7e8f9a0b',
            ],
            'expectedCommandClass' => 'Micro\\Article\\Application\\Command\\Task\\ArticleArchiveTaskCommand',
        ];

        yield 'create article delete task command' => [
            'commandType' => 'delete',
            'inputData' => [
                'processUuid' => 'f6a7b8c9-d0e1-2f3a-4b5c-6d7e8f9a0b1c',
                'uuid' => 'a7b8c9d0-e1f2-3a4b-5c6d-7e8f9a0b1c2d',
            ],
            'expectedCommandClass' => 'Micro\\Article\\Application\\Command\\Task\\ArticleDeleteTaskCommand',
        ];
    }

    /**
     * @return iterable<string, array{commandType: string, inputData: array, expectedException: class-string<\Throwable>, expectedMessage?: string}>
     */
    public static function provideInvalidCommandCreation(): iterable
    {
        yield 'unknown command type throws exception' => [
            'commandType' => 'unknown',
            'inputData' => [],
            'expectedException' => \InvalidArgumentException::class,
            'expectedMessage' => 'Unknown command type',
        ];

        yield 'missing required fields throws exception' => [
            'commandType' => 'create',
            'inputData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            ],
            'expectedException' => \InvalidArgumentException::class,
            'expectedMessage' => 'Missing required fields',
        ];

        yield 'invalid uuid format throws exception' => [
            'commandType' => 'create',
            'inputData' => [
                'processUuid' => 'invalid-uuid',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Valid Title',
                'description' => 'This is a valid description with comprehensive content meeting validation requirements.',
                'shortDescription' => 'Valid',
                'status' => 'draft',
            ],
            'expectedException' => \InvalidArgumentException::class,
            'expectedMessage' => 'Invalid UUID format',
        ];

        yield 'empty title throws exception' => [
            'commandType' => 'create',
            'inputData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => '',
                'description' => 'This is a valid description with comprehensive content meeting validation requirements.',
                'shortDescription' => 'Valid',
                'status' => 'draft',
            ],
            'expectedException' => \InvalidArgumentException::class,
            'expectedMessage' => 'Title cannot be empty',
        ];

        yield 'description too short throws exception' => [
            'commandType' => 'create',
            'inputData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Valid Title',
                'description' => 'Short',
                'shortDescription' => 'Valid',
                'status' => 'draft',
            ],
            'expectedException' => \InvalidArgumentException::class,
            'expectedMessage' => 'Description too short',
        ];
    }

    /**
     * @return iterable<string, array{rawData: array, expectedCommandType: string, expectedData: array}>
     */
    public static function provideCommandMapping(): iterable
    {
        yield 'map raw data to create command' => [
            'rawData' => [
                'action' => 'create',
                'process_uuid' => '550e8400-e29b-41d4-a716-446655440000',
                'article_uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Mapped Title',
                'description' => 'This is a mapped description with comprehensive content meeting all validation requirements.',
                'short_description' => 'Mapped',
                'status' => 'draft',
            ],
            'expectedCommandType' => 'create',
            'expectedData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Mapped Title',
                'description' => 'This is a mapped description with comprehensive content meeting all validation requirements.',
                'shortDescription' => 'Mapped',
                'status' => 'draft',
            ],
        ];

        yield 'map raw data to publish command' => [
            'rawData' => [
                'action' => 'publish',
                'process_uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
                'article_uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            ],
            'expectedCommandType' => 'publish',
            'expectedData' => [
                'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
                'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            ],
        ];
    }

    /**
     * @return iterable<string, array{commands: array, expectedBatchSize: int}>
     */
    public static function provideBatchCommandCreation(): iterable
    {
        yield 'create batch of create commands' => [
            'commands' => [
                [
                    'type' => 'create',
                    'data' => [
                        'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                        'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                        'title' => 'First Article',
                        'description' => 'First comprehensive description with content meeting validation requirements.',
                        'shortDescription' => 'First',
                        'status' => 'draft',
                    ],
                ],
                [
                    'type' => 'create',
                    'data' => [
                        'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
                        'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
                        'title' => 'Second Article',
                        'description' => 'Second comprehensive description with content meeting validation requirements.',
                        'shortDescription' => 'Second',
                        'status' => 'draft',
                    ],
                ],
            ],
            'expectedBatchSize' => 2,
        ];

        yield 'create batch of mixed commands' => [
            'commands' => [
                [
                    'type' => 'create',
                    'data' => [
                        'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                        'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                        'title' => 'New Article',
                        'description' => 'Comprehensive description with content meeting validation requirements.',
                        'shortDescription' => 'New',
                        'status' => 'draft',
                    ],
                ],
                [
                    'type' => 'publish',
                    'data' => [
                        'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
                        'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
                    ],
                ],
                [
                    'type' => 'delete',
                    'data' => [
                        'processUuid' => '3d6f2a88-5b1c-4e9d-8f3a-7c2b9e4a1f6d',
                        'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d5e',
                    ],
                ],
            ],
            'expectedBatchSize' => 3,
        ];
    }
}
