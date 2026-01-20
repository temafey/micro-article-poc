<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\ValueObject;

use Micro\Article\Domain\ValueObject\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\ValueObject\UuidDataProvider;

/**
 * Unit tests for Uuid ValueObject.
 */
#[CoversClass(Uuid::class)]
final class UuidTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(UuidDataProvider::class, 'provideValidUuids')]
    public function constructWithValidValueShouldCreateInstance(string $value, string $expected): void
    {
        // Act
        $uuid = Uuid::fromNative($value);

        // Assert
        $this->assertInstanceOf(Uuid::class, $uuid);
        $this->assertSame($expected, $uuid->toNative());
    }

    #[Test]
    #[DataProviderExternal(UuidDataProvider::class, 'provideInvalidUuids')]
    public function constructWithInvalidValueShouldThrowException(string $value, string $expectedException): void
    {
        // Assert
        $this->expectException($expectedException);

        // Act
        Uuid::fromNative($value);
    }

    #[Test]
    #[DataProviderExternal(UuidDataProvider::class, 'provideSameValueAsScenarios')]
    public function sameValueAsShouldReturnCorrectResult(string $value, string $otherValue, bool $expected): void
    {
        // Arrange
        $uuid1 = Uuid::fromNative($value);
        $uuid2 = Uuid::fromNative($otherValue);

        // Act
        $result = $uuid1->sameValueAs($uuid2);

        // Assert
        $this->assertSame($expected, $result);
    }

    #[Test]
    public function defaultConstructorShouldGenerateUuid(): void
    {
        // Act
        $uuid = new Uuid();

        // Assert
        $this->assertInstanceOf(Uuid::class, $uuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $uuid->toNative()
        );
    }

    #[Test]
    public function defaultConstructorShouldGenerateUniqueValues(): void
    {
        // Act
        $uuid1 = new Uuid();
        $uuid2 = new Uuid();

        // Assert
        $this->assertNotSame($uuid1->toNative(), $uuid2->toNative());
    }

    #[Test]
    public function toStringShouldReturnValue(): void
    {
        // Arrange
        $value = '550e8400-e29b-41d4-a716-446655440000';
        $uuid = Uuid::fromNative($value);

        // Act
        $result = (string) $uuid;

        // Assert
        $this->assertSame($value, $result);
    }

    #[Test]
    public function toNativeShouldReturnOriginalValue(): void
    {
        // Arrange
        $value = '550e8400-e29b-41d4-a716-446655440000';
        $uuid = Uuid::fromNative($value);

        // Act
        $result = $uuid->toNative();

        // Assert
        $this->assertSame($value, $result);
    }

    #[Test]
    public function isEmptyShouldReturnFalseForValidUuid(): void
    {
        // Arrange
        $uuid = Uuid::fromNative('550e8400-e29b-41d4-a716-446655440000');

        // Act
        $result = $uuid->isEmpty();

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function nilUuidShouldBeValid(): void
    {
        // Arrange
        $nilValue = '00000000-0000-0000-0000-000000000000';

        // Act
        $uuid = Uuid::fromNative($nilValue);

        // Assert
        $this->assertSame($nilValue, $uuid->toNative());
    }
}
