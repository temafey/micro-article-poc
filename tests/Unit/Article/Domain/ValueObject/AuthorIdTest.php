<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\ValueObject;

use Micro\Article\Domain\ValueObject\AuthorId;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\ValueObject\AuthorIdDataProvider;

/**
 * Unit tests for AuthorId ValueObject.
 */
#[CoversClass(AuthorId::class)]
final class AuthorIdTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(AuthorIdDataProvider::class, 'provideValidUuids')]
    public function constructWithValidValueShouldCreateInstance(string $value, string $expected): void
    {
        // Act
        $authorId = AuthorId::fromNative($value);

        // Assert
        $this->assertInstanceOf(AuthorId::class, $authorId);
        $this->assertSame($expected, $authorId->toNative());
    }

    #[Test]
    #[DataProviderExternal(AuthorIdDataProvider::class, 'provideInvalidUuids')]
    public function constructWithInvalidValueShouldThrowException(string $value, string $expectedException): void
    {
        // Assert
        $this->expectException($expectedException);

        // Act
        AuthorId::fromNative($value);
    }

    #[Test]
    #[DataProviderExternal(AuthorIdDataProvider::class, 'provideSameValueAsScenarios')]
    public function sameValueAsShouldReturnCorrectResult(string $value, string $otherValue, bool $expected): void
    {
        // Arrange
        $authorId1 = AuthorId::fromNative($value);
        $authorId2 = AuthorId::fromNative($otherValue);

        // Act
        $result = $authorId1->sameValueAs($authorId2);

        // Assert
        $this->assertSame($expected, $result);
    }

    #[Test]
    public function defaultConstructorShouldGenerateUuid(): void
    {
        // Act
        $authorId = new AuthorId();

        // Assert
        $this->assertInstanceOf(AuthorId::class, $authorId);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $authorId->toNative()
        );
    }

    #[Test]
    public function defaultConstructorShouldGenerateUniqueValues(): void
    {
        // Act
        $authorId1 = new AuthorId();
        $authorId2 = new AuthorId();

        // Assert
        $this->assertNotSame($authorId1->toNative(), $authorId2->toNative());
    }

    #[Test]
    public function toStringShouldReturnValue(): void
    {
        // Arrange
        $value = '550e8400-e29b-41d4-a716-446655440000';
        $authorId = AuthorId::fromNative($value);

        // Act
        $result = (string) $authorId;

        // Assert
        $this->assertSame($value, $result);
    }

    #[Test]
    public function toNativeShouldReturnOriginalValue(): void
    {
        // Arrange
        $value = '550e8400-e29b-41d4-a716-446655440000';
        $authorId = AuthorId::fromNative($value);

        // Act
        $result = $authorId->toNative();

        // Assert
        $this->assertSame($value, $result);
    }

    #[Test]
    public function isEmptyShouldReturnFalseForValidUuid(): void
    {
        // Arrange
        $authorId = AuthorId::fromNative('550e8400-e29b-41d4-a716-446655440000');

        // Act
        $result = $authorId->isEmpty();

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function nilUuidShouldBeValid(): void
    {
        // Arrange
        $nilValue = '00000000-0000-0000-0000-000000000000';

        // Act
        $authorId = AuthorId::fromNative($nilValue);

        // Assert
        $this->assertSame($nilValue, $authorId->toNative());
    }
}
