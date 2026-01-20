<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\ValueObject;

use Micro\Article\Domain\ValueObject\ArchivedAt;
use MicroModule\ValueObject\DateTime\Date;
use MicroModule\ValueObject\DateTime\Time;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\ValueObject\ArchivedAtDataProvider;

/**
 * Unit tests for ArchivedAt ValueObject.
 */
#[CoversClass(ArchivedAt::class)]
final class ArchivedAtTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(ArchivedAtDataProvider::class, 'provideValidDateStrings')]
    public function fromNativeWithValidStringShouldCreateInstance(string $value, string $expectedFormat): void
    {
        // Act
        $archivedAt = ArchivedAt::fromNative($value);

        // Assert
        $this->assertInstanceOf(ArchivedAt::class, $archivedAt);
        $this->assertInstanceOf(\DateTimeInterface::class, $archivedAt->toNative());
        $this->assertSame($expectedFormat, $archivedAt->toNative()->format('Y-m-d H:i:s'));
    }

    #[Test]
    #[DataProviderExternal(ArchivedAtDataProvider::class, 'provideValidDateTimeObjects')]
    public function fromNativeWithDateTimeObjectShouldCreateInstance(\DateTimeInterface $value): void
    {
        // Act
        $archivedAt = ArchivedAt::fromNative($value);

        // Assert
        $this->assertInstanceOf(ArchivedAt::class, $archivedAt);
        $this->assertInstanceOf(\DateTimeInterface::class, $archivedAt->toNative());
    }

    #[Test]
    #[DataProviderExternal(ArchivedAtDataProvider::class, 'provideSameValueAsScenarios')]
    public function sameValueAsShouldReturnCorrectResult(string $value1, string $value2, bool $expected): void
    {
        // Arrange
        $archivedAt1 = ArchivedAt::fromNative($value1);
        $archivedAt2 = ArchivedAt::fromNative($value2);

        // Act
        $result = $archivedAt1->sameValueAs($archivedAt2);

        // Assert
        $this->assertSame($expected, $result);
    }

    #[Test]
    public function nowShouldCreateCurrentDateTime(): void
    {
        // Act
        $archivedAt = ArchivedAt::now();

        // Assert
        $this->assertInstanceOf(ArchivedAt::class, $archivedAt);
        $this->assertInstanceOf(\DateTimeInterface::class, $archivedAt->toNative());
    }

    #[Test]
    public function toNativeShouldReturnDateTimeInterface(): void
    {
        // Arrange
        $archivedAt = ArchivedAt::fromNative('2024-01-15 10:30:00');

        // Act
        $result = $archivedAt->toNative();

        // Assert
        $this->assertInstanceOf(\DateTimeInterface::class, $result);
        $this->assertSame('2024-01-15', $result->format('Y-m-d'));
    }

    #[Test]
    public function toStringShouldReturnFormattedDate(): void
    {
        // Arrange
        $archivedAt = ArchivedAt::fromNative('2024-01-15 10:30:00');

        // Act
        $result = (string) $archivedAt;

        // Assert
        $this->assertIsString($result);
        $this->assertStringContainsString('2024', $result);
    }

    #[Test]
    public function getDateShouldReturnDateValueObject(): void
    {
        // Arrange
        $archivedAt = ArchivedAt::fromNative('2024-01-15 10:30:00');

        // Act
        $date = $archivedAt->getDate();

        // Assert
        $this->assertInstanceOf(Date::class, $date);
    }

    #[Test]
    public function getTimeShouldReturnTimeValueObject(): void
    {
        // Arrange
        $archivedAt = ArchivedAt::fromNative('2024-01-15 10:30:00');

        // Act
        $time = $archivedAt->getTime();

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
        $archivedAt = new ArchivedAt($date, $time);

        // Assert
        $this->assertInstanceOf(ArchivedAt::class, $archivedAt);
        $this->assertSame('2024-01-15', $archivedAt->toNative()->format('Y-m-d'));
    }

    #[Test]
    public function constructWithDateOnlyShouldUseZeroTime(): void
    {
        // Arrange
        $date = Date::fromNativeDateTime(new \DateTime('2024-01-15'));

        // Act
        $archivedAt = new ArchivedAt($date);

        // Assert
        $this->assertInstanceOf(ArchivedAt::class, $archivedAt);
        $this->assertSame('00:00:00', $archivedAt->toNative()->format('H:i:s'));
    }
}
