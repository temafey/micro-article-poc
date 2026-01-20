<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\Factory;

/**
 * DataProvider for QueryFactory tests.
 *
 * @see Tests\Unit\Article\Application\Factory\QueryFactoryTest
 */
final class QueryFactoryDataProvider
{
    /**
     * @return iterable<string, array{queryType: string, inputData: array, expectedQueryClass: class-string}>
     */
    public static function provideValidQueryCreation(): iterable
    {
        yield 'create fetch one article query' => [
            'queryType' => 'fetch_one',
            'inputData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            ],
            'expectedQueryClass' => 'Micro\\Article\\Application\\Query\\FetchOneArticleQuery',
        ];

        yield 'create fetch collection article query' => [
            'queryType' => 'fetch_collection',
            'inputData' => [
                'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
                'page' => 1,
                'limit' => 10,
                'filters' => [
                    'status' => 'published',
                ],
            ],
            'expectedQueryClass' => 'Micro\\Article\\Application\\Query\\FetchCollectionArticleQuery',
        ];

        yield 'create find by uuid query' => [
            'queryType' => 'find_by_uuid',
            'inputData' => [
                'processUuid' => '3d6f2a88-5b1c-4e9d-8f3a-7c2b9e4a1f6d',
                'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d5e',
            ],
            'expectedQueryClass' => 'Micro\\Article\\Application\\Query\\FindByUuidArticleQuery',
        ];

        yield 'create find by status query' => [
            'queryType' => 'find_by_status',
            'inputData' => [
                'processUuid' => '9f8e7d6c-5b4a-3c2d-1e0f-9a8b7c6d5e4f',
                'status' => 'draft',
                'page' => 1,
                'limit' => 20,
            ],
            'expectedQueryClass' => 'Micro\\Article\\Application\\Query\\FindByStatusArticleQuery',
        ];

        yield 'create count article query' => [
            'queryType' => 'count',
            'inputData' => [
                'processUuid' => 'd4e5f6a7-b8c9-0d1e-2f3a-4b5c6d7e8f9a',
                'filters' => [
                    'status' => 'published',
                ],
            ],
            'expectedQueryClass' => 'Micro\\Article\\Application\\Query\\CountArticleQuery',
        ];
    }

    /**
     * @return iterable<string, array{queryType: string, inputData: array, expectedException: class-string<\Throwable>, expectedMessage?: string}>
     */
    public static function provideInvalidQueryCreation(): iterable
    {
        yield 'unknown query type throws exception' => [
            'queryType' => 'unknown_query',
            'inputData' => [],
            'expectedException' => \InvalidArgumentException::class,
            'expectedMessage' => 'Unknown query type',
        ];

        yield 'missing required uuid throws exception' => [
            'queryType' => 'fetch_one',
            'inputData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            ],
            'expectedException' => \InvalidArgumentException::class,
            'expectedMessage' => 'Missing required field: uuid',
        ];

        yield 'invalid uuid format throws exception' => [
            'queryType' => 'fetch_one',
            'inputData' => [
                'processUuid' => 'invalid-process-uuid',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            ],
            'expectedException' => \InvalidArgumentException::class,
            'expectedMessage' => 'Invalid UUID format',
        ];

        yield 'invalid pagination parameters throw exception' => [
            'queryType' => 'fetch_collection',
            'inputData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'page' => 0,
                'limit' => -10,
            ],
            'expectedException' => \InvalidArgumentException::class,
            'expectedMessage' => 'Invalid pagination parameters',
        ];

