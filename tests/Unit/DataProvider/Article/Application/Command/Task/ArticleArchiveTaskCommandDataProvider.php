<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\Command\Task;

/**
 * DataProvider for ArticleArchiveTaskCommand tests.
 *
 * Usage in test class:
 * #[DataProviderExternal(ArticleArchiveTaskCommandDataProvider::class, 'provideValidConstructionData')]
 *
 * @see Tests\Unit\Article\Application\Command\Task\ArticleArchiveTaskCommandTest
 */
final class ArticleArchiveTaskCommandDataProvider
{
    /**
     * Provides valid scenarios for ArticleArchiveTaskCommand construction.
     *
     * @return iterable<string, array{processUuid: string, uuid: string}>
     */
    public static function provideValidConstructionData(): iterable
    {
        yield 'archive published article' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440005',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430cd',
        ];

        yield 'archive after expiration' => [
            'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90aec',
            'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a16',
        ];

        yield 'archive old content' => [
            'processUuid' => '3d6f2a88-5b1c-4e9d-8f3a-7c2b9e4a1f72',
            'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d63',
        ];

        yield 'archive seasonal article' => [
            'processUuid' => '9f8e7d6c-5b4a-3c2d-1e0f-9a8b7c6d5e44',
            'uuid' => 'c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e84',
        ];

        yield 'archive for historical record' => [
            'processUuid' => 'd4e5f6a7-b8c9-0d1e-2f3a-4b5c6d7e8f9e',
            'uuid' => 'e5f6a7b8-c9d0-1e2f-3a4b-5c6d7e8f9a0f',
        ];
    }

    /**
     * Provides invalid scenarios for ArticleArchiveTaskCommand construction.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, expectedException: class-string<\Throwable>}>
     */
    public static function provideInvalidConstructionData(): iterable
    {
        yield 'malformed process uuid' => [
            'processUuid' => 'archive-process-uuid',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430cd',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'malformed article uuid' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440005',
            'uuid' => 'archive-article-id',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'empty process uuid' => [
            'processUuid' => '',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430cd',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'empty article uuid' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440005',
            'uuid' => '',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'uuid with spaces' => [
            'processUuid' => '550e8400 e29b 41d4 a716 446655440005',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430cd',
            'expectedException' => \InvalidArgumentException::class,
        ];
    }
}
