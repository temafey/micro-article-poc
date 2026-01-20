<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\ValueObject;

use Micro\Article\Domain\ValueObject\ShortDescription;
use MicroModule\ValueObject\Exception\InvalidNativeArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\ValueObject\ShortDescriptionDataProvider;

/**
 * Unit tests for ShortDescription ValueObject.
 */
#[CoversClass(ShortDescription::class)]
final class ShortDescriptionTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(ShortDescriptionDataProvider::class, 'provideValidShortDescriptions')]
    public function constructWithValidValueShouldCreateInstance(string $value, string $expected): void
    {
        // Act
        $shortDescription = new ShortDescription($value);

        // Assert
        $this->assertInstanceOf(ShortDescription::class, $shortDescription);
        $this->assertSame($expected, $shortDescription->toNative());
    }

    #[Test]
    #[DataProviderExternal(ShortDescriptionDataProvider::class, 'provideInvalidShortDescriptions')]
    public function constructWithInvalidValueShouldThrowException(string $value, string $expectedException): void
    {
        // Assert
        $this->expectException($expectedException);

        // Act
        new ShortDescription($value);
    }

    #[Test]
    #[DataProviderExternal(ShortDescriptionDataProvider::class, 'provideBoundaryLengths')]
    public function constructWithBoundaryLengthShouldBehaveCorrectly(int $length, bool $shouldPass): void
    {
        $value = str_repeat('a', $length);

        if ($shouldPass) {
            $shortDescription = new ShortDescription($value);
            $this->assertSame($value, $shortDescription->toNative());
        } else {
            $this->expectException(InvalidNativeArgumentException::class);
            new ShortDescription($value);
        }
    }

    #[Test]
    public function fromNativeShouldCreateValidInstance(): void
    {
        // Arrange
        $value = 'A brief summary of the article article.';

        // Act
        $shortDescription = ShortDescription::fromNative($value);

        // Assert
        $this->assertInstanceOf(ShortDescription::class, $shortDescription);
        $this->assertSame($value, $shortDescription->toNative());
    }

    #[Test]
    public function toStringShouldReturnValue(): void
    {
        // Arrange
        $value = 'Short description text here.';
        $shortDescription = new ShortDescription($value);

        // Act
        $result = (string) $shortDescription;

        // Assert
        $this->assertSame($value, $result);
    }

    #[Test]
    public function sameValueAsShouldReturnTrueForEqualDescriptions(): void
    {
        // Arrange
        $value = 'Same short description text.';
        $shortDesc1 = new ShortDescription($value);
        $shortDesc2 = new ShortDescription($value);

        // Act
        $result = $shortDesc1->sameValueAs($shortDesc2);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function sameValueAsShouldReturnFalseForDifferentDescriptions(): void
    {
        // Arrange
        $shortDesc1 = new ShortDescription('First short description.');
        $shortDesc2 = new ShortDescription('Second short description.');

        // Act
        $result = $shortDesc1->sameValueAs($shortDesc2);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function isEmptyShouldReturnFalseForValidShortDescription(): void
    {
        // Arrange
        $shortDescription = new ShortDescription('Valid short description.');

        // Act
        $result = $shortDescription->isEmpty();

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function validateWithValueObjectShouldExtractNativeValue(): void
    {
        // Arrange - create valid ShortDescription instances
        $existingShortDescription = new ShortDescription('This is a valid existing short description.');
        $shortDescription = new ShortDescription('This is another valid short description.');

        // Act - call validate() with ValueObject to cover the instanceof branch
        $shortDescription->validate($existingShortDescription);

        // Assert - no exception means validation passed
        $this->assertTrue(true);
    }

    #[Test]
    public function validateWithInvalidValueObjectShouldThrowException(): void
    {
        // Arrange
        $shortDescription = new ShortDescription('Valid short description.');
        // Create a mock ValueObject that returns too short value
        $mockValueObject = $this->createMock(\MicroModule\ValueObject\ValueObjectInterface::class);
        $mockValueObject->method('toNative')->willReturn('ab');

        // Assert
        $this->expectException(InvalidNativeArgumentException::class);

        // Act
        $shortDescription->validate($mockValueObject);
    }
}