        yield 'invalid status filter throws exception' => [
            'queryType' => 'find_by_status',
            'inputData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'status' => 'invalid_status',
            ],
            'expectedException' => \InvalidArgumentException::class,
            'expectedMessage' => 'Invalid status value',
        ];
    }

    /**
     * @return iterable<string, array{rawData: array, expectedQueryType: string, expectedData: array}>
     */
    public static function provideQueryMapping(): iterable
    {
        yield 'map raw data to fetch one query' => [
            'rawData' => [
                'action' => 'fetch_one',
                'process_uuid' => '550e8400-e29b-41d4-a716-446655440000',
                'article_uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            ],
            'expectedQueryType' => 'fetch_one',
            'expectedData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            ],
        ];

        yield 'map raw data to fetch collection query with filters' => [
            'rawData' => [
                'action' => 'fetch_collection',
                'process_uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
                'page' => 2,
                'limit' => 15,
                'filter_status' => 'published',
            ],
            'expectedQueryType' => 'fetch_collection',
            'expectedData' => [
                'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
                'page' => 2,
                'limit' => 15,
                'filters' => [
                    'status' => 'published',
                ],
            ],
        ];

        yield 'map raw data to count query' => [
            'rawData' => [
                'action' => 'count',
                'process_uuid' => '3d6f2a88-5b1c-4e9d-8f3a-7c2b9e4a1f6d',
                'filter_status' => 'draft',
            ],
            'expectedQueryType' => 'count',
            'expectedData' => [
                'processUuid' => '3d6f2a88-5b1c-4e9d-8f3a-7c2b9e4a1f6d',
                'filters' => [
                    'status' => 'draft',
                ],
            ],
        ];
    }

    /**
     * @return iterable<string, array{filters: array, expectedQueryFilters: array}>
     */
    public static function provideFilterNormalization(): iterable
    {
        yield 'normalize single status filter' => [
            'filters' => [
                'status' => 'published',
            ],
            'expectedQueryFilters' => [
                'status' => 'published',
            ],
        ];

        yield 'normalize multiple filters' => [
            'filters' => [
                'status' => 'published',
                'createdAfter' => '2024-01-01',
                'createdBefore' => '2024-12-31',
            ],
            'expectedQueryFilters' => [
                'status' => 'published',
                'createdAfter' => '2024-01-01',
                'createdBefore' => '2024-12-31',
            ],
        ];

        yield 'normalize empty filters' => [
            'filters' => [],
            'expectedQueryFilters' => [],
        ];

        yield 'normalize filters with null values removed' => [
            'filters' => [
                'status' => 'draft',
                'author' => null,
                'category' => 'tech',
            ],
            'expectedQueryFilters' => [
                'status' => 'draft',
                'category' => 'tech',
            ],
        ];
    }

    /**
     * @return iterable<string, array{page: int, limit: int, expectedOffset: int, expectedLimit: int}>
     */
    public static function providePaginationCalculation(): iterable
    {
        yield 'first page with limit 10' => [
            'page' => 1,
            'limit' => 10,
            'expectedOffset' => 0,
            'expectedLimit' => 10,
        ];

        yield 'second page with limit 10' => [
            'page' => 2,
            'limit' => 10,
            'expectedOffset' => 10,
            'expectedLimit' => 10,
        ];

        yield 'third page with limit 25' => [
            'page' => 3,
            'limit' => 25,
            'expectedOffset' => 50,
            'expectedLimit' => 25,
        ];

        yield 'page 10 with limit 5' => [
            'page' => 10,
            'limit' => 5,
            'expectedOffset' => 45,
            'expectedLimit' => 5,
        ];
    }

    /**
     * @return iterable<string, array{queries: array, expectedBatchSize: int}>
     */
    public static function provideBatchQueryCreation(): iterable
    {
        yield 'create batch of fetch one queries' => [
            'queries' => [
                [
                    'type' => 'fetch_one',
                    'data' => [
                        'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                        'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                    ],
                ],
                [
                    'type' => 'fetch_one',
                    'data' => [
                        'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
                        'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
                    ],
                ],
            ],
            'expectedBatchSize' => 2,
        ];

        yield 'create batch of mixed query types' => [
            'queries' => [
                [
                    'type' => 'fetch_one',
                    'data' => [
                        'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                        'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                    ],
                ],
                [
                    'type' => 'fetch_collection',
                    'data' => [
                        'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
                        'page' => 1,
                        'limit' => 10,
                    ],
                ],
                [
                    'type' => 'count',
                    'data' => [
                        'processUuid' => '3d6f2a88-5b1c-4e9d-8f3a-7c2b9e4a1f6d',
                        'filters' => [
                            'status' => 'published',
                        ],
                    ],
                ],
            ],
            'expectedBatchSize' => 3,
        ];
    }
}
