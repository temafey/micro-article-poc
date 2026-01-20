<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\Service;

/**
 * DataProvider for ArticleSlugGeneratorService tests.
 *
 * @see \Tests\Unit\Article\Domain\Service\ArticleSlugGeneratorServiceTest
 */
final class ArticleSlugGeneratorServiceDataProvider
{
    /**
     * Provides valid titles for slug generation.
     *
     * @return iterable<string, array{title: string, expectedSlug: string}>
     */
    public static function provideValidTitlesForSlugGeneration(): iterable
    {
        yield 'simple title' => [
            'title' => 'Hello World',
            'expectedSlug' => 'hello-world',
        ];

        yield 'title with special characters' => [
            'title' => 'Breaking Article: Important Update!',
            'expectedSlug' => 'breaking-article-important-update',
        ];

        yield 'title with numbers' => [
            'title' => 'Article Article 2024',
            'expectedSlug' => 'article-article-2024',
        ];

        yield 'title with multiple spaces' => [
            'title' => 'Title   With   Multiple   Spaces',
            'expectedSlug' => 'title-with-multiple-spaces',
        ];
    }

    /**
     * Provides invalid titles for slug generation.
     *
     * @return iterable<string, array{title: string}>
     */
    public static function provideInvalidTitlesForSlugGeneration(): iterable
    {
        yield 'empty title' => [
            'title' => '',
        ];

        yield 'whitespace only' => [
            'title' => '   ',
        ];
    }

    /**
     * Provides valid slugs for format validation.
     *
     * @return iterable<string, array{slug: string, expected: bool}>
     */
    public static function provideSlugFormatValidation(): iterable
    {
        yield 'valid simple slug' => [
            'slug' => 'hello-world',
            'expected' => true,
        ];

        yield 'valid slug with numbers' => [
            'slug' => 'article-article-2024',
            'expected' => true,
        ];

        yield 'valid single word slug' => [
            'slug' => 'hello',
            'expected' => true,
        ];

        yield 'valid slug with counter suffix' => [
            'slug' => 'hello-world-1',
            'expected' => true,
        ];

        yield 'invalid slug with uppercase' => [
            'slug' => 'Hello-World',
            'expected' => false,
        ];

        yield 'invalid slug with leading hyphen' => [
            'slug' => '-hello-world',
            'expected' => false,
        ];

        yield 'invalid slug with trailing hyphen' => [
            'slug' => 'hello-world-',
            'expected' => false,
        ];

        yield 'invalid slug with special characters' => [
            'slug' => 'hello_world',
            'expected' => false,
        ];

        yield 'invalid empty slug' => [
            'slug' => '',
            'expected' => false,
        ];

        yield 'invalid slug with consecutive hyphens' => [
            'slug' => 'hello--world',
            'expected' => false,
        ];
    }

    /**
     * Provides scenarios for uniqueness checking.
     *
     * @return iterable<string, array{title: string, existingSlug: string|null, excludeUuid: string|null}>
     */
    public static function provideUniquenessScenarios(): iterable
    {
        yield 'new slug without existing' => [
            'title' => 'New Article',
            'existingSlug' => null,
            'excludeUuid' => null,
        ];

        yield 'update with existing slug matching title' => [
            'title' => 'Existing Article',
            'existingSlug' => 'existing-article',
            'excludeUuid' => '550e8400-e29b-41d4-a716-446655440000',
        ];

        yield 'update with different slug' => [
            'title' => 'Updated Title',
            'existingSlug' => 'old-slug',
            'excludeUuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
        ];
    }
}
