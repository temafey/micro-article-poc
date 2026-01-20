<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\ValueObject;

use Micro\Article\Domain\ValueObject\PublishedAt;
use MicroModule\ValueObject\DateTime\Date;
use MicroModule\ValueObject\DateTime\Time;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\ValueObject\PublishedAtDataProvider;

/**
 * Unit tests for PublishedAt ValueObject.
 */
#[CoversClass(PublishedAt::class)]
final class PublishedAtTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(PublishedAtDataProvider::class, 'provideValidDateStrings')]
    public function fromNativeWithValidStringShouldCreateInstance(string $value, string $expectedFormat): void
    {
        // Act
        $publishedAt = PublishedAt::fromNative($value);

        // Assert
        $this->assertInstanceOf(PublishedAt::class, $publishedAt);
        $this->assertInstanceOf(\DateTimeInterface::class, $publishedAt->toNative());
        $this->assertSame($expectedFormat, $publishedAt->toNative()->format('Y-m-d H:i:s'));
    }

    #[Test]
    #[DataProviderExternal(PublishedAtDataProvider::class, 'provideValidDateTimeObjects')]
    public function fromNativeWithDateTimeObjectShouldCreateInstance(\DateTimeInterface $value): void
    {
        // Act
        $publishedAt = PublishedAt::fromNative($value);

        // Assert
        $this->assertInstanceOf(PublishedAt::class, $publishedAt);
        $this->assertInstanceOf(\DateTimeInterface::class, $publishedAt->toNative());
    }

    #[Test]
    #[DataProviderExternal(PublishedAtDataProvider::class, 'provideSameValueAsScenarios')]
    public function sameValueAsShouldReturnCorrectResult(string $value1, string $value2, bool $expected): void
    {
        // Arrange
        $publishedAt1 = PublishedAt::fromNative($value1);
        $publishedAt2 = PublishedAt::fromNative($value2);

        // Act
        $result = $publishedAt1->sameValueAs($publishedAt2);

        // Assert
        $this->assertSame($expected, $result);
    }

    #[Test]
    public function nowShouldCreateCurrentDateTime(): void
    {
        // Act
        $publishedAt = PublishedAt::now();

        // Assert
        $this->assertInstanceOf(PublishedAt::class, $publishedAt);
        $this->assertInstanceOf(\DateTimeInterface::class, $publishedAt->toNative());
    }

    #[Test]
    public function toNativeShouldReturnDateTimeInterface(): void
    {
        // Arrange
        $publishedAt = PublishedAt::fromNative('2024-01-15 10:30:00');

        // Act
        $result = $publishedAt->toNative();

        // Assert
        $this->assertInstanceOf(\DateTimeInterface::class, $result);
        $this->assertSame('2024-01-15', $result->format('Y-m-d'));
    }

    #[Test]
    public function toStringShouldReturnFormattedDate(): void
    {
        // Arrange
        $publishedAt = PublishedAt::fromNative('2024-01-15 10:30:00');

        // Act
        $result = (string) $publishedAt;

        // Assert
        $this->assertIsString($result);
        $this->assertStringContainsString('2024', $result);
    }

    #[Test]
    public function getDateShouldReturnDateValueObject(): void
    {
        // Arrange
        $publishedAt = PublishedAt::fromNative('2024-01-15 10:30:00');

        // Act
        $date = $publishedAt->getDate();

        // Assert
        $this->assertInstanceOf(Date::class, $date);
    }

    #[Test]
    public function getTimeShouldReturnTimeValueObject(): void
    {
        // Arrange
        $publishedAt = PublishedAt::fromNative('2024-01-15 10:30:00');

        // Act
        $time = $publishedAt->getTime();

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
        $publishedAt = new PublishedAt($date, $time);

        // Assert
        $this->assertInstanceOf(PublishedAt::class, $publishedAt);
        $this->assertSame('2024-01-15', $publishedAt->toNative()->format('Y-m-d'));
    }

    #[Test]
    public function constructWithDateOnlyShouldUseZeroTime(): void
    {
        // Arrange
        $date = Date::fromNativeDateTime(new \DateTime('2024-01-15'));

        // Act
        $publishedAt = new PublishedAt($date);

        // Assert
        $this->assertInstanceOf(PublishedAt::class, $publishedAt);
        $this->assertSame('00:00:00', $publishedAt->toNative()->format('H:i:s'));
    }
}
