<?php

declare(strict_types=1);

namespace Tests\Unit\DataProvider\Article\Domain\ValueObject;

/**
 * DataProvider for Id ValueObject tests.
 *
 * @see \Tests\Unit\Article\Domain\ValueObject\IdTest
 */
final class IdDataProvider
{
    /**
     * Provides valid UUID v4 values for Id construction.
     * Note: Id class requires strict UUID v4 format with version digit 4 and variant [89ab].
     *
     * @return iterable<string, array{value: string}>
     */
    public static function provideValidUuidValues(): iterable
    {
        yield 'standard uuid v4' => [
            'value' => '550e8400-e29b-41d4-a716-446655440000',
        ];

        yield 'another valid uuid v4' => [
            'value' => 'f47ac10b-58cc-4372-a567-0e02b2c3d479',
        ];

        yield 'lowercase uuid v4' => [
            'value' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
        ];

        yield 'uppercase uuid v4' => [
            'value' => '123E4567-E89B-42D3-A456-426614174000',
        ];
    }

    /**
     * Provides invalid values for Id validation.
     *
     * @return iterable<string, array{value: mixed, expectedException: class-string<\Throwable>}>
     */
    public static function provideInvalidValues(): iterable
    {
        yield 'empty string' => [
            'value' => '',
            'expectedException' => \MicroModule\ValueObject\Exception\InvalidNativeArgumentException::class,
        ];

        yield 'invalid format without hyphens' => [
            'value' => '550e8400e29b41d4a716446655440000',
            'expectedException' => \MicroModule\ValueObject\Exception\InvalidNativeArgumentException::class,
        ];

        yield 'too short' => [
            'value' => '550e8400-e29b-41d4',
            'expectedException' => \MicroModule\ValueObject\Exception\InvalidNativeArgumentException::class,
        ];

        yield 'invalid characters' => [
            'value' => '550e8400-e29b-41d4-a716-44665544xxxx',
            'expectedException' => \MicroModule\ValueObject\Exception\InvalidNativeArgumentException::class,
        ];

        yield 'uuid v1 format not v4' => [
            'value' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            'expectedException' => \MicroModule\ValueObject\Exception\InvalidNativeArgumentException::class,
        ];

        yield 'not uuid v4 format (wrong version digit)' => [
            'value' => '550e8400-e29b-51d4-a716-446655440000',
            'expectedException' => \MicroModule\ValueObject\Exception\InvalidNativeArgumentException::class,
        ];
    }

    /**
     * Provides scenarios for Id generation.
     *
     * @return iterable<string, array{iterations: int}>
     */
    public static function provideGenerationScenarios(): iterable
    {
        yield 'single generation' => [
            'iterations' => 1,
        ];

        yield 'multiple generations for uniqueness' => [
            'iterations' => 10,
        ];
    }
}
