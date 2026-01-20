<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\ValueObject;

use MicroModule\ValueObject\Exception\InvalidNativeArgumentException;

/**
 * DataProvider for Body ValueObject tests.
 *
 * @see \Tests\Unit\Article\Domain\ValueObject\BodyTest
 */
final class BodyDataProvider
{
    /**
     * Provides valid body values for construction tests.
     *
     * @return iterable<string, array{value: string, expected: string}>
     */
    public static function provideValidBodies(): iterable
    {
        yield 'minimum length 10 chars' => [
            'value' => str_repeat('a', 10),
            'expected' => str_repeat('a', 10),
        ];

        yield 'above minimum 11 chars' => [
            'value' => str_repeat('b', 11),
            'expected' => str_repeat('b', 11),
        ];

        yield 'medium length 1000 chars' => [
            'value' => str_repeat('c', 1000),
            'expected' => str_repeat('c', 1000),
        ];

        yield 'large length 10000 chars' => [
            'value' => str_repeat('d', 10000),
            'expected' => str_repeat('d', 10000),
        ];

        yield 'maximum length 65535 chars' => [
            'value' => str_repeat('e', 65535),
            'expected' => str_repeat('e', 65535),
        ];

        yield 'realistic body content' => [
            'value' => 'This is the main body of the article article with detailed content.',
            'expected' => 'This is the main body of the article article with detailed content.',
        ];

        yield 'with html content' => [
            'value' => '<div><h1>Title</h1><p>Paragraph content here.</p></div>',
            'expected' => '<div><h1>Title</h1><p>Paragraph content here.</p></div>',
        ];

        yield 'with unicode characters' => [
            'value' => 'Содержание новостной статьи на русском языке с достаточным количеством символов.',
            'expected' => 'Содержание новостной статьи на русском языке с достаточным количеством символов.',
        ];

        yield 'with markdown' => [
            'value' => '# Heading\n\n**Bold** and *italic* text with [link](url)',
            'expected' => '# Heading\n\n**Bold** and *italic* text with [link](url)',
        ];
    }

    /**
     * Provides invalid body values for exception tests.
     *
     * @return iterable<string, array{value: string, expectedException: class-string<\Throwable>}>
     */
    public static function provideInvalidBodies(): iterable
    {
        yield 'empty string' => [
            'value' => '',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'too short 5 chars' => [
            'value' => 'Short',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'too short 9 chars' => [
            'value' => str_repeat('a', 9),
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'exceeds max 65536 chars' => [
            'value' => str_repeat('a', 65536),
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'whitespace only' => [
            'value' => '   ',
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
        yield 'below minimum 9 chars' => [
            'length' => 9,
            'shouldPass' => false,
        ];

        yield 'at minimum 10 chars' => [
            'length' => 10,
            'shouldPass' => true,
        ];

        yield 'at maximum 65535 chars' => [
            'length' => 65535,
            'shouldPass' => true,
        ];

        yield 'above maximum 65536 chars' => [
            'length' => 65536,
            'shouldPass' => false,
        ];
    }
}
