<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\Processor;

/**
 * DataProvider for QueueEventProcessor tests.
 *
 * @see Tests\Unit\Article\Application\Processor\QueueEventProcessorTest
 */
final class QueueEventProcessorDataProvider
{
    /**
     * @return iterable<string, array{eventData: array, mockArgs: array, mockTimes: array, expectedResult: bool}>
     */
    public static function provideSuccessScenarios(): iterable
    {
        yield 'process article created event from queue' => [
            'eventData' => [
                'eventType' => 'ArticleCreatedEvent',
                'payload' => [
                    'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                    'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                    'title' => 'Queue Article Title',
                    'description' => 'This is a comprehensive queue article description that contains sufficient content to meet validation requirements.',
                    'shortDescription' => 'Queue summary',
                    'status' => 'draft',
                ],
                'occurredAt' => '2024-01-01T10:00:00+00:00',
            ],
            'mockArgs' => [
                'eventBusSuccess' => true,
                'projectorSuccess' => true,
            ],
            'mockTimes' => [
                'eventBusDispatch' => 1,
                'projectorHandle' => 1,
                'loggerInfo' => 1,
                'loggerError' => 0,
            ],
            'expectedResult' => true,
        ];

        yield 'process article updated event from queue' => [
            'eventData' => [
                'eventType' => 'ArticleUpdatedEvent',
                'payload' => [
                    'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
                    'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
                    'title' => 'Updated Queue Title',
                    'description' => 'This is an updated queue description with comprehensive content meeting all validation requirements.',
                    'shortDescription' => 'Updated queue',
                    'status' => 'draft',
                ],
                'occurredAt' => '2024-01-01T11:00:00+00:00',
            ],
            'mockArgs' => [
                'eventBusSuccess' => true,
                'projectorSuccess' => true,
            ],
            'mockTimes' => [
                'eventBusDispatch' => 1,
                'projectorHandle' => 1,
                'loggerInfo' => 1,
                'loggerError' => 0,
            ],
            'expectedResult' => true,
        ];

        yield 'process article published event from queue' => [
            'eventData' => [
                'eventType' => 'ArticlePublishedEvent',
                'payload' => [
                    'processUuid' => '3d6f2a88-5b1c-4e9d-8f3a-7c2b9e4a1f6d',
                    'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d5e',
                ],
                'occurredAt' => '2024-01-01T12:00:00+00:00',
            ],
            'mockArgs' => [
                'eventBusSuccess' => true,
                'projectorSuccess' => true,
            ],
            'mockTimes' => [
                'eventBusDispatch' => 1,
                'projectorHandle' => 1,
                'loggerInfo' => 1,
                'loggerError' => 0,
            ],
            'expectedResult' => true,
        ];

        yield 'process article deleted event from queue' => [
            'eventData' => [
                'eventType' => 'ArticleDeletedEvent',
                'payload' => [
                    'processUuid' => '9f8e7d6c-5b4a-3c2d-1e0f-9a8b7c6d5e4f',
                    'uuid' => 'c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e8f',
                ],
                'occurredAt' => '2024-01-01T13:00:00+00:00',
            ],
            'mockArgs' => [
                'eventBusSuccess' => true,
                'projectorSuccess' => true,
            ],
            'mockTimes' => [
                'eventBusDispatch' => 1,
                'projectorHandle' => 1,
                'loggerInfo' => 1,
                'loggerError' => 0,
            ],
            'expectedResult' => true,
        ];
    }

