<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\ValueObject;

use Micro\Article\Domain\ValueObject\UpdatedAt;
use MicroModule\ValueObject\DateTime\Date;
use MicroModule\ValueObject\DateTime\Time;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\ValueObject\UpdatedAtDataProvider;

/**
 * Unit tests for UpdatedAt ValueObject.
 */
#[CoversClass(UpdatedAt::class)]
final class UpdatedAtTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(UpdatedAtDataProvider::class, 'provideValidDateStrings')]
    public function fromNativeWithValidStringShouldCreateInstance(string $value, string $expectedFormat): void
    {
        // Act
        $updatedAt = UpdatedAt::fromNative($value);

        // Assert
        $this->assertInstanceOf(UpdatedAt::class, $updatedAt);
        $this->assertInstanceOf(\DateTimeInterface::class, $updatedAt->toNative());
        $this->assertSame($expectedFormat, $updatedAt->toNative()->format('Y-m-d H:i:s'));
    }

    #[Test]
    #[DataProviderExternal(UpdatedAtDataProvider::class, 'provideValidDateTimeObjects')]
    public function fromNativeWithDateTimeObjectShouldCreateInstance(\DateTimeInterface $value): void
    {
        // Act
        $updatedAt = UpdatedAt::fromNative($value);

        // Assert
        $this->assertInstanceOf(UpdatedAt::class, $updatedAt);
        $this->assertInstanceOf(\DateTimeInterface::class, $updatedAt->toNative());
    }

    #[Test]
    #[DataProviderExternal(UpdatedAtDataProvider::class, 'provideSameValueAsScenarios')]
    public function sameValueAsShouldReturnCorrectResult(string $value1, string $value2, bool $expected): void
    {
        // Arrange
        $updatedAt1 = UpdatedAt::fromNative($value1);
        $updatedAt2 = UpdatedAt::fromNative($value2);

        // Act
        $result = $updatedAt1->sameValueAs($updatedAt2);

        // Assert
        $this->assertSame($expected, $result);
    }

    #[Test]
    public function nowShouldCreateCurrentDateTime(): void
    {
        // Act
        $updatedAt = UpdatedAt::now();

        // Assert
        $this->assertInstanceOf(UpdatedAt::class, $updatedAt);
        $this->assertInstanceOf(\DateTimeInterface::class, $updatedAt->toNative());
    }

    #[Test]
    public function toNativeShouldReturnDateTimeInterface(): void
    {
        // Arrange
        $updatedAt = UpdatedAt::fromNative('2024-01-15 10:30:00');

        // Act
        $result = $updatedAt->toNative();

        // Assert
        $this->assertInstanceOf(\DateTimeInterface::class, $result);
        $this->assertSame('2024-01-15', $result->format('Y-m-d'));
    }

    #[Test]
    public function toStringShouldReturnFormattedDate(): void
    {
        // Arrange
        $updatedAt = UpdatedAt::fromNative('2024-01-15 10:30:00');

        // Act
        $result = (string) $updatedAt;

        // Assert
        $this->assertIsString($result);
        $this->assertStringContainsString('2024', $result);
    }

    #[Test]
    public function getDateShouldReturnDateValueObject(): void
    {
        // Arrange
        $updatedAt = UpdatedAt::fromNative('2024-01-15 10:30:00');

        // Act
        $date = $updatedAt->getDate();

        // Assert
        $this->assertInstanceOf(Date::class, $date);
    }

    #[Test]
    public function getTimeShouldReturnTimeValueObject(): void
    {
        // Arrange
        $updatedAt = UpdatedAt::fromNative('2024-01-15 10:30:00');

        // Act
        $time = $updatedAt->getTime();

        // Assert
        $this->assertInstanceOf(Time::class, $time);
    }

    #[Test]
    public function constructWithDateAndTimeShouldCreateInstance(): void
    {
        // Arrange
        $date = Date::fromNativeDateTime(new \DateTime('2024-01-15'));
        $time = Time::fromNative(10, 30, 0);

        // Act
        $updatedAt = new UpdatedAt($date, $time);

        // Assert
        $this->assertInstanceOf(UpdatedAt::class, $updatedAt);
        $this->assertSame('2024-01-15', $updatedAt->toNative()->format('Y-m-d'));
    }

    #[Test]
    public function constructWithDateOnlyShouldUseZeroTime(): void
    {
        // Arrange
        $date = Date::fromNativeDateTime(new \DateTime('2024-01-15'));

        // Act
        $updatedAt = new UpdatedAt($date);

        // Assert
        $this->assertInstanceOf(UpdatedAt::class, $updatedAt);
        $this->assertSame('00:00:00', $updatedAt->toNative()->format('H:i:s'));
    }
}
