<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\ValueObject;

use MicroModule\ValueObject\Exception\InvalidNativeArgumentException;

/**
 * DataProvider for ShortDescription ValueObject tests.
 *
 * @see \Tests\Unit\Article\Domain\ValueObject\ShortDescriptionTest
 */
final class ShortDescriptionDataProvider
{
    /**
     * Provides valid short description values for construction tests.
     *
     * @return iterable<string, array{value: string, expected: string}>
     */
    public static function provideValidShortDescriptions(): iterable
    {
        yield 'minimum length 10 chars' => [
            'value' => str_repeat('a', 10),
            'expected' => str_repeat('a', 10),
        ];

        yield 'above minimum 11 chars' => [
            'value' => str_repeat('b', 11),
            'expected' => str_repeat('b', 11),
        ];

        yield 'medium length 250 chars' => [
            'value' => str_repeat('c', 250),
            'expected' => str_repeat('c', 250),
        ];

        yield 'maximum length 500 chars' => [
            'value' => str_repeat('d', 500),
            'expected' => str_repeat('d', 500),
        ];

        yield 'realistic short description' => [
            'value' => 'A brief summary of the article article covering the main points.',
            'expected' => 'A brief summary of the article article covering the main points.',
        ];

        yield 'with unicode characters' => [
            'value' => 'Краткое описание новостной статьи на русском языке.',
            'expected' => 'Краткое описание новостной статьи на русском языке.',
        ];

        yield 'with special characters' => [
            'value' => 'Breaking: Major event & updates! Check it out.',
            'expected' => 'Breaking: Major event & updates! Check it out.',
        ];
    }

    /**
     * Provides invalid short description values for exception tests.
     *
     * @return iterable<string, array{value: string, expectedException: class-string<\Throwable>}>
     */
    public static function provideInvalidShortDescriptions(): iterable
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

        yield 'exceeds max 501 chars' => [
            'value' => str_repeat('a', 501),
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'exceeds max 600 chars' => [
            'value' => str_repeat('a', 600),
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

        yield 'at maximum 500 chars' => [
            'length' => 500,
            'shouldPass' => true,
        ];

        yield 'above maximum 501 chars' => [
            'length' => 501,
            'shouldPass' => false,
        ];
    }
}
