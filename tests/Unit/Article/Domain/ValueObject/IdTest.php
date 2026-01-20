<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\ValueObject;

use Micro\Article\Domain\ValueObject\Id;
use MicroModule\ValueObject\Exception\InvalidNativeArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\ValueObject\IdDataProvider;

/**
 * Unit tests for Id ValueObject.
 */
#[CoversClass(Id::class)]
final class IdTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(IdDataProvider::class, 'provideValidUuidValues')]
    public function constructWithValidUuidShouldCreateInstance(string $value): void
    {
        // Act
        $id = new Id($value);

        // Assert
        $this->assertInstanceOf(Id::class, $id);
    }

    #[Test]
    #[DataProviderExternal(IdDataProvider::class, 'provideValidUuidValues')]
    public function fromNativeWithValidUuidShouldCreateInstance(string $value): void
    {
        // Act
        $id = Id::fromNative($value);

        // Assert
        $this->assertInstanceOf(Id::class, $id);
    }

    #[Test]
    #[DataProviderExternal(IdDataProvider::class, 'provideValidUuidValues')]
    public function toNativeShouldReturnOriginalValue(string $value): void
    {
        // Arrange
        $id = new Id($value);

        // Act
        $result = $id->toNative();

        // Assert
        $this->assertSame(strtolower($value), strtolower($result));
    }

    #[Test]
    #[DataProviderExternal(IdDataProvider::class, 'provideInvalidValues')]
    public function constructWithInvalidValueShouldThrowException(string $value, string $expectedException): void
    {
        // Assert
        $this->expectException($expectedException);

        // Act
        new Id($value);
    }

    #[Test]
    #[DataProviderExternal(IdDataProvider::class, 'provideGenerationScenarios')]
    public function generateShouldCreateUniqueIds(int $iterations): void
    {
        // Arrange
        $generatedIds = [];

        // Act
        for ($i = 0; $i < $iterations; ++$i) {
            $id = Id::generate();
            $generatedIds[] = $id->toNative();
        }

        // Assert
        $this->assertCount($iterations, array_unique($generatedIds));
    }

    #[Test]
    public function generateShouldReturnValidUuidV4Format(): void
    {
        // Act
        $id = Id::generate();
        $value = $id->toNative();

        // Assert
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value
        );
    }

    #[Test]
    public function toStringShouldReturnUuidValue(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id = new Id($uuid);

        // Act
        $result = (string) $id;

        // Assert
        $this->assertSame($uuid, $result);
    }

    #[Test]
    public function sameValueAsShouldReturnTrueForEqualIds(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $id1 = new Id($uuid);
        $id2 = new Id($uuid);

        // Act
        $result = $id1->sameValueAs($id2);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function sameValueAsShouldReturnFalseForDifferentIds(): void
    {
        // Arrange
        $id1 = new Id('550e8400-e29b-41d4-a716-446655440000');
        $id2 = new Id('f47ac10b-58cc-4372-a567-0e02b2c3d479');

        // Act
        $result = $id1->sameValueAs($id2);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function validateWithValueObjectShouldExtractNativeValue(): void
    {
        // Arrange - create valid Id instances
        $existingId = new Id('550e8400-e29b-41d4-a716-446655440000');
        $id = new Id('f47ac10b-58cc-4372-a567-0e02b2c3d479');

        // Act - call validate() with ValueObject to cover the instanceof branch
        $id->validate($existingId);

        // Assert - no exception means validation passed
        $this->assertTrue(true);
    }

    #[Test]
    public function validateWithInvalidValueObjectShouldThrowException(): void
    {
        // Arrange
        $id = new Id('550e8400-e29b-41d4-a716-446655440000');
        // Create a mock ValueObject that returns invalid UUID value
        $mockValueObject = $this->createMock(\MicroModule\ValueObject\ValueObjectInterface::class);
        $mockValueObject->method('toNative')->willReturn('invalid-uuid');

        // Assert
        $this->expectException(InvalidNativeArgumentException::class);

        // Act
        $id->validate($mockValueObject);
    }

    #[Test]
    public function validateWithNonStringValueShouldThrowException(): void
    {
        // Arrange
        $id = new Id('550e8400-e29b-41d4-a716-446655440000');

        // Assert
        $this->expectException(InvalidNativeArgumentException::class);
        $this->expectExceptionMessage('Article ID must be a valid UUID string');

        // Act - pass integer instead of string to cover is_string check
        $id->validate(12345);
    }
}
