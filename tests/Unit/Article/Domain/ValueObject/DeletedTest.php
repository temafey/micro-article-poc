<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\ValueObject;

use Micro\Article\Domain\ValueObject\Deleted;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\ValueObject\DeletedDataProvider;

/**
 * Unit tests for Deleted ValueObject.
 */
#[CoversClass(Deleted::class)]
final class DeletedTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(DeletedDataProvider::class, 'provideValidDeletedStates')]
    public function constructWithValidValueShouldCreateInstance(bool $value, bool $expected): void
    {
        // Act
        $deleted = new Deleted($value);

        // Assert
        $this->assertInstanceOf(Deleted::class, $deleted);
        $this->assertSame($expected, $deleted->toNative());
    }

    #[Test]
    #[DataProviderExternal(DeletedDataProvider::class, 'provideIsDeletedScenarios')]
    public function isDeletedShouldReturnCorrectValue(bool $value, bool $expectedIsDeleted): void
    {
        // Arrange
        $deleted = new Deleted($value);

        // Act
        $result = $deleted->isDeleted();

        // Assert
        $this->assertSame($expectedIsDeleted, $result);
    }

    #[Test]
    #[DataProviderExternal(DeletedDataProvider::class, 'provideSameValueAsScenarios')]
    public function sameValueAsShouldReturnCorrectResult(bool $value, bool $otherValue, bool $expected): void
    {
        // Arrange
        $deleted1 = new Deleted($value);
        $deleted2 = new Deleted($otherValue);

        // Act
        $result = $deleted1->sameValueAs($deleted2);

        // Assert
        $this->assertSame($expected, $result);
    }

    #[Test]
    public function deletedFactoryMethodShouldCreateDeletedInstance(): void
    {
        // Act
        $deleted = Deleted::deleted();

        // Assert
        $this->assertInstanceOf(Deleted::class, $deleted);
        $this->assertTrue($deleted->isDeleted());
        $this->assertTrue($deleted->toNative());
    }

    #[Test]
    public function notDeletedFactoryMethodShouldCreateNotDeletedInstance(): void
    {
        // Act
        $deleted = Deleted::notDeleted();

        // Assert
        $this->assertInstanceOf(Deleted::class, $deleted);
        $this->assertFalse($deleted->isDeleted());
        $this->assertFalse($deleted->toNative());
    }

    #[Test]
    public function toNativeShouldReturnBooleanTrue(): void
    {
        // Arrange
        $deleted = new Deleted(true);

        // Act
        $result = $deleted->toNative();

        // Assert
        $this->assertTrue($result);
        $this->assertIsBool($result);
    }

    #[Test]
    public function toNativeShouldReturnBooleanFalse(): void
    {
        // Arrange
        $deleted = new Deleted(false);

        // Act
        $result = $deleted->toNative();

        // Assert
        $this->assertFalse($result);
        $this->assertIsBool($result);
    }

    #[Test]
    public function fromNativeShouldCreateValidTrueInstance(): void
    {
        // Act
        $deleted = Deleted::fromNative(true);

        // Assert
        $this->assertInstanceOf(Deleted::class, $deleted);
        $this->assertTrue($deleted->toNative());
    }

    #[Test]
    public function fromNativeShouldCreateValidFalseInstance(): void
    {
        // Act
        $deleted = Deleted::fromNative(false);

        // Assert
        $this->assertInstanceOf(Deleted::class, $deleted);
        $this->assertFalse($deleted->toNative());
    }
}
