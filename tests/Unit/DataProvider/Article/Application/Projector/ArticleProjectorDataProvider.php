<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\Projector;

/**
 * DataProvider for ArticleProjector tests.
 *
 * @see Tests\Unit\Article\Application\Projector\ArticleProjectorTest
 */
final class ArticleProjectorDataProvider
{
    /**
     * @return iterable<string, array{eventType: string, mockTimes: array}>
     */
    public static function provideEventTypes(): iterable
    {
        yield 'created event triggers add read model' => [
            'eventType' => 'ArticleCreatedEvent',
            'mockTimes' => [
                'repositoryFind' => 1,
                'factoryCreate' => 1,
                'readModelAdd' => 1,
                'readModelSave' => 0,
                'readModelDelete' => 0,
            ],
        ];

        yield 'updated event triggers save read model' => [
            'eventType' => 'ArticleUpdatedEvent',
            'mockTimes' => [
                'repositoryFind' => 1,
                'factoryCreate' => 0,
                'readModelAdd' => 0,
                'readModelSave' => 1,
                'readModelDelete' => 0,
            ],
        ];

        yield 'published event triggers save read model' => [
            'eventType' => 'ArticlePublishedEvent',
            'mockTimes' => [
                'repositoryFind' => 1,
                'factoryCreate' => 0,
                'readModelAdd' => 0,
                'readModelSave' => 1,
                'readModelDelete' => 0,
            ],
        ];

        yield 'unpublished event triggers save read model' => [
            'eventType' => 'ArticleUnpublishedEvent',
            'mockTimes' => [
                'repositoryFind' => 1,
                'factoryCreate' => 0,
                'readModelAdd' => 0,
                'readModelSave' => 1,
                'readModelDelete' => 0,
            ],
        ];

        yield 'archived event triggers save read model' => [
            'eventType' => 'ArticleArchivedEvent',
            'mockTimes' => [
                'repositoryFind' => 1,
                'factoryCreate' => 0,
                'readModelAdd' => 0,
                'readModelSave' => 1,
                'readModelDelete' => 0,
            ],
        ];

        yield 'deleted event triggers delete read model' => [
            'eventType' => 'ArticleDeletedEvent',
            'mockTimes' => [
                'repositoryFind' => 0,
                'factoryCreate' => 0,
                'readModelAdd' => 0,
                'readModelSave' => 0,
                'readModelDelete' => 1,
            ],
        ];
    }

    /**
     * @return iterable<string, array{events: array, expectedOperations: array}>
     */
    public static function provideIdempotencyCases(): iterable
    {
        yield 'duplicate create events skip second' => [
            'events' => ['ArticleCreatedEvent', 'ArticleCreatedEvent'],
            'expectedOperations' => [
                'add' => 1,
                'save' => 0,
                'delete' => 0,
            ],
        ];

        yield 'duplicate update events process both' => [
            'events' => ['ArticleUpdatedEvent', 'ArticleUpdatedEvent'],
            'expectedOperations' => [
                'add' => 0,
                'save' => 2,
                'delete' => 0,
            ],
        ];

        yield 'create then update applies both' => [
            'events' => ['ArticleCreatedEvent', 'ArticleUpdatedEvent'],
            'expectedOperations' => [
                'add' => 1,
                'save' => 1,
                'delete' => 0,
            ],
        ];

        yield 'create then delete removes read model' => [
            'events' => ['ArticleCreatedEvent', 'ArticleDeletedEvent'],
            'expectedOperations' => [
                'add' => 1,
                'save' => 0,
                'delete' => 1,
            ],
        ];

        yield 'events after delete should be ignored' => [
            'events' => ['ArticleCreatedEvent', 'ArticleDeletedEvent', 'ArticleUpdatedEvent'],
            'expectedOperations' => [
                'add' => 1,
                'save' => 0,
                'delete' => 1,
            ],
        ];
    }

    /**
     * @return iterable<string, array{eventData: array, expectedReadModelData: array}>
     */
    public static function provideEventProjection(): iterable
    {
        yield 'project created event to read model' => [
            'eventData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Article Title',
                'description' => 'This is a comprehensive article description that contains sufficient content to meet the minimum validation requirements.',
                'shortDescription' => 'Short description',
                'status' => 'draft',
            ],
            'expectedReadModelData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Article Title',
                'description' => 'This is a comprehensive article description that contains sufficient content to meet the minimum validation requirements.',
                'shortDescription' => 'Short description',
                'status' => 'draft',
            ],
        ];

        yield 'project updated event to read model' => [
            'eventData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Updated Title',
                'description' => 'This is an updated description with comprehensive content that meets all validation requirements for article updates.',
                'shortDescription' => 'Updated summary',
                'status' => 'draft',
            ],
            'expectedReadModelData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Updated Title',
                'description' => 'This is an updated description with comprehensive content that meets all validation requirements for article updates.',
                'shortDescription' => 'Updated summary',
                'status' => 'draft',
            ],
        ];

        yield 'project published event updates status' => [
            'eventData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'status' => 'published',
            ],
            'expectedReadModelData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'status' => 'published',
            ],
        ];

        yield 'project archived event updates status' => [
            'eventData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'status' => 'archived',
            ],
            'expectedReadModelData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'status' => 'archived',
            ],
        ];
    }

    /**
     * @return iterable<string, array{eventData: array, mockArgs: array, expectedException: class-string<\Throwable>}>
     */
    public static function provideErrorScenarios(): iterable
    {
        yield 'repository throws exception on add' => [
            'eventData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Article Title',
                'description' => 'This is a comprehensive article description that contains sufficient content to meet validation requirements.',
                'shortDescription' => 'Short desc',
                'status' => 'draft',
            ],
            'mockArgs' => [
                'repositoryException' => \RuntimeException::class,
                'repositoryExceptionMessage' => 'Failed to add read model',
            ],
            'expectedException' => \RuntimeException::class,
        ];

        yield 'repository throws exception on save' => [
            'eventData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'title' => 'Updated Title',
                'description' => 'This is an updated description with comprehensive content meeting validation requirements.',
                'shortDescription' => 'Updated',
                'status' => 'draft',
            ],
            'mockArgs' => [
                'repositoryException' => \RuntimeException::class,
                'repositoryExceptionMessage' => 'Failed to save read model',
            ],
            'expectedException' => \RuntimeException::class,
        ];

        yield 'repository throws exception on delete' => [
            'eventData' => [
                'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            ],
            'mockArgs' => [
                'repositoryException' => \RuntimeException::class,
                'repositoryExceptionMessage' => 'Failed to delete read model',
            ],
            'expectedException' => \RuntimeException::class,
        ];
    }
}
