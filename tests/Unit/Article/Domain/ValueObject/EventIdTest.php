<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\ValueObject;

use Micro\Article\Domain\ValueObject\EventId;
use MicroModule\ValueObject\Exception\InvalidNativeArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\ValueObject\EventIdDataProvider;

/**
 * Unit tests for EventId ValueObject.
 */
#[CoversClass(EventId::class)]
final class EventIdTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(EventIdDataProvider::class, 'provideValidValues')]
    public function constructWithValidValueShouldCreateInstance(int $value): void
    {
        // Act
        $eventId = new EventId($value);

        // Assert
        $this->assertInstanceOf(EventId::class, $eventId);
    }

    #[Test]
    #[DataProviderExternal(EventIdDataProvider::class, 'provideValidValues')]
    public function fromNativeWithValidValueShouldCreateInstance(int $value): void
    {
        // Act
        $eventId = EventId::fromNative($value);

        // Assert
        $this->assertInstanceOf(EventId::class, $eventId);
    }

    #[Test]
    #[DataProviderExternal(EventIdDataProvider::class, 'provideToNativeScenarios')]
    public function toNativeShouldReturnCorrectValue(int $value, int $expectedNative): void
    {
        // Arrange
        $eventId = new EventId($value);

        // Act
        $result = $eventId->toNative();

        // Assert
        $this->assertSame($expectedNative, $result);
    }

    #[Test]
    #[DataProviderExternal(EventIdDataProvider::class, 'provideInvalidValues')]
    public function constructWithInvalidValueShouldThrowException(int $value, string $expectedException): void
    {
        // Assert
        $this->expectException($expectedException);

        // Act
        new EventId($value);
    }

    #[Test]
    public function sameValueAsShouldReturnTrueForEqualEventIds(): void
    {
        // Arrange
        $eventId1 = new EventId(12345);
        $eventId2 = new EventId(12345);

        // Act
        $result = $eventId1->sameValueAs($eventId2);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function sameValueAsShouldReturnFalseForDifferentEventIds(): void
    {
        // Arrange
        $eventId1 = new EventId(12345);
        $eventId2 = new EventId(67890);

        // Act
        $result = $eventId1->sameValueAs($eventId2);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function toStringShouldReturnStringValue(): void
    {
        // Arrange
        $eventId = new EventId(12345);

        // Act
        $result = (string) $eventId;

        // Assert
        $this->assertSame('12345', $result);
    }

    #[Test]
    public function validateWithValueObjectShouldExtractNativeValue(): void
    {
        // Arrange - create valid EventId instances
        $existingEventId = new EventId(100);
        $eventId = new EventId(200);

        // Act - call validate() with ValueObject to cover the instanceof branch
        $eventId->validate($existingEventId);

        // Assert - no exception means validation passed
        $this->assertTrue(true);
    }

    #[Test]
    public function validateWithInvalidValueObjectShouldThrowException(): void
    {
        // Arrange
        $eventId = new EventId(100);
        // Create a mock ValueObject that returns invalid value (0 or negative)
        $mockValueObject = $this->createMock(\MicroModule\ValueObject\ValueObjectInterface::class);
        $mockValueObject->method('toNative')->willReturn(0);

        // Assert
        $this->expectException(InvalidNativeArgumentException::class);

        // Act
        $eventId->validate($mockValueObject);
    }
}
