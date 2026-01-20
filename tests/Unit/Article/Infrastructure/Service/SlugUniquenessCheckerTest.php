<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Infrastructure\Service;

use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use Micro\Article\Domain\Repository\Query\ArticleRepositoryInterface;
use Micro\Article\Infrastructure\Service\SlugUniquenessChecker;
use MicroModule\Base\Domain\ValueObject\FindCriteria;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Infrastructure\Service\SlugUniquenessCheckerDataProvider;

/**
 * Unit tests for SlugUniquenessChecker.
 */
#[CoversClass(SlugUniquenessChecker::class)]
final class SlugUniquenessCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private SlugUniquenessChecker $checker;
    private ArticleRepositoryInterface&Mockery\MockInterface $articleRepositoryMock;

    protected function setUp(): void
    {
        $this->articleRepositoryMock = \Mockery::mock(ArticleRepositoryInterface::class);
        $this->checker = new SlugUniquenessChecker($this->articleRepositoryMock);
    }

    #[Test]
    #[DataProviderExternal(SlugUniquenessCheckerDataProvider::class, 'slugExistsFoundScenarios')]
    public function slugExistsShouldReturnTrueWhenSlugFound(
        string $slug,
        ?string $excludeUuid,
        string $foundUuid,
        bool $expectedResult,
    ): void {
        // Arrange
        $uuidMock = \Mockery::mock(Uuid::class);
        $uuidMock->shouldReceive('toNative')
            ->andReturn($foundUuid);

        $readModelMock = \Mockery::mock(ArticleReadModelInterface::class);
        $readModelMock->shouldReceive('getUuid')
            ->andReturn($uuidMock);

        $this->articleRepositoryMock
            ->shouldReceive('findOneBy')
            ->once()
            ->with(\Mockery::on(function (FindCriteria $criteria) use ($slug) {
                return $criteria->toNative() === [
                    'slug' => $slug,
                ];
            }))
            ->andReturn($readModelMock);

        // Act
        $result = $this->checker->slugExists($slug, $excludeUuid);

        // Assert
        $this->assertSame($expectedResult, $result);
    }

    #[Test]
    #[DataProviderExternal(SlugUniquenessCheckerDataProvider::class, 'slugNotFoundScenarios')]
    public function slugExistsShouldReturnFalseWhenSlugNotFound(
        string $slug,
        ?string $excludeUuid,
        bool $expectedResult,
    ): void {
        // Arrange
        $this->articleRepositoryMock
            ->shouldReceive('findOneBy')
            ->once()
            ->with(\Mockery::on(function (FindCriteria $criteria) use ($slug) {
                return $criteria->toNative() === [
                    'slug' => $slug,
                ];
            }))
            ->andReturn(null);

        // Act
        $result = $this->checker->slugExists($slug, $excludeUuid);

        // Assert
        $this->assertSame($expectedResult, $result);
    }

    #[Test]
    #[DataProviderExternal(SlugUniquenessCheckerDataProvider::class, 'slugExistsSameUuidExcludedScenarios')]
    public function slugExistsShouldReturnFalseWhenSameUuidExcluded(
        string $slug,
        ?string $excludeUuid,
        string $foundUuid,
        bool $expectedResult,
    ): void {
        // Arrange
        $uuidMock = \Mockery::mock(Uuid::class);
        $uuidMock->shouldReceive('toNative')
            ->andReturn($foundUuid);

        $readModelMock = \Mockery::mock(ArticleReadModelInterface::class);
        $readModelMock->shouldReceive('getUuid')
            ->andReturn($uuidMock);

        $this->articleRepositoryMock
            ->shouldReceive('findOneBy')
            ->once()
            ->with(\Mockery::on(function (FindCriteria $criteria) use ($slug) {
                return $criteria->toNative() === [
                    'slug' => $slug,
                ];
            }))
            ->andReturn($readModelMock);

        // Act
        $result = $this->checker->slugExists($slug, $excludeUuid);

        // Assert
        $this->assertSame($expectedResult, $result);
    }

    #[Test]
    public function constructorShouldAcceptDependencies(): void
    {
        // Arrange & Act
        $checker = new SlugUniquenessChecker($this->articleRepositoryMock);

        // Assert
        $this->assertInstanceOf(SlugUniquenessChecker::class, $checker);
    }
}
