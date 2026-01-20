<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\ValueObject;

use Micro\Article\Domain\ValueObject\CreatedAt;
use MicroModule\ValueObject\DateTime\Date;
use MicroModule\ValueObject\DateTime\Time;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\ValueObject\CreatedAtDataProvider;

/**
 * Unit tests for CreatedAt ValueObject.
 */
#[CoversClass(CreatedAt::class)]
final class CreatedAtTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(CreatedAtDataProvider::class, 'provideValidDateStrings')]
    public function fromNativeWithValidStringShouldCreateInstance(string $value, string $expectedFormat): void
    {
        // Act
        $createdAt = CreatedAt::fromNative($value);

        // Assert
        $this->assertInstanceOf(CreatedAt::class, $createdAt);
        $this->assertInstanceOf(\DateTimeInterface::class, $createdAt->toNative());
        $this->assertSame($expectedFormat, $createdAt->toNative()->format('Y-m-d H:i:s'));
    }

    #[Test]
    #[DataProviderExternal(CreatedAtDataProvider::class, 'provideValidDateTimeObjects')]
    public function fromNativeWithDateTimeObjectShouldCreateInstance(\DateTimeInterface $value): void
    {
        // Act
        $createdAt = CreatedAt::fromNative($value);

        // Assert
        $this->assertInstanceOf(CreatedAt::class, $createdAt);
        $this->assertInstanceOf(\DateTimeInterface::class, $createdAt->toNative());
    }

    #[Test]
    #[DataProviderExternal(CreatedAtDataProvider::class, 'provideSameValueAsScenarios')]
    public function sameValueAsShouldReturnCorrectResult(string $value1, string $value2, bool $expected): void
    {
        // Arrange
        $createdAt1 = CreatedAt::fromNative($value1);
        $createdAt2 = CreatedAt::fromNative($value2);

        // Act
        $result = $createdAt1->sameValueAs($createdAt2);

        // Assert
        $this->assertSame($expected, $result);
    }

    #[Test]
    public function nowShouldCreateCurrentDateTime(): void
    {
        // Act
        $createdAt = CreatedAt::now();

        // Assert
        $this->assertInstanceOf(CreatedAt::class, $createdAt);
        $this->assertInstanceOf(\DateTimeInterface::class, $createdAt->toNative());
    }

    #[Test]
    public function toNativeShouldReturnDateTimeInterface(): void
    {
        // Arrange
        $createdAt = CreatedAt::fromNative('2024-01-15 10:30:00');

        // Act
        $result = $createdAt->toNative();

        // Assert
        $this->assertInstanceOf(\DateTimeInterface::class, $result);
        $this->assertSame('2024-01-15', $result->format('Y-m-d'));
    }

    #[Test]
    public function toStringShouldReturnFormattedDate(): void
    {
        // Arrange
        $createdAt = CreatedAt::fromNative('2024-01-15 10:30:00');

        // Act
        $result = (string) $createdAt;

        // Assert
        $this->assertIsString($result);
        $this->assertStringContainsString('2024', $result);
    }

    #[Test]
    public function getDateShouldReturnDateValueObject(): void
    {
        // Arrange
        $createdAt = CreatedAt::fromNative('2024-01-15 10:30:00');

        // Act
        $date = $createdAt->getDate();

        // Assert
        $this->assertInstanceOf(Date::class, $date);
    }

    #[Test]
    public function getTimeShouldReturnTimeValueObject(): void
    {
        // Arrange
        $createdAt = CreatedAt::fromNative('2024-01-15 10:30:00');

        // Act
        $time = $createdAt->getTime();

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
        $createdAt = new CreatedAt($date, $time);

        // Assert
        $this->assertInstanceOf(CreatedAt::class, $createdAt);
        $this->assertSame('2024-01-15', $createdAt->toNative()->format('Y-m-d'));
    }

    #[Test]
    public function constructWithDateOnlyShouldUseZeroTime(): void
    {
        // Arrange
        $date = Date::fromNativeDateTime(new \DateTime('2024-01-15'));

        // Act
        $createdAt = new CreatedAt($date);

        // Assert
        $this->assertInstanceOf(CreatedAt::class, $createdAt);
        $this->assertSame('00:00:00', $createdAt->toNative()->format('H:i:s'));
    }
}
