<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\Command\Task;

/**
 * DataProvider for ArticlePublishTaskCommand tests.
 *
 * Usage in test class:
 * #[DataProviderExternal(ArticlePublishTaskCommandDataProvider::class, 'provideValidConstructionData')]
 *
 * @see Tests\Unit\Article\Application\Command\Task\ArticlePublishTaskCommandTest
 */
final class ArticlePublishTaskCommandDataProvider
{
    /**
     * Provides valid scenarios for ArticlePublishTaskCommand construction.
     *
     * @return iterable<string, array{processUuid: string, uuid: string}>
     */
    public static function provideValidConstructionData(): iterable
    {
        yield 'publish draft article' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440003',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430cb',
        ];

        yield 'publish reviewed article' => [
            'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90aea',
            'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a14',
        ];

        yield 'publish with specific workflow' => [
            'processUuid' => '3d6f2a88-5b1c-4e9d-8f3a-7c2b9e4a1f70',
            'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d61',
        ];

        yield 'publish scheduled article' => [
            'processUuid' => '9f8e7d6c-5b4a-3c2d-1e0f-9a8b7c6d5e42',
            'uuid' => 'c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e82',
        ];

        yield 'publish urgent article' => [
            'processUuid' => 'd4e5f6a7-b8c9-0d1e-2f3a-4b5c6d7e8f9c',
            'uuid' => 'e5f6a7b8-c9d0-1e2f-3a4b-5c6d7e8f9a0d',
        ];
    }

    /**
     * Provides invalid scenarios for ArticlePublishTaskCommand construction.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, expectedException: class-string<\Throwable>}>
     */
    public static function provideInvalidConstructionData(): iterable
    {
        yield 'malformed process uuid' => [
            'processUuid' => 'proc-publish-123',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430cb',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'malformed article uuid' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440003',
            'uuid' => 'article-to-publish',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'empty process uuid' => [
            'processUuid' => '',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430cb',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'empty article uuid' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440003',
            'uuid' => '',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'invalid uuid with special chars' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440003',
            'uuid' => 'invalid@uuid#format',
            'expectedException' => \InvalidArgumentException::class,
        ];
    }
}
