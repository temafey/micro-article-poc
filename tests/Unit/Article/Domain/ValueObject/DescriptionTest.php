<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\ValueObject;

use Micro\Article\Domain\ValueObject\Description;
use MicroModule\ValueObject\Exception\InvalidNativeArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\ValueObject\DescriptionDataProvider;

/**
 * Unit tests for Description ValueObject.
 */
#[CoversClass(Description::class)]
final class DescriptionTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(DescriptionDataProvider::class, 'provideValidDescriptions')]
    public function constructWithValidValueShouldCreateInstance(string $value, string $expected): void
    {
        // Act
        $description = new Description($value);

        // Assert
        $this->assertInstanceOf(Description::class, $description);
        $this->assertSame($expected, $description->toNative());
    }

    #[Test]
    #[DataProviderExternal(DescriptionDataProvider::class, 'provideInvalidDescriptions')]
    public function constructWithInvalidValueShouldThrowException(string $value, string $expectedException): void
    {
        // Assert
        $this->expectException($expectedException);

        // Act
        new Description($value);
    }

    #[Test]
    #[DataProviderExternal(DescriptionDataProvider::class, 'provideBoundaryLengths')]
    public function constructWithBoundaryLengthShouldBehaveCorrectly(int $length, bool $shouldPass): void
    {
        $value = str_repeat('a', $length);

        if ($shouldPass) {
            $description = new Description($value);
            $this->assertSame($value, $description->toNative());
        } else {
            $this->expectException(InvalidNativeArgumentException::class);
            new Description($value);
        }
    }

    #[Test]
    #[DataProviderExternal(DescriptionDataProvider::class, 'provideSameValueAsScenarios')]
    public function sameValueAsShouldReturnCorrectResult(string $value, string $otherValue, bool $expected): void
    {
        // Arrange
        $description1 = new Description($value);
        $description2 = new Description($otherValue);

        // Act
        $result = $description1->sameValueAs($description2);

        // Assert
        $this->assertSame($expected, $result);
    }

    #[Test]
    public function fromNativeShouldCreateValidInstance(): void
    {
        // Arrange
        $value = 'This is a valid description with at least fifty characters for testing purposes.';

        // Act
        $description = Description::fromNative($value);

        // Assert
        $this->assertInstanceOf(Description::class, $description);
        $this->assertSame($value, $description->toNative());
    }

    #[Test]
    public function toStringShouldReturnValue(): void
    {
        // Arrange
        $value = 'This is a valid description with at least fifty characters for testing purposes.';
        $description = new Description($value);

        // Act
        $result = (string) $description;

        // Assert
        $this->assertSame($value, $result);
    }

    #[Test]
    public function isEmptyShouldReturnFalseForValidDescription(): void
    {
        // Arrange
        $description = new Description(str_repeat('a', 50));

        // Act
        $result = $description->isEmpty();

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function lengthValidationShouldEnforceMinimumFiftyCharacters(): void
    {
        // Arrange - 49 characters should fail
        $tooShort = str_repeat('a', 49);

        // Assert
        $this->expectException(InvalidNativeArgumentException::class);

        // Act
        new Description($tooShort);
    }

    #[Test]
    public function lengthValidationShouldEnforceMaximumFiftyThousandCharacters(): void
    {
        // Arrange - 50001 characters should fail
        $tooLong = str_repeat('a', 50001);

        // Assert
        $this->expectException(InvalidNativeArgumentException::class);

        // Act
        new Description($tooLong);
    }

    #[Test]
    public function validateWithValueObjectShouldExtractNativeValue(): void
    {
        // Arrange - create valid Description instances
        $existingDescription = new Description('This is a valid existing description with at least fifty characters.');
        $description = new Description('This is another valid description with at least fifty characters for test.');

        // Act - call validate() with ValueObject to cover the instanceof branch
        $description->validate($existingDescription);

        // Assert - no exception means validation passed
        $this->assertTrue(true);
    }

    #[Test]
    public function validateWithInvalidValueObjectShouldThrowException(): void
    {
        // Arrange
        $description = new Description('This is a valid description with at least fifty characters for testing.');
        // Create a mock ValueObject that returns too short value
        $mockValueObject = $this->createMock(\MicroModule\ValueObject\ValueObjectInterface::class);
        $mockValueObject->method('toNative')->willReturn('short');

        // Assert
        $this->expectException(InvalidNativeArgumentException::class);

        // Act
        $description->validate($mockValueObject);
    }
}
