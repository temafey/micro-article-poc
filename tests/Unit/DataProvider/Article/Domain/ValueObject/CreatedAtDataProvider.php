<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\ValueObject;

use DateTime;

/**
 * DataProvider for CreatedAt ValueObject tests.
 *
 * @see \Tests\Unit\Article\Domain\ValueObject\CreatedAtTest
 */
final class CreatedAtDataProvider
{
    /**
     * Provides valid date string values for construction tests.
     *
     * @return iterable<string, array{value: string, expectedFormat: string}>
     */
    public static function provideValidDateStrings(): iterable
    {
        yield 'standard datetime format' => [
            'value' => '2024-01-15 10:30:00',
            'expectedFormat' => '2024-01-15 10:30:00',
        ];

        yield 'date only format' => [
            'value' => '2024-01-15',
            'expectedFormat' => '2024-01-15 00:00:00',
        ];

        yield 'ISO 8601 format' => [
            'value' => '2024-01-15T10:30:00',
            'expectedFormat' => '2024-01-15 10:30:00',
        ];

        yield 'datetime with timezone' => [
            'value' => '2024-01-15 10:30:00+00:00',
            'expectedFormat' => '2024-01-15 10:30:00',
        ];

        yield 'past date' => [
            'value' => '2020-06-15 08:45:30',
            'expectedFormat' => '2020-06-15 08:45:30',
        ];

        yield 'entity creation timestamp' => [
            'value' => '2024-01-01 00:00:01',
            'expectedFormat' => '2024-01-01 00:00:01',
        ];

        yield 'midnight creation' => [
            'value' => '2024-01-01 00:00:00',
            'expectedFormat' => '2024-01-01 00:00:00',
        ];

        yield 'precise microseconds' => [
            'value' => '2024-01-15 10:30:00',
            'expectedFormat' => '2024-01-15 10:30:00',
        ];
    }

    /**
     * Provides valid DateTime objects for construction tests.
     *
     * @return iterable<string, array{value: \DateTimeInterface}>
     */
    public static function provideValidDateTimeObjects(): iterable
    {
        yield 'DateTime object' => [
            'value' => new \DateTime('2024-01-15 10:30:00'),
        ];

        yield 'DateTimeImmutable object' => [
            'value' => new \DateTimeImmutable('2024-01-15 10:30:00'),
        ];

        yield 'DateTime with timezone' => [
            'value' => new \DateTime('2024-01-15 10:30:00', new \DateTimeZone('UTC')),
        ];
    }

    /**
     * Provides same value comparison scenarios.
     *
     * @return iterable<string, array{value1: string, value2: string, expected: bool}>
     */
    public static function provideSameValueAsScenarios(): iterable
    {
        yield 'same datetime returns true' => [
            'value1' => '2024-01-15 10:30:00',
            'value2' => '2024-01-15 10:30:00',
            'expected' => true,
        ];

        yield 'different datetime returns false' => [
            'value1' => '2024-01-15 10:30:00',
            'value2' => '2024-01-15 10:30:01',
            'expected' => false,
        ];

        yield 'different date returns false' => [
            'value1' => '2024-01-15 10:30:00',
            'value2' => '2024-01-16 10:30:00',
            'expected' => false,
        ];

        yield 'different time returns false' => [
            'value1' => '2024-01-15 10:30:00',
            'value2' => '2024-01-15 11:30:00',
            'expected' => false,
        ];
    }
}
