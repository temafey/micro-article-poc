<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\ValueObject;

/**
 * DataProvider for EventId ValueObject tests.
 *
 * @see \Tests\Unit\Article\Domain\ValueObject\EventIdTest
 */
final class EventIdDataProvider
{
    /**
     * Provides valid integer values for EventId construction.
     *
     * @return iterable<string, array{value: int}>
     */
    public static function provideValidValues(): iterable
    {
        yield 'minimum valid value' => [
            'value' => 1,
        ];

        yield 'standard event id' => [
            'value' => 12345,
        ];

        yield 'large event id' => [
            'value' => 999999,
        ];

        yield 'max int value' => [
            'value' => PHP_INT_MAX,
        ];
    }

    /**
     * Provides invalid values for EventId validation.
     *
     * @return iterable<string, array{value: int, expectedException: class-string<\Throwable>}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'zero value' => [
            'value' => 0,
            'expectedException' => \MicroModule\ValueObject\Exception\InvalidNativeArgumentException::class,
        ];

        yield 'negative value' => [
            'value' => -1,
            'expectedException' => \MicroModule\ValueObject\Exception\InvalidNativeArgumentException::class,
        ];

        yield 'large negative value' => [
            'value' => -99999,
            'expectedException' => \MicroModule\ValueObject\Exception\InvalidNativeArgumentException::class,
        ];
    }

    /**
     * Provides scenarios for toNative conversion.
     *
     * @return iterable<string, array{value: int, expectedNative: int}>
     */
    public static function provideToNativeScenarios(): iterable
    {
        yield 'small event id' => [
            'value' => 42,
            'expectedNative' => 42,
        ];

        yield 'large event id' => [
            'value' => 123456789,
            'expectedNative' => 123456789,
        ];
    }
}
