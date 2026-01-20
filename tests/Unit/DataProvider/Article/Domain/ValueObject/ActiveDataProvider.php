<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\ValueObject;

/**
 * DataProvider for Active ValueObject tests.
 *
 * @see \Tests\Unit\Article\Domain\ValueObject\ActiveTest
 */
final class ActiveDataProvider
{
    /**
     * Provides valid active state values.
     *
     * @return iterable<string, array{value: bool, expected: bool}>
     */
    public static function provideValidActiveStates(): iterable
    {
        yield 'active true' => [
            'value' => true,
            'expected' => true,
        ];

        yield 'active false' => [
            'value' => false,
            'expected' => false,
        ];
    }

    /**
     * Provides isActive scenarios.
     *
     * @return iterable<string, array{value: bool, expectedIsActive: bool}>
     */
    public static function provideIsActiveScenarios(): iterable
    {
        yield 'true value returns true for isActive' => [
            'value' => true,
            'expectedIsActive' => true,
        ];

        yield 'false value returns false for isActive' => [
            'value' => false,
            'expectedIsActive' => false,
        ];
    }

    /**
     * Provides same value comparison scenarios.
     *
     * @return iterable<string, array{value: bool, otherValue: bool, expected: bool}>
     */
    public static function provideSameValueAsScenarios(): iterable
    {
        yield 'both true returns true' => [
            'value' => true,
            'otherValue' => true,
            'expected' => true,
        ];

        yield 'both false returns true' => [
            'value' => false,
            'otherValue' => false,
            'expected' => true,
        ];

        yield 'true and false returns false' => [
            'value' => true,
            'otherValue' => false,
            'expected' => false,
        ];

        yield 'false and true returns false' => [
            'value' => false,
            'otherValue' => true,
            'expected' => false,
        ];
    }
}
