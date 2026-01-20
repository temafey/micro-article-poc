<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\ValueObject;

use MicroModule\ValueObject\Exception\InvalidNativeArgumentException;

/**
 * DataProvider for Description ValueObject tests.
 *
 * @see \Tests\Unit\Article\Domain\ValueObject\DescriptionTest
 */
final class DescriptionDataProvider
{
    /**
     * Provides valid description values for construction tests.
     *
     * @return iterable<string, array{value: string, expected: string}>
     */
    public static function provideValidDescriptions(): iterable
    {
        yield 'minimum length 50 chars' => [
            'value' => str_repeat('a', 50),
            'expected' => str_repeat('a', 50),
        ];

        yield 'above minimum 51 chars' => [
            'value' => str_repeat('b', 51),
            'expected' => str_repeat('b', 51),
        ];

        yield 'medium length 500 chars' => [
            'value' => str_repeat('c', 500),
            'expected' => str_repeat('c', 500),
        ];

        yield 'large length 5000 chars' => [
            'value' => str_repeat('d', 5000),
            'expected' => str_repeat('d', 5000),
        ];

        yield 'maximum length 50000 chars' => [
            'value' => str_repeat('e', 50000),
            'expected' => str_repeat('e', 50000),
        ];

        yield 'realistic description' => [
            'value' => 'This is a complete article article description that provides comprehensive details about the topic at hand.',
            'expected' => 'This is a complete article article description that provides comprehensive details about the topic at hand.',
        ];

        yield 'with unicode characters' => [
            'value' => 'Это полное описание новостной статьи на русском языке с достаточным количеством символов для прохождения валидации.',
            'expected' => 'Это полное описание новостной статьи на русском языке с достаточным количеством символов для прохождения валидации.',
        ];

        yield 'with html content' => [
            'value' => '<p>This is a paragraph with <strong>bold</strong> and <em>italic</em> text that meets the minimum length requirement.</p>',
            'expected' => '<p>This is a paragraph with <strong>bold</strong> and <em>italic</em> text that meets the minimum length requirement.</p>',
        ];

        yield 'with newlines' => [
            'value' => "First paragraph of the description.\n\nSecond paragraph with more content to meet the minimum requirement.",
            'expected' => "First paragraph of the description.\n\nSecond paragraph with more content to meet the minimum requirement.",
        ];
    }

    /**
     * Provides invalid description values for exception tests.
     *
     * @return iterable<string, array{value: string, expectedException: class-string<\Throwable>}>
     */
    public static function provideInvalidDescriptions(): iterable
    {
        yield 'empty string' => [
            'value' => '',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'too short 10 chars' => [
            'value' => str_repeat('a', 10),
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'too short 49 chars' => [
            'value' => str_repeat('a', 49),
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'exceeds max 50001 chars' => [
            'value' => str_repeat('a', 50001),
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'single word' => [
            'value' => 'Description',
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
        yield 'below minimum 49 chars' => [
            'length' => 49,
            'shouldPass' => false,
        ];

        yield 'at minimum 50 chars' => [
            'length' => 50,
            'shouldPass' => true,
        ];

        yield 'above minimum 51 chars' => [
            'length' => 51,
            'shouldPass' => true,
        ];

        yield 'at maximum 50000 chars' => [
            'length' => 50000,
            'shouldPass' => true,
        ];

        yield 'above maximum 50001 chars' => [
            'length' => 50001,
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
        $validDescription = 'This is a valid description with at least fifty characters for testing purposes.';

        yield 'same value returns true' => [
            'value' => $validDescription,
            'otherValue' => $validDescription,
            'expected' => true,
        ];

        yield 'different value returns false' => [
            'value' => $validDescription,
            'otherValue' => 'Another valid description with at least fifty characters for testing purposes here.',
            'expected' => false,
        ];
    }
}
