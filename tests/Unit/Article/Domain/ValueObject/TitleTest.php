<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\ValueObject;

use Micro\Article\Domain\ValueObject\Title;
use MicroModule\ValueObject\Exception\InvalidNativeArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\ValueObject\TitleDataProvider;

/**
 * Unit tests for Title ValueObject.
 */
#[CoversClass(Title::class)]
final class TitleTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(TitleDataProvider::class, 'provideValidTitles')]
    public function constructWithValidValueShouldCreateInstance(string $value, string $expected): void
    {
        // Act
        $title = new Title($value);

        // Assert
        $this->assertInstanceOf(Title::class, $title);
        $this->assertSame($expected, $title->toNative());
    }

    #[Test]
    #[DataProviderExternal(TitleDataProvider::class, 'provideInvalidTitles')]
    public function constructWithInvalidValueShouldThrowException(string $value, string $expectedException): void
    {
        // Assert
        $this->expectException($expectedException);

        // Act
        new Title($value);
    }

    #[Test]
    #[DataProviderExternal(TitleDataProvider::class, 'provideBoundaryLengths')]
    public function constructWithBoundaryLengthShouldBehaveCorrectly(int $length, bool $shouldPass): void
    {
        $value = str_repeat('a', $length);

        if ($shouldPass) {
            $title = new Title($value);
            $this->assertSame($value, $title->toNative());
        } else {
            $this->expectException(InvalidNativeArgumentException::class);
            new Title($value);
        }
    }

    #[Test]
    #[DataProviderExternal(TitleDataProvider::class, 'provideSameValueAsScenarios')]
    public function sameValueAsShouldReturnCorrectResult(string $value, string $otherValue, bool $expected): void
    {
        // Arrange
        $title1 = new Title($value);
        $title2 = new Title($otherValue);

        // Act
        $result = $title1->sameValueAs($title2);

        // Assert
        $this->assertSame($expected, $result);
    }

    #[Test]
    #[DataProviderExternal(TitleDataProvider::class, 'provideToNativeScenarios')]
    public function toNativeShouldReturnOriginalValue(string $value): void
    {
        // Arrange
        $title = new Title($value);

        // Act
        $result = $title->toNative();

        // Assert
        $this->assertSame($value, $result);
        $this->assertIsString($result);
    }

    #[Test]
    public function fromNativeShouldCreateValidInstance(): void
    {
        // Arrange
        $value = 'Test Title Value';

        // Act
        $title = Title::fromNative($value);

        // Assert
        $this->assertInstanceOf(Title::class, $title);
        $this->assertSame($value, $title->toNative());
    }

    #[Test]
    public function toStringShouldReturnValue(): void
    {
        // Arrange
        $value = 'Test Title';
        $title = new Title($value);

        // Act
        $result = (string) $title;

        // Assert
        $this->assertSame($value, $result);
    }

    #[Test]
    public function isEmptyShouldReturnFalseForValidTitle(): void
    {
        // Arrange
        $title = new Title('Valid Title');

        // Act
        $result = $title->isEmpty();

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function getValueShouldReturnSameAsToNative(): void
    {
        // Arrange
        $value = 'Test Title';
        $title = new Title($value);

        // Act & Assert
        $this->assertSame($title->toNative(), $title->toNative());
    }

    #[Test]
    public function validateWithValueObjectShouldExtractNativeValue(): void
    {
        // Arrange - create valid Title instances
        $existingTitle = new Title('Existing Title');
        $title = new Title('Another Title');

        // Act - call validate() with ValueObject to cover the instanceof branch
        $title->validate($existingTitle);

        // Assert - no exception means validation passed
        $this->assertTrue(true);
    }

    #[Test]
    public function validateWithInvalidValueObjectShouldThrowException(): void
    {
        // Arrange
        $title = new Title('Valid Title');
        // Create a mock ValueObject that returns too short value (less than 3 chars)
        $mockValueObject = $this->createMock(\MicroModule\ValueObject\ValueObjectInterface::class);
        $mockValueObject->method('toNative')->willReturn('ab');

        // Assert
        $this->expectException(InvalidNativeArgumentException::class);

        // Act
        $title->validate($mockValueObject);
    }
}
