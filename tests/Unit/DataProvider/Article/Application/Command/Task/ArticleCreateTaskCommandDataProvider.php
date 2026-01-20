<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Application\Command\Task;

/**
 * DataProvider for ArticleCreateTaskCommand tests.
 *
 * Usage in test class:
 * #[DataProviderExternal(ArticleCreateTaskCommandDataProvider::class, 'provideValidConstructionData')]
 *
 * @see Tests\Unit\Article\Application\Command\Task\ArticleCreateTaskCommandTest
 */
final class ArticleCreateTaskCommandDataProvider
{
    /**
     * Provides valid scenarios for ArticleCreateTaskCommand construction.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, title: string, description: string, shortDescription: string, status: string}>
     */
    public static function provideValidConstructionData(): iterable
    {
        yield 'standard article creation' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'title' => 'Breaking Article Title',
            'description' => 'This is a valid test description that contains at least fifty characters for validation purposes and meets all requirements.',
            'shortDescription' => 'Short desc',
            'status' => 'draft',
        ];

        yield 'article with maximum length fields' => [
            'processUuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'uuid' => 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380a11',
            'title' => str_repeat('A', 255),
            'description' => str_repeat(
                'This is a valid description segment that will be repeated to reach the minimum length requirement. ',
                50
            ),
            'shortDescription' => str_repeat('Short', 100),
            'status' => 'draft',
        ];

        yield 'article with unicode characters' => [
            'processUuid' => '3d6f2a88-5b1c-4e9d-8f3a-7c2b9e4a1f6d',
            'uuid' => 'b1c2d3e4-f5a6-4b7c-8d9e-0f1a2b3c4d5e',
            'title' => 'Новости дня: важное событие',
            'description' => 'Это действительное описание на русском языке с необходимыми пятьюдесятью символами и более для проверки валидации контента.',
            'shortDescription' => 'Краткое описание новости',
            'status' => 'draft',
        ];

        yield 'article with published status' => [
            'processUuid' => '9f8e7d6c-5b4a-3c2d-1e0f-9a8b7c6d5e4f',
            'uuid' => 'c3d4e5f6-a7b8-9c0d-1e2f-3a4b5c6d7e8f',
            'title' => 'Published Article Article',
            'description' => 'This is a published article article with a comprehensive description that exceeds the minimum fifty character requirement for validation.',
            'shortDescription' => 'Published article summary',
            'status' => 'published',
        ];

        yield 'article with special characters in title' => [
            'processUuid' => 'd4e5f6a7-b8c9-0d1e-2f3a-4b5c6d7e8f9a',
            'uuid' => 'e5f6a7b8-c9d0-1e2f-3a4b-5c6d7e8f9a0b',
            'title' => 'Article & Updates: What\'s Next? (2024)',
            'description' => 'This description contains special characters and demonstrates proper handling of various symbols in article content validation tests.',
            'shortDescription' => 'Updates & Article',
            'status' => 'draft',
        ];
    }

    /**
     * Provides invalid scenarios for ArticleCreateTaskCommand construction.
     *
     * @return iterable<string, array{processUuid: string, uuid: string, title: string, description: string, shortDescription: string, status: string, expectedException: class-string<\Throwable>}>
     */
    public static function provideInvalidConstructionData(): iterable
    {
        yield 'empty title' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'title' => '',
            'description' => 'This is a valid test description that contains at least fifty characters for validation purposes.',
            'shortDescription' => 'Short desc',
            'status' => 'draft',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'description too short' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'title' => 'Valid Title',
            'description' => 'Too short',
            'shortDescription' => 'Short desc',
            'status' => 'draft',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'invalid status' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'title' => 'Valid Title',
            'description' => 'This is a valid test description that contains at least fifty characters for validation purposes.',
            'shortDescription' => 'Short desc',
            'status' => 'invalid_status',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'invalid process uuid format' => [
            'processUuid' => 'invalid-uuid',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'title' => 'Valid Title',
            'description' => 'This is a valid test description that contains at least fifty characters for validation purposes.',
            'shortDescription' => 'Short desc',
            'status' => 'draft',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'invalid article uuid format' => [
            'processUuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => 'not-a-valid-uuid',
            'title' => 'Valid Title',
            'description' => 'This is a valid test description that contains at least fifty characters for validation purposes.',
            'shortDescription' => 'Short desc',
            'status' => 'draft',
            'expectedException' => \InvalidArgumentException::class,
        ];
    }
}
