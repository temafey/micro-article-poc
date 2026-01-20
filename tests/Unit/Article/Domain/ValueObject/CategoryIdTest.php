<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\ValueObject;

use Micro\Article\Domain\ValueObject\CategoryId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\ValueObject\CategoryIdDataProvider;

/**
 * Unit tests for CategoryId ValueObject.
 */
#[CoversClass(CategoryId::class)]
final class CategoryIdTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(CategoryIdDataProvider::class, 'provideValidUuids')]
    public function constructWithValidValueShouldCreateInstance(string $value, string $expected): void
    {
        // Act
        $categoryId = CategoryId::fromNative($value);

        // Assert
        $this->assertInstanceOf(CategoryId::class, $categoryId);
        $this->assertSame($expected, $categoryId->toNative());
    }

    #[Test]
    #[DataProviderExternal(CategoryIdDataProvider::class, 'provideInvalidUuids')]
    public function constructWithInvalidValueShouldThrowException(string $value, string $expectedException): void
    {
        // Assert
        $this->expectException($expectedException);

        // Act
        CategoryId::fromNative($value);
    }

    #[Test]
    #[DataProviderExternal(CategoryIdDataProvider::class, 'provideSameValueAsScenarios')]
    public function sameValueAsShouldReturnCorrectResult(string $value, string $otherValue, bool $expected): void
    {
        // Arrange
        $categoryId1 = CategoryId::fromNative($value);
        $categoryId2 = CategoryId::fromNative($otherValue);

        // Act
        $result = $categoryId1->sameValueAs($categoryId2);

        // Assert
        $this->assertSame($expected, $result);
    }

    #[Test]
    public function defaultConstructorShouldGenerateUuid(): void
    {
        // Act
        $categoryId = new CategoryId();

        // Assert
        $this->assertInstanceOf(CategoryId::class, $categoryId);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $categoryId->toNative()
        );
    }

    #[Test]
    public function defaultConstructorShouldGenerateUniqueValues(): void
    {
        // Act
        $categoryId1 = new CategoryId();
        $categoryId2 = new CategoryId();

        // Assert
        $this->assertNotSame($categoryId1->toNative(), $categoryId2->toNative());
    }

    #[Test]
    public function toStringShouldReturnValue(): void
    {
        // Arrange
        $value = '550e8400-e29b-41d4-a716-446655440000';
        $categoryId = CategoryId::fromNative($value);

        // Act
        $result = (string) $categoryId;

        // Assert
        $this->assertSame($value, $result);
    }

    #[Test]
    public function toNativeShouldReturnOriginalValue(): void
    {
        // Arrange
        $value = '550e8400-e29b-41d4-a716-446655440000';
        $categoryId = CategoryId::fromNative($value);

        // Act
        $result = $categoryId->toNative();

        // Assert
        $this->assertSame($value, $result);
    }

    #[Test]
    public function isEmptyShouldReturnFalseForValidUuid(): void
    {
        // Arrange
        $categoryId = CategoryId::fromNative('550e8400-e29b-41d4-a716-446655440000');

        // Act
        $result = $categoryId->isEmpty();

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function nilUuidShouldBeValid(): void
    {
        // Arrange
        $nilValue = '00000000-0000-0000-0000-000000000000';

        // Act
        $categoryId = CategoryId::fromNative($nilValue);

        // Assert
        $this->assertSame($nilValue, $categoryId->toNative());
    }
}
