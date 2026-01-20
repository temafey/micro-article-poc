<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\ValueObject;

use Micro\Article\Domain\ValueObject\Slug;
use MicroModule\ValueObject\Exception\InvalidNativeArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\ValueObject\SlugDataProvider;

/**
 * Unit tests for Slug ValueObject.
 */
#[CoversClass(Slug::class)]
final class SlugTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(SlugDataProvider::class, 'provideValidSlugs')]
    public function constructWithValidValueShouldCreateInstance(string $value, string $expected): void
    {
        // Act
        $slug = new Slug($value);

        // Assert
        $this->assertInstanceOf(Slug::class, $slug);
        $this->assertSame($expected, $slug->toNative());
    }

    #[Test]
    #[DataProviderExternal(SlugDataProvider::class, 'provideInvalidSlugs')]
    public function constructWithInvalidValueShouldThrowException(string $value, string $expectedException): void
    {
        // Assert
        $this->expectException($expectedException);

        // Act
        new Slug($value);
    }

    #[Test]
    #[DataProviderExternal(SlugDataProvider::class, 'provideBoundaryLengths')]
    public function constructWithBoundaryLengthShouldBehaveCorrectly(int $length, bool $shouldPass): void
    {
        $value = str_repeat('a', $length);

        if ($shouldPass) {
            $slug = new Slug($value);
            $this->assertSame($value, $slug->toNative());
        } else {
            $this->expectException(InvalidNativeArgumentException::class);
            new Slug($value);
        }
    }

    #[Test]
    #[DataProviderExternal(SlugDataProvider::class, 'provideUrlSafeScenarios')]
    public function urlSafeSlugShouldBeValid(string $value, bool $isUrlSafe): void
    {
        // Act
        $slug = new Slug($value);

        // Assert
        $this->assertTrue($isUrlSafe);
        $this->assertMatchesRegularExpression('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug->toNative());
    }

    #[Test]
    public function fromNativeShouldCreateValidInstance(): void
    {
        // Arrange
        $value = 'test-slug-value';

        // Act
        $slug = Slug::fromNative($value);

        // Assert
        $this->assertInstanceOf(Slug::class, $slug);
        $this->assertSame($value, $slug->toNative());
    }

    #[Test]
    public function toStringShouldReturnValue(): void
    {
        // Arrange
        $value = 'test-slug';
        $slug = new Slug($value);

        // Act
        $result = (string) $slug;

        // Assert
        $this->assertSame($value, $result);
    }

    #[Test]
    public function sameValueAsShouldReturnTrueForEqualSlugs(): void
    {
        // Arrange
        $slug1 = new Slug('same-slug');
        $slug2 = new Slug('same-slug');

        // Act
        $result = $slug1->sameValueAs($slug2);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function sameValueAsShouldReturnFalseForDifferentSlugs(): void
    {
        // Arrange
        $slug1 = new Slug('first-slug');
        $slug2 = new Slug('second-slug');

        // Act
        $result = $slug1->sameValueAs($slug2);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function isEmptyShouldReturnFalseForValidSlug(): void
    {
        // Arrange
        $slug = new Slug('valid-slug');

        // Act
        $result = $slug->isEmpty();

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function validateWithValueObjectShouldExtractNativeValue(): void
    {
        // Arrange - create valid Slug instances
        $existingSlug = new Slug('existing-slug');
        $slug = new Slug('another-slug');

        // Act - call validate() with ValueObject to cover the instanceof branch
        $slug->validate($existingSlug);

        // Assert - no exception means validation passed
        $this->assertTrue(true);
    }

    #[Test]
    public function validateWithInvalidValueObjectShouldThrowException(): void
    {
        // Arrange
        $slug = new Slug('valid-slug');
        // Create a mock ValueObject that returns invalid slug value
        $mockValueObject = $this->createMock(\MicroModule\ValueObject\ValueObjectInterface::class);
        $mockValueObject->method('toNative')->willReturn('INVALID SLUG!');

        // Assert
        $this->expectException(InvalidNativeArgumentException::class);

        // Act
        $slug->validate($mockValueObject);
    }
}
