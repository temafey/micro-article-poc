<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\ValueObject;

use DateTime;

/**
 * DataProvider for ArchivedAt ValueObject tests.
 *
 * @see \Tests\Unit\Article\Domain\ValueObject\ArchivedAtTest
 */
final class ArchivedAtDataProvider
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

        yield 'archive date' => [
            'value' => '2023-12-31 23:59:59',
            'expectedFormat' => '2023-12-31 23:59:59',
        ];

        yield 'old content archived' => [
            'value' => '2020-01-01 00:00:00',
            'expectedFormat' => '2020-01-01 00:00:00',
        ];

        yield 'recent archive' => [
            'value' => '2024-06-15 18:30:45',
            'expectedFormat' => '2024-06-15 18:30:45',
        ];

        yield 'end of year archive' => [
            'value' => '2023-12-31 23:59:00',
            'expectedFormat' => '2023-12-31 23:59:00',
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
