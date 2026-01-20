<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\ValueObject;

use MicroModule\ValueObject\Exception\InvalidNativeArgumentException;

/**
 * DataProvider for Slug ValueObject tests.
 *
 * @see \Tests\Unit\Article\Domain\ValueObject\SlugTest
 */
final class SlugDataProvider
{
    /**
     * Provides valid slug values for construction tests.
     *
     * @return iterable<string, array{value: string, expected: string}>
     */
    public static function provideValidSlugs(): iterable
    {
        yield 'simple lowercase' => [
            'value' => 'breaking-article',
            'expected' => 'breaking-article',
        ];

        yield 'minimum length 3 chars' => [
            'value' => 'abc',
            'expected' => 'abc',
        ];

        yield 'maximum length 255 chars' => [
            'value' => str_repeat('a', 255),
            'expected' => str_repeat('a', 255),
        ];

        yield 'with numbers' => [
            'value' => 'top-10-stories-2024',
            'expected' => 'top-10-stories-2024',
        ];

        yield 'alphanumeric only' => [
            'value' => 'article123',
            'expected' => 'article123',
        ];

        yield 'single word' => [
            'value' => 'article',
            'expected' => 'article',
        ];

        yield 'multiple hyphens' => [
            'value' => 'this-is-a-long-slug-example',
            'expected' => 'this-is-a-long-slug-example',
        ];

        yield 'numbers at start' => [
            'value' => '123-article',
            'expected' => '123-article',
        ];

        yield 'numbers at end' => [
            'value' => 'article-123',
            'expected' => 'article-123',
        ];
    }

    /**
     * Provides invalid slug values for exception tests.
     *
     * @return iterable<string, array{value: string, expectedException: class-string<\Throwable>}>
     */
    public static function provideInvalidSlugs(): iterable
    {
        yield 'empty string' => [
            'value' => '',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'too short 1 char' => [
            'value' => 'a',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'too short 2 chars' => [
            'value' => 'ab',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'exceeds max 256 chars' => [
            'value' => str_repeat('a', 256),
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'uppercase letters' => [
            'value' => 'Breaking-Article',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'spaces in slug' => [
            'value' => 'breaking article',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'special characters underscore' => [
            'value' => 'breaking_article',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'starts with hyphen' => [
            'value' => '-breaking-article',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'ends with hyphen' => [
            'value' => 'breaking-article-',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'double hyphens' => [
            'value' => 'breaking--article',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'unicode characters' => [
            'value' => 'новости',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'special characters ampersand' => [
            'value' => 'article&updates',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'dot character' => [
            'value' => 'article.updates',
            'expectedException' => InvalidNativeArgumentException::class,
        ];
    }

    /**
     * Provides boundary length test cases.
     *
     * @return iterable<string, array{length: int, shouldPass: bool}>
     */
    public static function provideBoundaryLengths(): iterable
    {
        yield 'below minimum 2 chars' => [
            'length' => 2,
            'shouldPass' => false,
        ];

        yield 'at minimum 3 chars' => [
            'length' => 3,
            'shouldPass' => true,
        ];

        yield 'at maximum 255 chars' => [
            'length' => 255,
            'shouldPass' => true,
        ];

        yield 'above maximum 256 chars' => [
            'length' => 256,
            'shouldPass' => false,
        ];
    }

    /**
     * Provides URL validation scenarios.
     *
     * @return iterable<string, array{value: string, isUrlSafe: bool}>
     */
    public static function provideUrlSafeScenarios(): iterable
    {
        yield 'simple slug is url safe' => [
            'value' => 'simple-slug',
            'isUrlSafe' => true,
        ];

        yield 'numeric slug is url safe' => [
            'value' => '123-456',
            'isUrlSafe' => true,
        ];

        yield 'mixed alphanumeric is url safe' => [
            'value' => 'article-2024-update',
            'isUrlSafe' => true,
        ];
    }
}
