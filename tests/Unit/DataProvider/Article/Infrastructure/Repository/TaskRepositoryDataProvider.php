<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Infrastructure\Repository;

use Micro\Article\Application\Factory\CommandFactoryInterface;

/**
 * DataProvider for TaskRepository tests.
 */
final class TaskRepositoryDataProvider
{
    /**
     * Data for addArticleCreateTask scenarios.
     */
    public static function addArticleCreateTaskScenarios(): \Generator
    {
        yield 'create task with full article data' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'articleData' => [
                'title' => 'Test Article Title',
                'slug' => 'test-article-title',
                'short_description' => 'Short description.',
                'description' => 'Full description that meets the minimum requirements of fifty characters for validation testing purposes.',
                'event_id' => 12345,
                'status' => 'draft',
            ],
            'expectedType' => CommandFactoryInterface::ARTICLE_CREATE_COMMAND,
        ];
    }

    /**
     * Data for addArticleUpdateTask scenarios.
     */
    public static function addArticleUpdateTaskScenarios(): \Generator
    {
        yield 'update task with article data' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'articleData' => [
                'title' => 'Updated Article Title',
                'description' => 'Updated description that meets the minimum requirements of fifty characters for validation testing.',
            ],
            'expectedType' => CommandFactoryInterface::ARTICLE_UPDATE_COMMAND,
        ];
    }

    /**
     * Data for simple task scenarios (publish, unpublish, archive, delete).
     */
    public static function simpleTaskScenarios(): \Generator
    {
        yield 'publish task' => [
            'method' => 'addArticlePublishTask',
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'expectedType' => CommandFactoryInterface::ARTICLE_PUBLISH_COMMAND,
        ];

        yield 'unpublish task' => [
            'method' => 'addArticleUnpublishTask',
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'expectedType' => CommandFactoryInterface::ARTICLE_UNPUBLISH_COMMAND,
        ];

        yield 'archive task' => [
            'method' => 'addArticleArchiveTask',
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'expectedType' => CommandFactoryInterface::ARTICLE_ARCHIVE_COMMAND,
        ];

        yield 'delete task' => [
            'method' => 'addArticleDeleteTask',
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'expectedType' => CommandFactoryInterface::ARTICLE_DELETE_COMMAND,
        ];
    }
}
