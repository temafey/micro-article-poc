<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\ValueObject;

use Micro\Article\Domain\ValueObject\ProcessUuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\ValueObject\ProcessUuidDataProvider;

/**
 * Unit tests for ProcessUuid ValueObject.
 */
#[CoversClass(ProcessUuid::class)]
final class ProcessUuidTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(ProcessUuidDataProvider::class, 'provideValidUuids')]
    public function constructWithValidValueShouldCreateInstance(string $value, string $expected): void
    {
        // Act
        $processUuid = ProcessUuid::fromNative($value);

        // Assert
        $this->assertInstanceOf(ProcessUuid::class, $processUuid);
        $this->assertSame($expected, $processUuid->toNative());
    }

    #[Test]
    #[DataProviderExternal(ProcessUuidDataProvider::class, 'provideInvalidUuids')]
    public function constructWithInvalidValueShouldThrowException(string $value, string $expectedException): void
    {
        // Assert
        $this->expectException($expectedException);

        // Act
        ProcessUuid::fromNative($value);
    }

    #[Test]
    #[DataProviderExternal(ProcessUuidDataProvider::class, 'provideSameValueAsScenarios')]
    public function sameValueAsShouldReturnCorrectResult(string $value, string $otherValue, bool $expected): void
    {
        // Arrange
        $processUuid1 = ProcessUuid::fromNative($value);
        $processUuid2 = ProcessUuid::fromNative($otherValue);

        // Act
        $result = $processUuid1->sameValueAs($processUuid2);

        // Assert
        $this->assertSame($expected, $result);
    }

    #[Test]
    public function defaultConstructorShouldGenerateUuid(): void
    {
        // Act
        $processUuid = new ProcessUuid();

        // Assert
        $this->assertInstanceOf(ProcessUuid::class, $processUuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $processUuid->toNative()
        );
    }

    #[Test]
    public function defaultConstructorShouldGenerateUniqueValues(): void
    {
        // Act
        $processUuid1 = new ProcessUuid();
        $processUuid2 = new ProcessUuid();

        // Assert
        $this->assertNotSame($processUuid1->toNative(), $processUuid2->toNative());
    }

    #[Test]
    public function toStringShouldReturnValue(): void
    {
        // Arrange
        $value = '550e8400-e29b-41d4-a716-446655440000';
        $processUuid = ProcessUuid::fromNative($value);

        // Act
        $result = (string) $processUuid;

        // Assert
        $this->assertSame($value, $result);
    }

    #[Test]
    public function toNativeShouldReturnOriginalValue(): void
    {
        // Arrange
        $value = '550e8400-e29b-41d4-a716-446655440000';
        $processUuid = ProcessUuid::fromNative($value);

        // Act
        $result = $processUuid->toNative();

        // Assert
        $this->assertSame($value, $result);
    }

    #[Test]
    public function isEmptyShouldReturnFalseForValidUuid(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');

        // Act
        $result = $processUuid->isEmpty();

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function nilUuidShouldBeValid(): void
    {
        // Arrange
        $nilValue = '00000000-0000-0000-0000-000000000000';

        // Act
        $processUuid = ProcessUuid::fromNative($nilValue);

        // Assert
        $this->assertSame($nilValue, $processUuid->toNative());
    }
}
