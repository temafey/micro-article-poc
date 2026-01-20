<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\ValueObject;

use MicroModule\ValueObject\Exception\InvalidNativeArgumentException;

/**
 * DataProvider for Uuid ValueObject tests.
 *
 * @see \Tests\Unit\Article\Domain\ValueObject\UuidTest
 */
final class UuidDataProvider
{
    /**
     * Provides valid UUID values for construction tests.
     *
     * @return iterable<string, array{value: string, expected: string}>
     */
    public static function provideValidUuids(): iterable
    {
        yield 'standard uuid v4' => [
            'value' => '550e8400-e29b-41d4-a716-446655440000',
            'expected' => '550e8400-e29b-41d4-a716-446655440000',
        ];

        yield 'uuid with lowercase' => [
            'value' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'expected' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
        ];

        yield 'nil uuid' => [
            'value' => '00000000-0000-0000-0000-000000000000',
            'expected' => '00000000-0000-0000-0000-000000000000',
        ];

        yield 'max uuid' => [
            'value' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
            'expected' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
        ];

        yield 'uuid v1 format' => [
            'value' => 'f47ac10b-58cc-1234-a567-0e02b2c3d479',
            'expected' => 'f47ac10b-58cc-1234-a567-0e02b2c3d479',
        ];
    }

    /**
     * Provides invalid UUID values for exception tests.
     *
     * @return iterable<string, array{value: string, expectedException: class-string<\Throwable>}>
     */
    public static function provideInvalidUuids(): iterable
    {
        yield 'empty string' => [
            'value' => '',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'invalid format without hyphens' => [
            'value' => '550e8400e29b41d4a716446655440000',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'invalid characters' => [
            'value' => '550e8400-e29b-41d4-a716-44665544000g',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'too short' => [
            'value' => '550e8400-e29b-41d4-a716',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'too long' => [
            'value' => '550e8400-e29b-41d4-a716-446655440000-extra',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'wrong hyphen positions' => [
            'value' => '550e-8400-e29b41d4-a716-446655440000',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'simple string' => [
            'value' => 'not-a-valid-uuid',
            'expectedException' => InvalidNativeArgumentException::class,
        ];

        yield 'numeric only' => [
            'value' => '12345678901234567890123456789012',
            'expectedException' => InvalidNativeArgumentException::class,
        ];
    }

    /**
     * Provides same value comparison scenarios.
     *
     * @return iterable<string, array{value: string, otherValue: string, expected: bool}>
     */
    public static function provideSameValueAsScenarios(): iterable
    {
        yield 'same uuid returns true' => [
            'value' => '550e8400-e29b-41d4-a716-446655440000',
            'otherValue' => '550e8400-e29b-41d4-a716-446655440000',
            'expected' => true,
        ];

        yield 'different uuid returns false' => [
            'value' => '550e8400-e29b-41d4-a716-446655440000',
            'otherValue' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'expected' => false,
        ];
    }

    /**
     * Provides UUID generation scenarios.
     *
     * @return iterable<string, array{description: string}>
     */
    public static function provideGenerationScenarios(): iterable
    {
        yield 'generate returns valid format' => [
            'description' => 'Generated UUID should match RFC 4122 format',
        ];

        yield 'generate returns unique values' => [
            'description' => 'Multiple generations should produce unique UUIDs',
        ];
    }
}
