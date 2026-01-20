<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\ValueObject;

use Micro\Article\Domain\ValueObject\Status;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\ValueObject\StatusDataProvider;

/**
 * Unit tests for Status ValueObject.
 */
#[CoversClass(Status::class)]
final class StatusTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(StatusDataProvider::class, 'provideValidStatuses')]
    public function constructWithValidValueShouldCreateInstance(string $value, string $expected): void
    {
        // Act
        $status = Status::fromNative($value);

        // Assert
        $this->assertInstanceOf(Status::class, $status);
        $this->assertSame($expected, $status->toNative());
    }

    #[Test]
    #[DataProviderExternal(StatusDataProvider::class, 'provideInvalidStatuses')]
    public function constructWithInvalidValueShouldThrowException(string $value, string $expectedException): void
    {
        // Assert
        $this->expectException($expectedException);

        // Act
        Status::fromNative($value);
    }

    #[Test]
    #[DataProviderExternal(StatusDataProvider::class, 'provideStatusConstants')]
    public function constantsShouldHaveCorrectValues(string $constant, string $expectedValue): void
    {
        // Act
        $value = constant(Status::class . '::' . $constant);

        // Assert
        $this->assertSame($expectedValue, $value);
    }

    #[Test]
    #[DataProviderExternal(StatusDataProvider::class, 'provideSameValueAsScenarios')]
    public function sameValueAsShouldReturnCorrectResult(string $value, string $otherValue, bool $expected): void
    {
        // Arrange
        $status1 = Status::fromNative($value);
        $status2 = Status::fromNative($otherValue);

        // Act
        $result = $status1->sameValueAs($status2);

        // Assert
        $this->assertSame($expected, $result);
    }

    #[Test]
    public function draftConstantShouldExist(): void
    {
        $this->assertSame('draft', Status::DRAFT);
    }

    #[Test]
    public function publishedConstantShouldExist(): void
    {
        $this->assertSame('published', Status::PUBLISHED);
    }

    #[Test]
    public function archivedConstantShouldExist(): void
    {
        $this->assertSame('archived', Status::ARCHIVED);
    }

    #[Test]
    public function deletedConstantShouldExist(): void
    {
        $this->assertSame('deleted', Status::DELETED);
    }

    #[Test]
    public function toStringShouldReturnValue(): void
    {
        // Arrange
        $status = Status::fromNative('draft');

        // Act
        $result = (string) $status;

        // Assert
        $this->assertSame('draft', $result);
    }

    #[Test]
    public function fromNativeShouldCreateValidDraftStatus(): void
    {
        // Act
        $status = Status::fromNative(Status::DRAFT);

        // Assert
        $this->assertSame('draft', $status->toNative());
    }

    #[Test]
    public function fromNativeShouldCreateValidPublishedStatus(): void
    {
        // Act
        $status = Status::fromNative(Status::PUBLISHED);

        // Assert
        $this->assertSame('published', $status->toNative());
    }

    #[Test]
    public function fromNativeShouldCreateValidArchivedStatus(): void
    {
        // Act
        $status = Status::fromNative(Status::ARCHIVED);

        // Assert
        $this->assertSame('archived', $status->toNative());
    }

    #[Test]
    public function fromNativeShouldCreateValidDeletedStatus(): void
    {
        // Act
        $status = Status::fromNative(Status::DELETED);

        // Assert
        $this->assertSame('deleted', $status->toNative());
    }
}
