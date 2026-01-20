<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\ValueObject;

use Micro\Article\Domain\ValueObject\Active;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\ValueObject\ActiveDataProvider;

/**
 * Unit tests for Active ValueObject.
 */
#[CoversClass(Active::class)]
final class ActiveTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(ActiveDataProvider::class, 'provideValidActiveStates')]
    public function constructWithValidValueShouldCreateInstance(bool $value, bool $expected): void
    {
        // Act
        $active = new Active($value);

        // Assert
        $this->assertInstanceOf(Active::class, $active);
        $this->assertSame($expected, $active->toNative());
    }

    #[Test]
    #[DataProviderExternal(ActiveDataProvider::class, 'provideIsActiveScenarios')]
    public function isActiveShouldReturnCorrectValue(bool $value, bool $expectedIsActive): void
    {
        // Arrange
        $active = new Active($value);

        // Act
        $result = $active->isActive();

        // Assert
        $this->assertSame($expectedIsActive, $result);
    }

    #[Test]
    #[DataProviderExternal(ActiveDataProvider::class, 'provideSameValueAsScenarios')]
    public function sameValueAsShouldReturnCorrectResult(bool $value, bool $otherValue, bool $expected): void
    {
        // Arrange
        $active1 = new Active($value);
        $active2 = new Active($otherValue);

        // Act
        $result = $active1->sameValueAs($active2);

        // Assert
        $this->assertSame($expected, $result);
    }

    #[Test]
    public function toNativeShouldReturnBooleanTrue(): void
    {
        // Arrange
        $active = new Active(true);

        // Act
        $result = $active->toNative();

        // Assert
        $this->assertTrue($result);
        $this->assertIsBool($result);
    }

    #[Test]
    public function toNativeShouldReturnBooleanFalse(): void
    {
        // Arrange
        $active = new Active(false);

        // Act
        $result = $active->toNative();

        // Assert
        $this->assertFalse($result);
        $this->assertIsBool($result);
    }

    #[Test]
    public function fromNativeShouldCreateValidTrueInstance(): void
    {
        // Act
        $active = Active::fromNative(true);

        // Assert
        $this->assertInstanceOf(Active::class, $active);
        $this->assertTrue($active->toNative());
    }

    #[Test]
    public function fromNativeShouldCreateValidFalseInstance(): void
    {
        // Act
        $active = Active::fromNative(false);

        // Assert
        $this->assertInstanceOf(Active::class, $active);
        $this->assertFalse($active->toNative());
    }
}
