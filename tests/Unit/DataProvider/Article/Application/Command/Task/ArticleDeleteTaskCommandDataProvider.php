<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\Command\Task;

/**
 * DataProvider for ArticleDeleteTaskCommand tests.
 *
 * Usage in test class:
 * #[DataProviderExternal(ArticleDeleteTaskCommandDataProvider::class, 'provideValidConstructionData')]
 *
 * @see Tests\Unit\Article\Application\Command\Task\ArticleDeleteTaskCommandTest
 */
final class ArticleDeleteTaskCommandDataProvider
{
    /**
     * Provides valid scenarios for ArticleDeleteTaskCommand construction.
     *
     * @return iterable<string, array{processUuid: string, uuid: string}>
     */
    public static function provideValidConstructionData(): iterable
    {
        yield 'delete draft article' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440002',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430ca',
        ];

        yield 'delete published article' => [
            'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae9',
            'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a13',
        ];

        yield 'delete archived article' => [
            'processUuid' => '3d6f2a88-5b1c-4e9d-8f3a-7c2b9e4a1f6f',
            'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d60',
        ];

        yield 'delete with specific process uuid' => [
            'processUuid' => '9f8e7d6c-5b4a-3c2d-1e0f-9a8b7c6d5e41',
            'uuid' => 'c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e81',
        ];

        yield 'delete with minimum valid uuid' => [
            'processUuid' => '00000000-0000-0000-0000-000000000001',
            'uuid' => '00000000-0000-0000-0000-000000000002',
        ];
    }

    /**
     * Provides invalid scenarios for ArticleDeleteTaskCommand construction.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, expectedException: class-string<\Throwable>}>
     */
    public static function provideInvalidConstructionData(): iterable
    {
        yield 'invalid process uuid format' => [
            'processUuid' => 'invalid-process-uuid',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430ca',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'invalid article uuid format' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440002',
            'uuid' => 'not-a-uuid',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'empty process uuid' => [
            'processUuid' => '',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430ca',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'empty article uuid' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440002',
            'uuid' => '',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'numeric string as process uuid' => [
            'processUuid' => '12345',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430ca',
            'expectedException' => \InvalidArgumentException::class,
        ];
    }
}
