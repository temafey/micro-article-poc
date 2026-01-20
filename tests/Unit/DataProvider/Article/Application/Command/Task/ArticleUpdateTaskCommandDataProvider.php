<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\Command\Task;

/**
 * DataProvider for ArticleUpdateTaskCommand tests.
 *
 * Usage in test class:
 * #[DataProviderExternal(ArticleUpdateTaskCommandDataProvider::class, 'provideValidConstructionData')]
 *
 * @see Tests\Unit\Article\Application\Command\Task\ArticleUpdateTaskCommandTest
 */
final class ArticleUpdateTaskCommandDataProvider
{
    /**
     * Provides valid scenarios for ArticleUpdateTaskCommand construction.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, title: string, description: string, shortDescription: string, status: string}>
     */
    public static function provideValidConstructionData(): iterable
    {
        yield 'update article title and description' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440001',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c9',
            'title' => 'Updated Article Title',
            'description' => 'This is an updated description that contains at least fifty characters for validation purposes and meets all requirements.',
            'shortDescription' => 'Updated summary',
            'status' => 'draft',
        ];

        yield 'update to published status' => [
            'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae8',
            'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a12',
            'title' => 'Article Ready for Publication',
            'description' => 'This article article has been reviewed and is ready for publication with all necessary content and validation requirements met.',
            'shortDescription' => 'Ready for publication',
            'status' => 'published',
        ];

        yield 'update with unicode content' => [
            'processUuid' => '3d6f2a88-5b1c-4e9d-8f3a-7c2b9e4a1f6e',
            'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d5f',
            'title' => 'Обновленные новости',
            'description' => 'Обновленное описание новости на русском языке с необходимыми пятьюдесятью символами для валидации системы управления контентом.',
            'shortDescription' => 'Обновленное краткое описание',
            'status' => 'draft',
        ];

        yield 'update to archived status' => [
            'processUuid' => '9f8e7d6c-5b4a-3c2d-1e0f-9a8b7c6d5e40',
            'uuid' => 'c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e80',
            'title' => 'Archived Article Article',
            'description' => 'This article article is being archived after its publication period has ended and is no longer actively displayed to users.',
            'shortDescription' => 'Archived article',
            'status' => 'archived',
        ];

        yield 'update all fields simultaneously' => [
            'processUuid' => 'd4e5f6a7-b8c9-0d1e-2f3a-4b5c6d7e8f9b',
            'uuid' => 'e5f6a7b8-c9d0-1e2f-3a4b-5c6d7e8f9a0c',
            'title' => 'Completely Updated Article: All Fields Changed',
            'description' => 'This comprehensive update modifies all fields of the article article including title, descriptions, and status to demonstrate full update capability.',
            'shortDescription' => 'Full update performed',
            'status' => 'published',
        ];
    }

    /**
     * Provides invalid scenarios for ArticleUpdateTaskCommand construction.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, title: string, description: string, shortDescription: string, status: string, expectedException: class-string<\Throwable>}>
     */
    public static function provideInvalidConstructionData(): iterable
    {
        yield 'empty title' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440001',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c9',
            'title' => '',
            'description' => 'This is a valid test description that contains at least fifty characters for validation purposes.',
            'shortDescription' => 'Valid short desc',
            'status' => 'draft',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'description below minimum length' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440001',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c9',
            'title' => 'Valid Title',
            'description' => 'Short',
            'shortDescription' => 'Valid short desc',
            'status' => 'draft',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'invalid status value' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440001',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c9',
            'title' => 'Valid Title',
            'description' => 'This is a valid test description that contains at least fifty characters for validation purposes.',
            'shortDescription' => 'Valid short desc',
            'status' => 'pending',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'malformed process uuid' => [
            'processUuid' => 'proc-123',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c9',
            'title' => 'Valid Title',
            'description' => 'This is a valid test description that contains at least fifty characters for validation purposes.',
            'shortDescription' => 'Valid short desc',
            'status' => 'draft',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'malformed article uuid' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440001',
            'uuid' => 'article-456',
            'title' => 'Valid Title',
            'description' => 'This is a valid test description that contains at least fifty characters for validation purposes.',
            'shortDescription' => 'Valid short desc',
            'status' => 'draft',
            'expectedException' => \InvalidArgumentException::class,
        ];
    }
}
