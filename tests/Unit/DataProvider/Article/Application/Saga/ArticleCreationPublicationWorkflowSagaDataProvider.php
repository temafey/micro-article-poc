<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\Saga;

/**
 * DataProvider for ArticleCreationPublicationWorkflowSaga tests.
 *
 * @see Tests\Unit\Article\Application\Saga\ArticleCreationPublicationWorkflowSagaTest
 */
final class ArticleCreationPublicationWorkflowSagaDataProvider
{
    /**
     * @return iterable<string, array{givenEvents: array, whenEvent: string, expectedCommands: array, expectedState: array, sagaDone: bool}>
     */
    public static function provideWorkflowSequences(): iterable
    {
        yield 'article created starts saga' => [
            'givenEvents' => [],
            'whenEvent' => 'ArticleCreatedEvent',
            'expectedCommands' => ['ArticleUpdateTaskCommand'],
            'expectedState' => [
                'status' => 'created',
                'articleUuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            ],
            'sagaDone' => false,
        ];

        yield 'article updated triggers publication check' => [
            'givenEvents' => ['ArticleCreatedEvent'],
            'whenEvent' => 'ArticleUpdatedEvent',
            'expectedCommands' => ['ArticlePublishTaskCommand'],
            'expectedState' => [
                'status' => 'updated',
                'articleUuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            ],
            'sagaDone' => false,
        ];

        yield 'article published completes saga' => [
            'givenEvents' => ['ArticleCreatedEvent', 'ArticleUpdatedEvent'],
            'whenEvent' => 'ArticlePublishedEvent',
            'expectedCommands' => [],
            'expectedState' => [
                'status' => 'published',
                'articleUuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            ],
            'sagaDone' => true,
        ];

        yield 'article deleted terminates saga early' => [
            'givenEvents' => ['ArticleCreatedEvent'],
            'whenEvent' => 'ArticleDeletedEvent',
            'expectedCommands' => [],
            'expectedState' => [
                'status' => 'deleted',
                'articleUuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'terminated' => true,
            ],
            'sagaDone' => true,
        ];

        yield 'article archived after publication completes saga' => [
            'givenEvents' => ['ArticleCreatedEvent', 'ArticleUpdatedEvent', 'ArticlePublishedEvent'],
            'whenEvent' => 'ArticleArchivedEvent',
            'expectedCommands' => [],
            'expectedState' => [
                'status' => 'archived',
                'articleUuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            ],
            'sagaDone' => true,
        ];
    }

    /**
     * @return iterable<string, array{initialState: array, event: string, expectedTransition: bool}>
     */
    public static function provideStateTransitions(): iterable
    {
        yield 'valid transition from created to updated' => [
            'initialState' => [
                'status' => 'created',
            ],
            'event' => 'ArticleUpdatedEvent',
            'expectedTransition' => true,
        ];

        yield 'valid transition from updated to published' => [
            'initialState' => [
                'status' => 'updated',
            ],
            'event' => 'ArticlePublishedEvent',
            'expectedTransition' => true,
        ];

        yield 'invalid transition from created directly to published' => [
            'initialState' => [
                'status' => 'created',
            ],
            'event' => 'ArticlePublishedEvent',
            'expectedTransition' => false,
        ];

        yield 'valid early termination with delete' => [
            'initialState' => [
                'status' => 'created',
            ],
            'event' => 'ArticleDeletedEvent',
            'expectedTransition' => true,
        ];

        yield 'invalid transition after saga completion' => [
            'initialState' => [
                'status' => 'published',
                'sagaDone' => true,
            ],
            'event' => 'ArticleUpdatedEvent',
            'expectedTransition' => false,
        ];
    }

    /**
     * @return iterable<string, array{processUuid: string, eventSequence: array, expectedCommandCount: int}>
     */
    public static function provideIdempotencyScenarios(): iterable
    {
        yield 'duplicate created event should not duplicate commands' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'eventSequence' => ['ArticleCreatedEvent', 'ArticleCreatedEvent'],
            'expectedCommandCount' => 1,
        ];

        yield 'duplicate updated event should be idempotent' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'eventSequence' => ['ArticleCreatedEvent', 'ArticleUpdatedEvent', 'ArticleUpdatedEvent'],
            'expectedCommandCount' => 2,
        ];

        yield 'events after saga completion should be ignored' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'eventSequence' => ['ArticleCreatedEvent', 'ArticleUpdatedEvent', 'ArticlePublishedEvent', 'ArticleUpdatedEvent'],
            'expectedCommandCount' => 2,
        ];
    }

    /**
     * @return iterable<string, array{eventData: array, expectedSagaId: string}>
     */
    public static function provideSagaIdentification(): iterable
    {
        yield 'saga identified by process uuid' => [
            'eventData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'articleUuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            ],
            'expectedSagaId' => '550e8400-e29b-41d4-a716-446655440000',
        ];

        yield 'saga identified by article uuid' => [
            'eventData' => [
                'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
                'articleUuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            ],
            'expectedSagaId' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
        ];
    }
}
