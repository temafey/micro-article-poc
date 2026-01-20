<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\Entity;

use Micro\Article\Domain\Event\ArticleArchivedEvent;
use Micro\Article\Domain\Event\ArticleCreatedEvent;
use Micro\Article\Domain\Event\ArticleDeletedEvent;
use Micro\Article\Domain\Event\ArticlePublishedEvent;
use Micro\Article\Domain\Event\ArticleUnpublishedEvent;
use Micro\Article\Domain\Event\ArticleUpdatedEvent;

/**
 * DataProvider for ArticleEntity Aggregate Root tests.
 *
 * @see \Tests\Unit\Article\Domain\Entity\ArticleEntityTest
 */
final class ArticleEntityDataProvider
{
    /**
     * Provides scenarios for successful article creation.
     *
     * @return iterable<string, array{
     *     inputData: array{
     *         processUuid: string,
     *         uuid: string,
     *         article: array{
     *             title: string,
     *             short_description: string,
     *             description: string,
     *             event_id: string|null
     *         },
     *         payload: array|null
     *     },
     *     expectedOutput: array{
     *         eventClass: class-string,
     *         status: string,
     *         hasSlug: bool,
     *         hasCreatedAt: bool,
     *         hasUpdatedAt: bool
     *     },
     *     mockArgs: array{
     *         slugGeneratorService: array{
     *             generateSlug: array{returnValue: string}
     *         },
     *         valueObjectFactory: array{
     *             makeArticle: array{called: bool}
     *         },
     *         eventFactory: array{
     *             makeArticleCreatedEvent: array{called: bool}
     *         }
     *     },
     *     mockTimes: array{
     *         slugGeneratorService: array{generateSlug: int},
     *         valueObjectFactory: array{makeArticle: int},
     *         eventFactory: array{makeArticleCreatedEvent: int}
     *     }
     * }>
     */
    public static function provideArticleCreationSuccessScenarios(): iterable
    {
        yield 'create article with all required fields' => [
            'inputData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '660e8400-e29b-41d4-a716-446655440001',
                'article' => [
                    'title' => 'Breaking Article Title',
                    'short_description' => 'Short description of the article',
                    'description' => 'Detailed description of the breaking article article',
                    'event_id' => null,
                ],
                'payload' => null,
            ],
            'expectedOutput' => [
                'eventClass' => ArticleCreatedEvent::class,
                'status' => 'draft',
                'hasSlug' => true,
                'hasCreatedAt' => true,
                'hasUpdatedAt' => true,
            ],
            'mockArgs' => [
                'slugGeneratorService' => [
                    'generateSlug' => [
                        'returnValue' => 'breaking-article-title',
                    ],
                ],
                'valueObjectFactory' => [
                    'makeArticle' => [
                        'called' => true,
                    ],
                ],
                'eventFactory' => [
                    'makeArticleCreatedEvent' => [
                        'called' => true,
                    ],
                ],
            ],
            'mockTimes' => [
                'slugGeneratorService' => [
                    'generateSlug' => 1,
                ],
                'valueObjectFactory' => [
                    'makeArticle' => 1,
                ],
                'eventFactory' => [
                    'makeArticleCreatedEvent' => 1,
                ],
            ],
        ];

