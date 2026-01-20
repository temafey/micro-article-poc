<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\ValueObject;

use Micro\Article\Domain\ValueObject\Body;
use MicroModule\ValueObject\Exception\InvalidNativeArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\ValueObject\BodyDataProvider;

/**
 * Unit tests for Body ValueObject.
 */
#[CoversClass(Body::class)]
final class BodyTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(BodyDataProvider::class, 'provideValidBodies')]
    public function constructWithValidValueShouldCreateInstance(string $value, string $expected): void
    {
        // Act
        $body = new Body($value);

        // Assert
        $this->assertInstanceOf(Body::class, $body);
        $this->assertSame($expected, $body->toNative());
    }

    #[Test]
    #[DataProviderExternal(BodyDataProvider::class, 'provideInvalidBodies')]
    public function constructWithInvalidValueShouldThrowException(string $value, string $expectedException): void
    {
        // Assert
        $this->expectException($expectedException);

        // Act
        new Body($value);
    }

    #[Test]
    #[DataProviderExternal(BodyDataProvider::class, 'provideBoundaryLengths')]
    public function constructWithBoundaryLengthShouldBehaveCorrectly(int $length, bool $shouldPass): void
    {
        $value = str_repeat('a', $length);

        if ($shouldPass) {
            $body = new Body($value);
            $this->assertSame($value, $body->toNative());
        } else {
            $this->expectException(InvalidNativeArgumentException::class);
            new Body($value);
        }
    }

    #[Test]
    public function fromNativeShouldCreateValidInstance(): void
    {
        // Arrange
        $value = 'This is a valid body content with enough characters.';

        // Act
        $body = Body::fromNative($value);

        // Assert
        $this->assertInstanceOf(Body::class, $body);
        $this->assertSame($value, $body->toNative());
    }

    #[Test]
    public function toStringShouldReturnValue(): void
    {
        // Arrange
        $value = 'Body content text here with enough characters.';
        $body = new Body($value);

        // Act
        $result = (string) $body;

        // Assert
        $this->assertSame($value, $result);
    }

    #[Test]
    public function sameValueAsShouldReturnTrueForEqualBodies(): void
    {
        // Arrange
        $value = 'Same body content with enough characters for testing.';
        $body1 = new Body($value);
        $body2 = new Body($value);

        // Act
        $result = $body1->sameValueAs($body2);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function sameValueAsShouldReturnFalseForDifferentBodies(): void
    {
        // Arrange
        $body1 = new Body('First body content with enough characters.');
        $body2 = new Body('Second body content with enough characters.');

        // Act
        $result = $body1->sameValueAs($body2);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function isEmptyShouldReturnFalseForValidBody(): void
    {
        // Arrange
        $body = new Body('Valid body content.');

        // Act
        $result = $body->isEmpty();

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function validateWithValueObjectShouldExtractNativeValue(): void
    {
        // Arrange - create a valid Body instance
        $existingBody = new Body('This is a valid body content with enough characters.');
        $body = new Body('Another valid body content.');

        // Act - call validate() with ValueObject to cover the instanceof branch
        $body->validate($existingBody);

        // Assert - no exception means validation passed
        $this->assertTrue(true);
    }

    #[Test]
    public function validateWithInvalidValueObjectShouldThrowException(): void
    {
        // Arrange
        $body = new Body('Valid body content.');
        // Create a mock ValueObject that returns too short value
        $mockValueObject = $this->createMock(\MicroModule\ValueObject\ValueObjectInterface::class);
        $mockValueObject->method('toNative')->willReturn('short');

        // Assert
        $this->expectException(InvalidNativeArgumentException::class);

        // Act
        $body->validate($mockValueObject);
    }
}
