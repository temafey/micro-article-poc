<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\Processor;

/**
 * DataProvider for JobProgramProcessor tests.
 *
 * @see Tests\Unit\Article\Application\Processor\JobProgramProcessorTest
 */
final class JobProgramProcessorDataProvider
{
    /**
     * @return iterable<string, array{jobData: array, mockArgs: array, mockTimes: array, expectedResult: bool}>
     */
    public static function provideSuccessScenarios(): iterable
    {
        yield 'process article creation job' => [
            'jobData' => [
                'type' => 'article_creation',
                'payload' => [
                    'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                    'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                    'title' => 'Job Article Title',
                    'description' => 'This is a comprehensive job article description that contains sufficient content to meet the minimum validation requirements.',
                    'shortDescription' => 'Job summary',
                    'status' => 'draft',
                ],
            ],
            'mockArgs' => [
                'commandBusSuccess' => true,
            ],
            'mockTimes' => [
                'commandBusDispatch' => 1,
                'loggerInfo' => 1,
                'loggerError' => 0,
            ],
            'expectedResult' => true,
        ];

        yield 'process article publication job' => [
            'jobData' => [
                'type' => 'article_publication',
                'payload' => [
                    'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
                    'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
                ],
            ],
            'mockArgs' => [
                'commandBusSuccess' => true,
            ],
            'mockTimes' => [
                'commandBusDispatch' => 1,
                'loggerInfo' => 1,
                'loggerError' => 0,
            ],
            'expectedResult' => true,
        ];

        yield 'process article update job' => [
            'jobData' => [
                'type' => 'article_update',
                'payload' => [
                    'processUuid' => '3d6f2a88-5b1c-4e9d-8f3a-7c2b9e4a1f6d',
                    'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d5e',
                    'title' => 'Updated Job Title',
                    'description' => 'This is an updated job description with comprehensive content that meets all validation requirements for processing.',
                    'shortDescription' => 'Updated job summary',
                    'status' => 'published',
                ],
            ],
            'mockArgs' => [
                'commandBusSuccess' => true,
            ],
            'mockTimes' => [
                'commandBusDispatch' => 1,
                'loggerInfo' => 1,
                'loggerError' => 0,
            ],
            'expectedResult' => true,
        ];

        yield 'process article deletion job' => [
            'jobData' => [
                'type' => 'article_deletion',
                'payload' => [
                    'processUuid' => '9f8e7d6c-5b4a-3c2d-1e0f-9a8b7c6d5e4f',
                    'uuid' => 'c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e8f',
                ],
            ],
            'mockArgs' => [
                'commandBusSuccess' => true,
            ],
            'mockTimes' => [
                'commandBusDispatch' => 1,
                'loggerInfo' => 1,
                'loggerError' => 0,
            ],
            'expectedResult' => true,
        ];
    }

    /**
     * @return iterable<string, array{jobData: array, mockArgs: array, mockTimes: array, expectedException: class-string<\Throwable>}>
     */
    public static function provideFailureScenarios(): iterable
    {
        yield 'invalid job type throws exception' => [
            'jobData' => [
                'type' => 'invalid_type',
                'payload' => [],
            ],
            'mockArgs' => [],
            'mockTimes' => [
                'commandBusDispatch' => 0,
                'loggerInfo' => 0,
                'loggerError' => 1,
            ],
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'missing payload throws exception' => [
            'jobData' => [
                'type' => 'article_creation',
            ],
            'mockArgs' => [],
            'mockTimes' => [
                'commandBusDispatch' => 0,
                'loggerInfo' => 0,
                'loggerError' => 1,
            ],
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'command bus dispatch fails' => [
            'jobData' => [
                'type' => 'article_creation',
                'payload' => [
                    'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                    'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                    'title' => 'Job Title',
                    'description' => 'This is a comprehensive description with sufficient content to meet the minimum validation requirements.',
                    'shortDescription' => 'Summary',
                    'status' => 'draft',
                ],
            ],
            'mockArgs' => [
                'commandBusException' => \RuntimeException::class,
                'commandBusExceptionMessage' => 'Command dispatch failed',
            ],
            'mockTimes' => [
                'commandBusDispatch' => 1,
                'loggerInfo' => 0,
                'loggerError' => 1,
            ],
            'expectedException' => \RuntimeException::class,
        ];
    }

    /**
     * @return iterable<string, array{jobs: array, expectedProcessedCount: int}>
     */
    public static function provideBatchProcessing(): iterable
    {
        yield 'process multiple jobs in sequence' => [
            'jobs' => [
                [
                    'type' => 'article_creation',
                    'payload' => [
                        'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                        'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                        'title' => 'First Article',
                        'description' => 'First comprehensive description with sufficient content to meet validation requirements.',
                        'shortDescription' => 'First',
                        'status' => 'draft',
                    ],
                ],
                [
                    'type' => 'article_creation',
                    'payload' => [
                        'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
                        'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
                        'title' => 'Second Article',
                        'description' => 'Second comprehensive description with sufficient content to meet validation requirements.',
                        'shortDescription' => 'Second',
                        'status' => 'draft',
                    ],
                ],
            ],
            'expectedProcessedCount' => 2,
        ];

        yield 'partial batch failure continues processing' => [
            'jobs' => [
                [
                    'type' => 'article_creation',
                    'payload' => [
                        'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                        'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                        'title' => 'Valid Article',
                        'description' => 'Valid comprehensive description with sufficient content to meet all validation requirements.',
                        'shortDescription' => 'Valid',
                        'status' => 'draft',
                    ],
                ],
                [
                    'type' => 'invalid_type',
                    'payload' => [],
                ],
                [
                    'type' => 'article_creation',
                    'payload' => [
                        'processUuid' => '3d6f2a88-5b1c-4e9d-8f3a-7c2b9e4a1f6d',
                        'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d5e',
                        'title' => 'Another Valid',
                        'description' => 'Another valid comprehensive description meeting all necessary validation requirements.',
                        'shortDescription' => 'Another',
                        'status' => 'draft',
                    ],
                ],
            ],
            'expectedProcessedCount' => 2,
        ];
    }
}
