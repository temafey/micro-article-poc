<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\ValueObject;

/**
 * DataProvider for Status ValueObject tests.
 *
 * @see \Tests\Unit\Article\Domain\ValueObject\StatusTest
 */
final class StatusDataProvider
{
    /**
     * Provides valid status values for construction tests.
     *
     * @return iterable<string, array{value: string, expected: string}>
     */
    public static function provideValidStatuses(): iterable
    {
        yield 'draft status' => [
            'value' => 'draft',
            'expected' => 'draft',
        ];

        yield 'published status' => [
            'value' => 'published',
            'expected' => 'published',
        ];

        yield 'archived status' => [
            'value' => 'archived',
            'expected' => 'archived',
        ];

        yield 'deleted status' => [
            'value' => 'deleted',
            'expected' => 'deleted',
        ];
    }

    /**
     * Provides invalid status values for exception tests.
     *
     * @return iterable<string, array{value: string, expectedException: class-string<\Throwable>}>
     */
    public static function provideInvalidStatuses(): iterable
    {
        yield 'empty string' => [
            'value' => '',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'unknown status' => [
            'value' => 'unknown',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'uppercase draft' => [
            'value' => 'DRAFT',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'mixed case published' => [
            'value' => 'Published',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'pending status not valid' => [
            'value' => 'pending',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'active status not valid' => [
            'value' => 'active',
            'expectedException' => \InvalidArgumentException::class,
        ];

        yield 'numeric value' => [
            'value' => '123',
            'expectedException' => \InvalidArgumentException::class,
        ];
    }

    /**
     * Provides status constants for verification.
     *
     * @return iterable<string, array{constant: string, expectedValue: string}>
     */
    public static function provideStatusConstants(): iterable
    {
        yield 'DRAFT constant' => [
            'constant' => 'DRAFT',
            'expectedValue' => 'draft',
        ];

        yield 'PUBLISHED constant' => [
            'constant' => 'PUBLISHED',
            'expectedValue' => 'published',
        ];

        yield 'ARCHIVED constant' => [
            'constant' => 'ARCHIVED',
            'expectedValue' => 'archived',
        ];

        yield 'DELETED constant' => [
            'constant' => 'DELETED',
            'expectedValue' => 'deleted',
        ];
    }

    /**
     * Provides same value comparison scenarios.
     *
     * @return iterable<string, array{value: string, otherValue: string, expected: bool}>
     */
    public static function provideSameValueAsScenarios(): iterable
    {
        yield 'same draft status' => [
            'value' => 'draft',
            'otherValue' => 'draft',
            'expected' => true,
        ];

        yield 'different statuses' => [
            'value' => 'draft',
            'otherValue' => 'published',
            'expected' => false,
        ];

        yield 'same published status' => [
            'value' => 'published',
            'otherValue' => 'published',
            'expected' => true,
        ];
    }
}
