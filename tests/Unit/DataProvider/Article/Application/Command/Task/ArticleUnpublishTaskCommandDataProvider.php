<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\Command\Task;

/**
 * DataProvider for ArticleUnpublishTaskCommand tests.
 *
 * Usage in test class:
 * #[DataProviderExternal(ArticleUnpublishTaskCommandDataProvider::class, 'provideValidConstructionData')]
 *
 * @see Tests\Unit\Article\Application\Command\Task\ArticleUnpublishTaskCommandTest
 */
final class ArticleUnpublishTaskCommandDataProvider
{
    /**
     * Provides valid scenarios for ArticleUnpublishTaskCommand construction.
     *
     * @return iterable<string, array{processUuid: string, uuid: string}>
     */
    public static function provideValidConstructionData(): iterable
    {
        yield 'unpublish published article' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440004',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430cc',
        ];

        yield 'unpublish for corrections' => [
            'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90aeb',
            'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a15',
        ];

        yield 'unpublish temporarily' => [
            'processUuid' => '3d6f2a88-5b1c-4e9d-8f3a-7c2b9e4a1f71',
            'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d62',
        ];

        yield 'unpublish due to policy violation' => [
            'processUuid' => '9f8e7d6c-5b4a-3c2d-1e0f-9a8b7c6d5e43',
            'uuid' => 'c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e83',
        ];

        yield 'unpublish outdated content' => [
            'processUuid' => 'd4e5f6a7-b8c9-0d1e-2f3a-4b5c6d7e8f9d',
            'uuid' => 'e5f6a7b8-c9d0-1e2f-3a4b-5c6d7e8f9a0e',
        ];
    }

    /**
     * Provides invalid scenarios for ArticleUnpublishTaskCommand construction.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, expectedException: class-string<\Throwable>}>
     */
    public static function provideInvalidConstructionData(): iterable
    {
        yield 'invalid process uuid format' => [
            'processUuid' => 'unpublish-proc-456',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430cc',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'invalid article uuid format' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440004',
            'uuid' => 'article-unpublish-789',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'empty process uuid' => [
            'processUuid' => '',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430cc',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'empty article uuid' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440004',
            'uuid' => '',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'uuid with invalid separators' => [
            'processUuid' => '550e8400_e29b_41d4_a716_446655440004',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430cc',
            'expectedException' => \InvalidArgumentException::class,
        ];
    }
}