    /**
     * @return iterable<string, array{eventData: array, mockArgs: array, mockTimes: array, expectedException: class-string<\Throwable>}>
     */
    public static function provideFailureScenarios(): iterable
    {
        yield 'invalid event type throws exception' => [
            'eventData' => [
                'eventType' => 'InvalidEvent',
                'payload' => [],
                'occurredAt' => '2024-01-01T10:00:00+00:00',
            ],
            'mockArgs' => [],
            'mockTimes' => [
                'eventBusDispatch' => 0,
                'projectorHandle' => 0,
                'loggerInfo' => 0,
                'loggerError' => 1,
            ],
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'missing payload throws exception' => [
            'eventData' => [
                'eventType' => 'ArticleCreatedEvent',
                'occurredAt' => '2024-01-01T10:00:00+00:00',
            ],
            'mockArgs' => [],
            'mockTimes' => [
                'eventBusDispatch' => 0,
                'projectorHandle' => 0,
                'loggerInfo' => 0,
                'loggerError' => 1,
            ],
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'event bus dispatch fails' => [
            'eventData' => [
                'eventType' => 'ArticleCreatedEvent',
                'payload' => [
                    'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                    'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                    'title' => 'Valid Title',
                    'description' => 'This is a valid description with comprehensive content meeting all validation requirements.',
                    'shortDescription' => 'Summary',
                    'status' => 'draft',
                ],
                'occurredAt' => '2024-01-01T10:00:00+00:00',
            ],
            'mockArgs' => [
                'eventBusException' => \RuntimeException::class,
                'eventBusExceptionMessage' => 'Event dispatch failed',
            ],
            'mockTimes' => [
                'eventBusDispatch' => 1,
                'projectorHandle' => 0,
                'loggerInfo' => 0,
                'loggerError' => 1,
            ],
            'expectedException' => \RuntimeException::class,
        ];

        yield 'projector handling fails' => [
            'eventData' => [
                'eventType' => 'ArticleCreatedEvent',
                'payload' => [
                    'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                    'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                    'title' => 'Valid Title',
                    'description' => 'This is a valid description with comprehensive content meeting all validation requirements.',
                    'shortDescription' => 'Summary',
                    'status' => 'draft',
                ],
                'occurredAt' => '2024-01-01T10:00:00+00:00',
            ],
            'mockArgs' => [
                'eventBusSuccess' => true,
                'projectorException' => \RuntimeException::class,
                'projectorExceptionMessage' => 'Projector failed',
            ],
            'mockTimes' => [
                'eventBusDispatch' => 1,
                'projectorHandle' => 1,
                'loggerInfo' => 0,
                'loggerError' => 1,
            ],
            'expectedException' => \RuntimeException::class,
        ];
    }

    /**
     * @return iterable<string, array{events: array, expectedProcessedCount: int, expectedSkippedCount: int}>
     */
    public static function provideIdempotencyScenarios(): iterable
    {
        yield 'duplicate events processed only once' => [
            'events' => [
                [
                    'eventType' => 'ArticleCreatedEvent',
                    'eventId' => 'event-001',
                    'payload' => [
                        'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                        'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                        'title' => 'Article Title',
                        'description' => 'Comprehensive description with sufficient content meeting validation requirements.',
                        'shortDescription' => 'Summary',
                        'status' => 'draft',
                    ],
                ],
                [
                    'eventType' => 'ArticleCreatedEvent',
                    'eventId' => 'event-001',
                    'payload' => [
                        'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                        'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                        'title' => 'Article Title',
                        'description' => 'Comprehensive description with sufficient content meeting validation requirements.',
                        'shortDescription' => 'Summary',
                        'status' => 'draft',
                    ],
                ],
            ],
            'expectedProcessedCount' => 1,
            'expectedSkippedCount' => 1,
        ];

        yield 'different events all processed' => [
            'events' => [
                [
                    'eventType' => 'ArticleCreatedEvent',
                    'eventId' => 'event-001',
                    'payload' => [
                        'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                        'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                        'title' => 'First Article',
                        'description' => 'First comprehensive description with content meeting validation requirements.',
                        'shortDescription' => 'First',
                        'status' => 'draft',
                    ],
                ],
                [
                    'eventType' => 'ArticleCreatedEvent',
                    'eventId' => 'event-002',
                    'payload' => [
                        'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
                        'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
                        'title' => 'Second Article',
                        'description' => 'Second comprehensive description with content meeting validation requirements.',
                        'shortDescription' => 'Second',
                        'status' => 'draft',
                    ],
                ],
            ],
            'expectedProcessedCount' => 2,
            'expectedSkippedCount' => 0,
        ];
    }

    /**
     * @return iterable<string, array{events: array, expectedOrderedUuids: array}>
     */
    public static function provideEventOrdering(): iterable
    {
        yield 'events processed in chronological order' => [
            'events' => [
                [
                    'eventType' => 'ArticleCreatedEvent',
                    'payload' => [
                        'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                    ],
                    'occurredAt' => '2024-01-01T10:00:00+00:00',
                ],
                [
                    'eventType' => 'ArticleUpdatedEvent',
                    'payload' => [
                        'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                    ],
                    'occurredAt' => '2024-01-01T11:00:00+00:00',
                ],
                [
                    'eventType' => 'ArticlePublishedEvent',
                    'payload' => [
                        'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                    ],
                    'occurredAt' => '2024-01-01T12:00:00+00:00',
                ],
            ],
            'expectedOrderedUuids' => [
                '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            ],
        ];

        yield 'out of order events reordered by timestamp' => [
            'events' => [
                [
                    'eventType' => 'ArticlePublishedEvent',
                    'payload' => [
                        'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                    ],
                    'occurredAt' => '2024-01-01T12:00:00+00:00',
                ],
                [
                    'eventType' => 'ArticleCreatedEvent',
                    'payload' => [
                        'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                    ],
                    'occurredAt' => '2024-01-01T10:00:00+00:00',
                ],
                [
                    'eventType' => 'ArticleUpdatedEvent',
                    'payload' => [
                        'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                    ],
                    'occurredAt' => '2024-01-01T11:00:00+00:00',
                ],
            ],
            'expectedOrderedUuids' => [
                '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            ],
        ];
    }
}
