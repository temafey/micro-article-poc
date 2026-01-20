<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\ValueObject;

use MicroModule\ValueObject\Exception\InvalidNativeArgumentException;

/**
 * DataProvider for Title ValueObject tests.
 *
 * @see \Tests\Unit\Article\Domain\ValueObject\TitleTest
 */
final class TitleDataProvider
{
    /**
     * Provides valid title values for construction tests.
     *
     * @return iterable<string, array{value: string, expected: string}>
     */
    public static function provideValidTitles(): iterable
    {
        yield 'simple title' => [
            'value' => 'Breaking Article',
            'expected' => 'Breaking Article',
        ];

        yield 'minimum length 3 chars' => [
            'value' => 'Abc',
            'expected' => 'Abc',
        ];

        yield 'maximum length 255 chars' => [
            'value' => str_repeat('a', 255),
            'expected' => str_repeat('a', 255),
        ];

        yield 'with numbers' => [
            'value' => 'Top 10 Stories 2024',
            'expected' => 'Top 10 Stories 2024',
        ];

        yield 'with unicode characters' => [
            'value' => 'Новости дня',
            'expected' => 'Новости дня',
        ];

        yield 'with special characters' => [
            'value' => 'Article & Updates: What\'s Next?',
            'expected' => 'Article & Updates: What\'s Next?',
        ];

        yield 'with punctuation' => [
            'value' => 'Breaking: Major Event!',
            'expected' => 'Breaking: Major Event!',
        ];

        yield 'with html entities' => [
            'value' => 'Test <script>alert("xss")</script>',
            'expected' => 'Test <script>alert("xss")</script>',
        ];

        yield 'trimmed whitespace at start' => [
            'value' => '  Title with spaces  ',
            'expected' => '  Title with spaces  ',
        ];
    }

    /**
     * Provides invalid title values for exception tests.
     *
     * @return iterable<string, array{value: string, expectedException: class-string<\Throwable>}>
     */
    public static function provideInvalidTitles(): iterable
    {
        yield 'empty string' => [
            'value' => '',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'too short 1 char' => [
            'value' => 'A',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'too short 2 chars' => [
            'value' => 'Ab',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'exceeds max 256 chars' => [
            'value' => str_repeat('A', 256),
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'exceeds max 300 chars' => [
            'value' => str_repeat('B', 300),
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

        yield 'above minimum 4 chars' => [
            'length' => 4,
            'shouldPass' => true,
        ];

        yield 'below maximum 254 chars' => [
            'length' => 254,
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
     * Provides same value comparison scenarios.
     *
     * @return iterable<string, array{value: string, otherValue: string, expected: bool}>
     */
    public static function provideSameValueAsScenarios(): iterable
    {
        yield 'same value returns true' => [
            'value' => 'Same Title',
            'otherValue' => 'Same Title',
            'expected' => true,
        ];

        yield 'different value returns false' => [
            'value' => 'Title One',
            'otherValue' => 'Title Two',
            'expected' => false,
        ];

        yield 'case sensitive comparison' => [
            'value' => 'Title',
            'otherValue' => 'title',
            'expected' => false,
        ];

        yield 'whitespace difference' => [
            'value' => 'Title',
            'otherValue' => 'Title ',
            'expected' => false,
        ];
    }

    /**
     * Provides toNative conversion test cases.
     *
     * @return iterable<string, array{value: string}>
     */
    public static function provideToNativeScenarios(): iterable
    {
        yield 'simple string' => [
            'value' => 'Simple Title',
        ];

        yield 'unicode string' => [
            'value' => 'Заголовок новости',
        ];

        yield 'special characters' => [
            'value' => 'Title & Subtitle: Test!',
        ];
    }
}
