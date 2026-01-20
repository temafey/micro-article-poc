<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\ValueObject;

/**
 * DataProvider for Deleted ValueObject tests.
 *
 * @see \Tests\Unit\Article\Domain\ValueObject\DeletedTest
 */
final class DeletedDataProvider
{
    /**
     * Provides valid deleted state values.
     *
     * @return iterable<string, array{value: bool, expected: bool}>
     */
    public static function provideValidDeletedStates(): iterable
    {
        yield 'deleted true' => [
            'value' => true,
            'expected' => true,
        ];

        yield 'deleted false' => [
            'value' => false,
            'expected' => false,
        ];
    }

    /**
     * Provides isDeleted scenarios.
     *
     * @return iterable<string, array{value: bool, expectedIsDeleted: bool}>
     */
    public static function provideIsDeletedScenarios(): iterable
    {
        yield 'true value returns true for isDeleted' => [
            'value' => true,
            'expectedIsDeleted' => true,
        ];

        yield 'false value returns false for isDeleted' => [
            'value' => false,
            'expectedIsDeleted' => false,
        ];
    }

    /**
     * Provides factory method scenarios.
     *
     * @return iterable<string, array{method: string, expectedValue: bool}>
     */
    public static function provideFactoryMethodScenarios(): iterable
    {
        yield 'deleted factory creates true' => [
            'method' => 'deleted',
            'expectedValue' => true,
        ];

        yield 'notDeleted factory creates false' => [
            'method' => 'notDeleted',
            'expectedValue' => false,
        ];
    }

    /**
     * Provides same value comparison scenarios.
     *
     * @return iterable<string, array{value: bool, otherValue: bool, expected: bool}>
     */
    public static function provideSameValueAsScenarios(): iterable
    {
        yield 'both deleted returns true' => [
            'value' => true,
            'otherValue' => true,
            'expected' => true,
        ];

        yield 'both not deleted returns true' => [
            'value' => false,
            'otherValue' => false,
            'expected' => true,
        ];

        yield 'deleted and not deleted returns false' => [
            'value' => true,
            'otherValue' => false,
            'expected' => false,
        ];
    }
}
