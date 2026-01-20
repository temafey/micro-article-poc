<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\ValueObject;

use Micro\Article\Domain\ValueObject\FindCriteria;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\ValueObject\FindCriteriaDataProvider;

/**
 * Unit tests for FindCriteria ValueObject.
 */
#[CoversClass(FindCriteria::class)]
final class FindCriteriaTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(FindCriteriaDataProvider::class, 'provideValidCriteria')]
    public function fromNativeWithValidCriteriaShouldCreateInstance(array $criteria): void
    {
        // Act
        $findCriteria = FindCriteria::fromNative($criteria);

        // Assert
        $this->assertInstanceOf(FindCriteria::class, $findCriteria);
    }

    #[Test]
    #[DataProviderExternal(FindCriteriaDataProvider::class, 'provideFromNativeScenarios')]
    public function fromNativeShouldPreserveCriteria(array $criteria): void
    {
        // Act
        $findCriteria = FindCriteria::fromNative($criteria);
        $result = $findCriteria->toNative();

        // Assert
        $this->assertSame($criteria, $result);
    }

    #[Test]
    public function toNativeShouldReturnOriginalCriteria(): void
    {
        // Arrange
        $criteria = [
            'status' => 'published',
            'event_id' => 12345,
        ];
        $findCriteria = FindCriteria::fromNative($criteria);

        // Act
        $result = $findCriteria->toNative();

        // Assert
        $this->assertSame($criteria, $result);
    }

    #[Test]
    public function emptyFindCriteriaShouldReturnEmptyArray(): void
    {
        // Arrange
        $findCriteria = FindCriteria::fromNative([]);

        // Act
        $result = $findCriteria->toNative();

        // Assert
        $this->assertSame([], $result);
    }

    #[Test]
    public function sameValueAsShouldReturnTrueForEqualCriteria(): void
    {
        // Arrange
        $criteria = [
            'status' => 'published',
        ];
        $findCriteria1 = FindCriteria::fromNative($criteria);
        $findCriteria2 = FindCriteria::fromNative($criteria);

        // Act
        $result = $findCriteria1->sameValueAs($findCriteria2);

        // Assert
        $this->assertTrue($result);
    }

    #[Test]
    public function sameValueAsShouldReturnFalseForDifferentCriteria(): void
    {
        // Arrange
        $findCriteria1 = FindCriteria::fromNative([
            'status' => 'published',
        ]);
        $findCriteria2 = FindCriteria::fromNative([
            'status' => 'draft',
        ]);

        // Act
        $result = $findCriteria1->sameValueAs($findCriteria2);

        // Assert
        $this->assertFalse($result);
    }
}
