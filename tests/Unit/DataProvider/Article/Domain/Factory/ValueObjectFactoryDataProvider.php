<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\Factory;

/**
 * DataProvider for ValueObjectFactory tests.
 *
 * Note: Description requires between 50 and 50,000 characters.
 *
 * @see \Tests\Unit\Article\Domain\Factory\ValueObjectFactoryTest
 */
final class ValueObjectFactoryDataProvider
{
    /**
     * Provides valid title values.
     *
     * @return iterable<string, array{title: string}>
     */
    public static function provideTitleValues(): iterable
    {
        yield 'simple title' => [
            'title' => 'Test Article Title',
        ];

        yield 'title with special characters' => [
            'title' => 'Breaking Article: Important Update!',
        ];

        yield 'unicode title' => [
            'title' => 'Novosti dnya: Vazhnoe soobshchenie',
        ];
    }

    /**
     * Provides valid short description values.
     *
     * @return iterable<string, array{shortDescription: string}>
     */
    public static function provideShortDescriptionValues(): iterable
    {
        yield 'simple short description' => [
            'shortDescription' => 'This is a short description.',
        ];

        yield 'longer short description' => [
            'shortDescription' => 'This is a slightly longer short description for testing purposes.',
        ];
    }

    /**
     * Provides valid description values (minimum 50 characters).
     *
     * @return iterable<string, array{description: string}>
     */
    public static function provideDescriptionValues(): iterable
    {
        yield 'minimum valid description' => [
            'description' => 'This is a complete article article description that provides comprehensive details about the topic at hand.',
        ];

        yield 'longer description' => [
            'description' => 'This is a much longer description that contains multiple sentences. It provides even more comprehensive details about the topic. The description continues with additional context and information for the reader.',
        ];
    }

    /**
     * Provides valid slug values.
     *
     * @return iterable<string, array{slug: string}>
     */
    public static function provideSlugValues(): iterable
    {
        yield 'simple slug' => [
            'slug' => 'test-article-title',
        ];

        yield 'slug with numbers' => [
            'slug' => 'article-article-2024',
        ];
    }

    /**
     * Provides valid event id values.
     *
     * @return iterable<string, array{eventId: int}>
     */
    public static function provideEventIdValues(): iterable
    {
        yield 'small event id' => [
            'eventId' => 1,
        ];

        yield 'standard event id' => [
            'eventId' => 12345,
        ];

        yield 'large event id' => [
            'eventId' => 999999,
        ];
    }

    /**
     * Provides valid status values.
     *
     * @return iterable<string, array{status: string}>
     */
    public static function provideStatusValues(): iterable
    {
        yield 'draft status' => [
            'status' => 'draft',
        ];

        yield 'published status' => [
            'status' => 'published',
        ];

        yield 'archived status' => [
            'status' => 'archived',
        ];
    }

    /**
     * Provides valid article data.
     *
     * @return iterable<string, array{articleData: array}>
     */
    public static function provideArticleData(): iterable
    {
        yield 'minimal article data' => [
            'articleData' => [
                'title' => 'Test Title',
            ],
        ];

        yield 'complete article data' => [
            'articleData' => [
                'title' => 'Complete Article',
                'short_description' => 'Short description.',
                'description' => 'Full description that meets the minimum requirements of fifty characters for validation testing purposes.',
                'slug' => 'complete-article',
                'status' => 'draft',
            ],
        ];
    }
}