        yield 'create article with event association' => [
            'inputData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '660e8400-e29b-41d4-a716-446655440001',
                'article' => [
                    'title' => 'Event Related Article',
                    'short_description' => 'Article about upcoming event',
                    'description' => 'Detailed description about the event',
                    'event_id' => '770e8400-e29b-41d4-a716-446655440002',
                ],
                'payload' => null,
            ],
            'expectedOutput' => [
                'eventClass' => ArticleCreatedEvent::class,
                'status' => 'draft',
                'hasSlug' => true,
                'hasCreatedAt' => true,
                'hasUpdatedAt' => true,
            ],
            'mockArgs' => [
                'slugGeneratorService' => [
                    'generateSlug' => [
                        'returnValue' => 'event-related-article',
                    ],
                ],
                'valueObjectFactory' => [
                    'makeArticle' => [
                        'called' => true,
                    ],
                ],
                'eventFactory' => [
                    'makeArticleCreatedEvent' => [
                        'called' => true,
                    ],
                ],
            ],
            'mockTimes' => [
                'slugGeneratorService' => [
                    'generateSlug' => 1,
                ],
                'valueObjectFactory' => [
                    'makeArticle' => 1,
                ],
                'eventFactory' => [
                    'makeArticleCreatedEvent' => 1,
                ],
            ],
        ];

        yield 'create article with unicode title' => [
            'inputData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '660e8400-e29b-41d4-a716-446655440001',
                'article' => [
                    'title' => 'Новости дня: Важное событие',
                    'short_description' => 'Краткое описание',
                    'description' => 'Подробное описание события',
                    'event_id' => null,
                ],
                'payload' => null,
            ],
            'expectedOutput' => [
                'eventClass' => ArticleCreatedEvent::class,
                'status' => 'draft',
                'hasSlug' => true,
                'hasCreatedAt' => true,
                'hasUpdatedAt' => true,
            ],
            'mockArgs' => [
                'slugGeneratorService' => [
                    'generateSlug' => [
                        'returnValue' => 'novosti-dnia-vazhnoe-sobytiie',
                    ],
                ],
                'valueObjectFactory' => [
                    'makeArticle' => [
                        'called' => true,
                    ],
                ],
                'eventFactory' => [
                    'makeArticleCreatedEvent' => [
                        'called' => true,
                    ],
                ],
            ],
            'mockTimes' => [
                'slugGeneratorService' => [
                    'generateSlug' => 1,
                ],
                'valueObjectFactory' => [
                    'makeArticle' => 1,
                ],
                'eventFactory' => [
                    'makeArticleCreatedEvent' => 1,
                ],
            ],
        ];
    }

    /**
     * Provides scenarios for article creation failures.
     *
     * @return iterable<string, array{
     *     inputData: array{
     *         processUuid: string,
     *         uuid: string,
     *         article: array{
     *             title: string|null,
     *             short_description: string,
     *             description: string,
     *             event_id: string|null
     *         },
     *         hasSlugService: bool
     *     },
     *     expectedOutput: array{
     *         exceptionClass: class-string<\Throwable>,
     *         exceptionMessage: string
     *     },
     *     mockArgs: array,
     *     mockTimes: array
     * }>
     */
    public static function provideArticleCreationFailureScenarios(): iterable
    {
        yield 'create without slug generator service throws exception' => [
            'inputData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '660e8400-e29b-41d4-a716-446655440001',
                'article' => [
                    'title' => 'Article Title',
                    'short_description' => 'Short description',
                    'description' => 'Full description',
                    'event_id' => null,
                ],
                'hasSlugService' => false,
            ],
            'expectedOutput' => [
                'exceptionClass' => \InvalidArgumentException::class,
                'exceptionMessage' => 'ArticleSlugGeneratorServiceInterface is required for article creation',
            ],
            'mockArgs' => [],
            'mockTimes' => [],
        ];

        yield 'create without title throws exception' => [
            'inputData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '660e8400-e29b-41d4-a716-446655440001',
                'article' => [
                    'title' => null,
                    'short_description' => 'Short description',
                    'description' => 'Full description',
                    'event_id' => null,
                ],
                'hasSlugService' => true,
            ],
            'expectedOutput' => [
                'exceptionClass' => \InvalidArgumentException::class,
                'exceptionMessage' => 'Article title is required for creation',
            ],
            'mockArgs' => [],
            'mockTimes' => [],
        ];
    }

    /**
     * Provides scenarios for successful article update.
     *
     * @return iterable<string, array{
     *     inputData: array{
     *         processUuid: string,
     *         uuid: string,
     *         article: array{
     *             title: string,
     *             short_description: string,
     *             description: string,
     *             slug: string|null
     *         },
     *         payload: array|null
     *     },
     *     expectedOutput: array{
     *         eventClass: class-string,
     *         slugRegenerated: bool
     *     },
     *     mockArgs: array{
     *         slugGeneratorService: array{
     *             generateSlug: array{returnValue: string}
     *         },
     *         valueObjectFactory: array{
     *             makeArticle: array{called: bool}
     *         },
     *         eventFactory: array{
     *             makeArticleUpdatedEvent: array{called: bool}
     *         }
     *     },
     *     mockTimes: array{
     *         slugGeneratorService: array{generateSlug: int},
     *         valueObjectFactory: array{makeArticle: int},
     *         eventFactory: array{makeArticleUpdatedEvent: int}
     *     }
     * }>
     */
    public static function provideArticleUpdateSuccessScenarios(): iterable
    {
        yield 'update article with changed title regenerates slug' => [
            'inputData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '660e8400-e29b-41d4-a716-446655440001',
                'article' => [
                    'title' => 'Updated Article Title',
                    'short_description' => 'Updated short description',
                    'description' => 'Updated detailed description',
                    'slug' => 'old-article-title',
                ],
                'payload' => null,
            ],
            'expectedOutput' => [
                'eventClass' => ArticleUpdatedEvent::class,
                'slugRegenerated' => true,
            ],
            'mockArgs' => [
                'slugGeneratorService' => [
                    'generateSlug' => [
                        'returnValue' => 'updated-article-title',
                    ],
                ],
                'valueObjectFactory' => [
                    'makeArticle' => [
                        'called' => true,
                    ],
                ],
                'eventFactory' => [
                    'makeArticleUpdatedEvent' => [
                        'called' => true,
                    ],
                ],
            ],
            'mockTimes' => [
                'slugGeneratorService' => [
                    'generateSlug' => 1,
                ],
                'valueObjectFactory' => [
                    'makeArticle' => 1,
                ],
                'eventFactory' => [
                    'makeArticleUpdatedEvent' => 1,
                ],
            ],
        ];

        yield 'update article preserves slug if title unchanged' => [
            'inputData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '660e8400-e29b-41d4-a716-446655440001',
                'article' => [
                    'title' => 'Same Title',
                    'short_description' => 'Updated short description only',
                    'description' => 'Updated detailed description only',
                    'slug' => 'same-title',
                ],
                'payload' => null,
            ],
            'expectedOutput' => [
                'eventClass' => ArticleUpdatedEvent::class,
                'slugRegenerated' => false,
            ],
            'mockArgs' => [
                'slugGeneratorService' => [
                    'generateSlug' => [
                        'returnValue' => 'same-title',
                    ],
                ],
                'valueObjectFactory' => [
                    'makeArticle' => [
                        'called' => true,
                    ],
                ],
                'eventFactory' => [
                    'makeArticleUpdatedEvent' => [
                        'called' => true,
                    ],
                ],
            ],
            'mockTimes' => [
                'slugGeneratorService' => [
                    'generateSlug' => 1,
                ],
                'valueObjectFactory' => [
                    'makeArticle' => 1,
                ],
                'eventFactory' => [
                    'makeArticleUpdatedEvent' => 1,
                ],
            ],
        ];
    }

    /**
     * Provides scenarios for article update failures.
     *
     * @return iterable<string, array{
     *     inputData: array{
     *         processUuid: string,
     *         uuid: string,
     *         article: array{
     *             title: string|null,
     *             short_description: string,
     *             description: string
     *         },
     *         hasSlugService: bool
     *     },
     *     expectedOutput: array{
     *         exceptionClass: class-string<\Throwable>,
     *         exceptionMessage: string
     *     },
     *     mockArgs: array,
     *     mockTimes: array
     * }>
     */
    public static function provideArticleUpdateFailureScenarios(): iterable
    {
        yield 'update without slug generator service throws exception' => [
            'inputData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '660e8400-e29b-41d4-a716-446655440001',
                'article' => [
                    'title' => 'Article Title',
                    'short_description' => 'Short description',
                    'description' => 'Full description',
                ],
                'hasSlugService' => false,
            ],
            'expectedOutput' => [
                'exceptionClass' => \InvalidArgumentException::class,
                'exceptionMessage' => 'ArticleSlugGeneratorServiceInterface is required for article update',
            ],
            'mockArgs' => [],
            'mockTimes' => [],
        ];

        yield 'update without title throws exception' => [
            'inputData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '660e8400-e29b-41d4-a716-446655440001',
                'article' => [
                    'title' => null,
                    'short_description' => 'Short description',
                    'description' => 'Full description',
                ],
                'hasSlugService' => true,
            ],
            'expectedOutput' => [
                'exceptionClass' => \InvalidArgumentException::class,
                'exceptionMessage' => 'Article title is required for update',
            ],
            'mockArgs' => [],
            'mockTimes' => [],
        ];
    }

    /**
     * Provides scenarios for article publish command.
     *
     * @return iterable<string, array{
     *     inputData: array{
     *         processUuid: string,
     *         uuid: string,
     *         currentStatus: string,
     *         payload: array|null
     *     },
     *     expectedOutput: array{
     *         eventClass: class-string,
     *         newStatus: string,
     *         hasPublishedAt: bool,
     *         hasUpdatedAt: bool
     *     },
     *     mockArgs: array{
     *         valueObjectFactory: array{
     *             makeStatus: array{returnValue: string},
     *             makePublishedAt: array{called: bool},
     *             makeUpdatedAt: array{called: bool}
     *         },
     *         eventFactory: array{
     *             makeArticlePublishedEvent: array{called: bool}
     *         }
     *     },
     *     mockTimes: array{
     *         valueObjectFactory: array{
     *             makeStatus: int,
     *             makePublishedAt: int,
     *             makeUpdatedAt: int
     *         },
     *         eventFactory: array{makeArticlePublishedEvent: int}
     *     }
     * }>
     */
    public static function provideArticlePublishSuccessScenarios(): iterable
    {
        yield 'publish draft article raises published event' => [
            'inputData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '660e8400-e29b-41d4-a716-446655440001',
                'currentStatus' => 'draft',
                'payload' => null,
            ],
            'expectedOutput' => [
                'eventClass' => ArticlePublishedEvent::class,
                'newStatus' => 'published',
                'hasPublishedAt' => true,
                'hasUpdatedAt' => true,
            ],
            'mockArgs' => [
                'valueObjectFactory' => [
                    'makeStatus' => [
                        'returnValue' => 'published',
                    ],
                    'makePublishedAt' => [
                        'called' => true,
                    ],
                    'makeUpdatedAt' => [
                        'called' => true,
                    ],
                ],
                'eventFactory' => [
                    'makeArticlePublishedEvent' => [
                        'called' => true,
                    ],
                ],
            ],
            'mockTimes' => [
                'valueObjectFactory' => [
                    'makeStatus' => 1,
                    'makePublishedAt' => 1,
                    'makeUpdatedAt' => 1,
                ],
                'eventFactory' => [
                    'makeArticlePublishedEvent' => 1,
                ],
            ],
        ];
    }

    /**
     * Provides scenarios for article unpublish command.
     *
     * @return iterable<string, array{
     *     inputData: array{
     *         processUuid: string,
     *         uuid: string,
     *         currentStatus: string,
     *         payload: array|null
     *     },
     *     expectedOutput: array{
     *         eventClass: class-string,
     *         newStatus: string,
     *         hasUpdatedAt: bool
     *     },
     *     mockArgs: array{
     *         valueObjectFactory: array{
     *             makeStatus: array{returnValue: string},
     *             makeUpdatedAt: array{called: bool}
     *         },
     *         eventFactory: array{
     *             makeArticleUnpublishedEvent: array{called: bool}
     *         }
     *     },
     *     mockTimes: array{
     *         valueObjectFactory: array{
     *             makeStatus: int,
     *             makeUpdatedAt: int
     *         },
     *         eventFactory: array{makeArticleUnpublishedEvent: int}
     *     }
     * }>
     */
    public static function provideArticleUnpublishSuccessScenarios(): iterable
    {
        yield 'unpublish published article raises unpublished event' => [
            'inputData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '660e8400-e29b-41d4-a716-446655440001',
                'currentStatus' => 'published',
                'payload' => null,
            ],
            'expectedOutput' => [
                'eventClass' => ArticleUnpublishedEvent::class,
                'newStatus' => 'draft',
                'hasUpdatedAt' => true,
            ],
            'mockArgs' => [
                'valueObjectFactory' => [
                    'makeStatus' => [
                        'returnValue' => 'draft',
                    ],
                    'makeUpdatedAt' => [
                        'called' => true,
                    ],
                ],
                'eventFactory' => [
                    'makeArticleUnpublishedEvent' => [
                        'called' => true,
                    ],
                ],
            ],
            'mockTimes' => [
                'valueObjectFactory' => [
                    'makeStatus' => 1,
                    'makeUpdatedAt' => 1,
                ],
                'eventFactory' => [
                    'makeArticleUnpublishedEvent' => 1,
                ],
            ],
        ];
    }

    /**
     * Provides scenarios for article archive command.
     *
     * @return iterable<string, array{
     *     inputData: array{
     *         processUuid: string,
     *         uuid: string,
     *         currentStatus: string,
     *         payload: array|null
     *     },
     *     expectedOutput: array{
     *         eventClass: class-string,
     *         newStatus: string,
     *         hasArchivedAt: bool,
     *         hasUpdatedAt: bool
     *     },
     *     mockArgs: array{
     *         valueObjectFactory: array{
     *             makeStatus: array{returnValue: string},
     *             makeArchivedAt: array{called: bool},
     *             makeUpdatedAt: array{called: bool}
     *         },
     *         eventFactory: array{
     *             makeArticleArchivedEvent: array{called: bool}
     *         }
     *     },
     *     mockTimes: array{
     *         valueObjectFactory: array{
     *             makeStatus: int,
     *             makeArchivedAt: int,
     *             makeUpdatedAt: int
     *         },
     *         eventFactory: array{makeArticleArchivedEvent: int}
     *     }
     * }>
     */
    public static function provideArticleArchiveSuccessScenarios(): iterable
    {
        yield 'archive published article raises archived event' => [
            'inputData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '660e8400-e29b-41d4-a716-446655440001',
                'currentStatus' => 'published',
                'payload' => null,
            ],
            'expectedOutput' => [
                'eventClass' => ArticleArchivedEvent::class,
                'newStatus' => 'archived',
                'hasArchivedAt' => true,
                'hasUpdatedAt' => true,
            ],
            'mockArgs' => [
                'valueObjectFactory' => [
                    'makeStatus' => [
                        'returnValue' => 'archived',
                    ],
                    'makeArchivedAt' => [
                        'called' => true,
                    ],
                    'makeUpdatedAt' => [
                        'called' => true,
                    ],
                ],
                'eventFactory' => [
                    'makeArticleArchivedEvent' => [
                        'called' => true,
                    ],
                ],
            ],
            'mockTimes' => [
                'valueObjectFactory' => [
                    'makeStatus' => 1,
                    'makeArchivedAt' => 1,
                    'makeUpdatedAt' => 1,
                ],
                'eventFactory' => [
                    'makeArticleArchivedEvent' => 1,
                ],
            ],
        ];
    }

    /**
     * Provides scenarios for article delete command.
     *
     * @return iterable<string, array{
     *     inputData: array{
     *         processUuid: string,
     *         uuid: string,
     *         payload: array|null
     *     },
     *     expectedOutput: array{
     *         eventClass: class-string
     *     },
     *     mockArgs: array{
     *         eventFactory: array{
     *             makeArticleDeletedEvent: array{called: bool}
     *         }
     *     },
     *     mockTimes: array{
     *         eventFactory: array{makeArticleDeletedEvent: int}
     *     }
     * }>
     */
    public static function provideArticleDeleteSuccessScenarios(): iterable
    {
        yield 'delete article raises deleted event' => [
            'inputData' => [
                'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                'uuid' => '660e8400-e29b-41d4-a716-446655440001',
                'payload' => null,
            ],
            'expectedOutput' => [
                'eventClass' => ArticleDeletedEvent::class,
            ],
            'mockArgs' => [
                'eventFactory' => [
                    'makeArticleDeletedEvent' => [
                        'called' => true,
                    ],
                ],
            ],
            'mockTimes' => [
                'eventFactory' => [
                    'makeArticleDeletedEvent' => 1,
                ],
            ],
        ];
    }

    /**
     * Provides scenarios for event application tests.
     *
     * @return iterable<string, array{
     *     inputData: array{
     *         eventData: array,
     *         eventClass: class-string
     *     },
     *     expectedOutput: array{
     *         processUuidSet: bool,
     *         uuidSet: bool,
     *         statusSet: bool,
     *         publishedAtSet: bool,
     *         archivedAtSet: bool,
     *         updatedAtSet: bool
     *     },
     *     mockArgs: array,
     *     mockTimes: array
     * }>
     */
    public static function provideEventApplicationScenarios(): iterable
    {
        yield 'apply article created event sets all initial properties' => [
            'inputData' => [
                'eventData' => [
                    'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                    'uuid' => '660e8400-e29b-41d4-a716-446655440001',
                    'article' => [
                        'title' => 'Article Title',
                        'short_description' => 'Short desc',
                        'description' => 'Full description',
                        'slug' => 'article-title',
                        'status' => 'draft',
                    ],
                ],
                'eventClass' => ArticleCreatedEvent::class,
            ],
            'expectedOutput' => [
                'processUuidSet' => true,
                'uuidSet' => true,
                'statusSet' => false,
                'publishedAtSet' => false,
                'archivedAtSet' => false,
                'updatedAtSet' => false,
            ],
            'mockArgs' => [],
            'mockTimes' => [],
        ];

        yield 'apply article published event sets status and timestamps' => [
            'inputData' => [
                'eventData' => [
                    'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                    'uuid' => '660e8400-e29b-41d4-a716-446655440001',
                    'status' => 'published',
                    'publishedAt' => '2025-01-15T10:00:00+00:00',
                    'updatedAt' => '2025-01-15T10:00:00+00:00',
                ],
                'eventClass' => ArticlePublishedEvent::class,
            ],
            'expectedOutput' => [
                'processUuidSet' => true,
                'uuidSet' => false,
                'statusSet' => true,
                'publishedAtSet' => true,
                'archivedAtSet' => false,
                'updatedAtSet' => true,
            ],
            'mockArgs' => [],
            'mockTimes' => [],
        ];

        yield 'apply article archived event sets status and archived timestamp' => [
            'inputData' => [
                'eventData' => [
                    'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
                    'uuid' => '660e8400-e29b-41d4-a716-446655440001',
                    'status' => 'archived',
                    'archivedAt' => '2025-01-20T10:00:00+00:00',
                    'updatedAt' => '2025-01-20T10:00:00+00:00',
                ],
                'eventClass' => ArticleArchivedEvent::class,
            ],
            'expectedOutput' => [
                'processUuidSet' => true,
                'uuidSet' => false,
                'statusSet' => true,
                'publishedAtSet' => false,
                'archivedAtSet' => true,
                'updatedAtSet' => true,
            ],
            'mockArgs' => [],
            'mockTimes' => [],
        ];
    }

    /**
     * Provides scenarios for aggregate root ID tests.
     *
     * @return iterable<string, array{
     *     inputData: array{uuid: string},
     *     expectedOutput: array{aggregateRootId: string},
     *     mockArgs: array,
     *     mockTimes: array
     * }>
     */
    public static function provideAggregateRootIdScenarios(): iterable
    {
        yield 'get aggregate root id returns uuid value' => [
            'inputData' => [
                'uuid' => '660e8400-e29b-41d4-a716-446655440001',
            ],
            'expectedOutput' => [
                'aggregateRootId' => '660e8400-e29b-41d4-a716-446655440001',
            ],
            'mockArgs' => [],
            'mockTimes' => [],
        ];
    }

    /**
     * Provides scenarios for serialization tests.
     *
     * @return iterable<string, array{
     *     inputData: array{
     *         uuid: string,
     *         title: string,
     *         status: string
     *     },
     *     expectedOutput: array{
     *         hasUuid: bool,
     *         hasTitle: bool,
     *         hasStatus: bool
     *     },
     *     mockArgs: array,
     *     mockTimes: array
     * }>
     */
    public static function provideSerializationScenarios(): iterable
    {
        yield 'serialize entity returns normalized array' => [
            'inputData' => [
                'uuid' => '660e8400-e29b-41d4-a716-446655440001',
                'title' => 'Article Title',
                'status' => 'draft',
            ],
            'expectedOutput' => [
                'hasUuid' => true,
                'hasTitle' => true,
                'hasStatus' => true,
            ],
            'mockArgs' => [],
            'mockTimes' => [],
        ];
    }
}
