<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\Service;

use Cocur\Slugify\SlugifyInterface;
use Micro\Article\Domain\Service\ArticleSlugGeneratorService;
use Micro\Article\Domain\Service\ArticleSlugGeneratorServiceInterface;
use Micro\Article\Domain\Service\SlugUniquenessCheckerInterface;
use MicroModule\Base\Domain\Exception\InvalidDataException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\Service\ArticleSlugGeneratorServiceDataProvider;

/**
 * Unit tests for ArticleSlugGeneratorService.
 */
#[CoversClass(ArticleSlugGeneratorService::class)]
final class ArticleSlugGeneratorServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ArticleSlugGeneratorService $service;
    private SlugifyInterface&Mockery\MockInterface $slugifyMock;
    private SlugUniquenessCheckerInterface&Mockery\MockInterface $uniquenessCheckerMock;

    protected function setUp(): void
    {
        $this->slugifyMock = \Mockery::mock(SlugifyInterface::class);
        $this->uniquenessCheckerMock = \Mockery::mock(SlugUniquenessCheckerInterface::class);

        $this->service = new ArticleSlugGeneratorService($this->slugifyMock, $this->uniquenessCheckerMock);
    }

    #[Test]
    #[DataProviderExternal(ArticleSlugGeneratorServiceDataProvider::class, 'provideValidTitlesForSlugGeneration')]
    public function generateSlugWithValidTitleShouldReturnSlug(string $title, string $expectedSlug): void
    {
        // Arrange
        $this->slugifyMock
            ->shouldReceive('slugify')
            ->with($title)
            ->andReturn($expectedSlug);

        $this->uniquenessCheckerMock
            ->shouldReceive('slugExists')
            ->with($expectedSlug, null)
            ->andReturn(false);

        // Act
        $result = $this->service->generateSlug($title);

        // Assert
        $this->assertSame($expectedSlug, $result);
    }

    #[Test]
    #[DataProviderExternal(ArticleSlugGeneratorServiceDataProvider::class, 'provideInvalidTitlesForSlugGeneration')]
    public function generateSlugWithInvalidTitleShouldThrowException(string $title): void
    {
        // Assert
        $this->expectException(InvalidDataException::class);

        // Act
        $this->service->generateSlug($title);
    }

    #[Test]
    public function generateSlugWithCollisionShouldAppendCounter(): void
    {
        // Arrange
        $title = 'Test Title';
        $baseSlug = 'test-title';

        $this->slugifyMock
            ->shouldReceive('slugify')
            ->with($title)
            ->andReturn($baseSlug);

        $this->uniquenessCheckerMock
            ->shouldReceive('slugExists')
            ->with($baseSlug, null)
            ->andReturn(true);

        $this->uniquenessCheckerMock
            ->shouldReceive('slugExists')
            ->with('test-title-1', null)
            ->andReturn(false);

        // Act
        $result = $this->service->generateSlug($title);

        // Assert
        $this->assertSame('test-title-1', $result);
    }

    #[Test]
    public function generateSlugWithMultipleCollisionsShouldIncrementCounter(): void
    {
        // Arrange
        $title = 'Test Title';
        $baseSlug = 'test-title';

        $this->slugifyMock
            ->shouldReceive('slugify')
            ->with($title)
            ->andReturn($baseSlug);

        $this->uniquenessCheckerMock
            ->shouldReceive('slugExists')
            ->with($baseSlug, null)
            ->andReturn(true);

        $this->uniquenessCheckerMock
            ->shouldReceive('slugExists')
            ->with('test-title-1', null)
            ->andReturn(true);

        $this->uniquenessCheckerMock
            ->shouldReceive('slugExists')
            ->with('test-title-2', null)
            ->andReturn(false);

        // Act
        $result = $this->service->generateSlug($title);

        // Assert
        $this->assertSame('test-title-2', $result);
    }

    #[Test]
    public function generateSlugWithExcludeUuidShouldExcludeFromCheck(): void
    {
        // Arrange
        $title = 'Test Title';
        $baseSlug = 'test-title';
        $excludeUuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->slugifyMock
            ->shouldReceive('slugify')
            ->with($title)
            ->andReturn($baseSlug);

        $this->uniquenessCheckerMock
            ->shouldReceive('slugExists')
            ->with($baseSlug, $excludeUuid)
            ->andReturn(false);

        // Act
        $result = $this->service->generateSlug($title, null, $excludeUuid);

        // Assert
        $this->assertSame($baseSlug, $result);
    }

    #[Test]
    public function generateSlugWithMatchingExistingSlugShouldPreserve(): void
    {
        // Arrange
        $title = 'Test Title';
        $existingSlug = 'test-title';
        $excludeUuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->slugifyMock
            ->shouldReceive('slugify')
            ->with($title)
            ->andReturn($existingSlug);

        $this->uniquenessCheckerMock
            ->shouldReceive('slugExists')
            ->with($existingSlug, $excludeUuid)
            ->andReturn(false);

        // Act
        $result = $this->service->generateSlug($title, $existingSlug, $excludeUuid);

        // Assert
        $this->assertSame($existingSlug, $result);
    }

    #[Test]
    #[DataProviderExternal(ArticleSlugGeneratorServiceDataProvider::class, 'provideSlugFormatValidation')]
    public function validateSlugFormatShouldReturnExpectedResult(string $slug, bool $expected): void
    {
        // Act
        $result = $this->service->validateSlugFormat($slug);

        // Assert
        $this->assertSame($expected, $result);
    }

    #[Test]
    public function generateSlugWithMaxCollisionsShouldThrowException(): void
    {
        // Arrange
        $title = 'Test Title';
        $baseSlug = 'test-title';

        $this->slugifyMock
            ->shouldReceive('slugify')
            ->with($title)
            ->andReturn($baseSlug);

        // All slugs exist (base + 1 through 10)
        $this->uniquenessCheckerMock
            ->shouldReceive('slugExists')
            ->andReturn(true);

        // Assert
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage('Unable to generate unique slug');

        // Act
        $this->service->generateSlug($title);
    }

    #[Test]
    public function serviceShouldImplementInterface(): void
    {
        // Assert
        $this->assertInstanceOf(ArticleSlugGeneratorServiceInterface::class, $this->service);
    }

    #[Test]
    public function generateSlugWithSpecialCharactersOnlyTitleShouldThrowException(): void
    {
        // Arrange - title that produces empty slug after processing
        $title = '!!!@@@###';

        $this->slugifyMock
            ->shouldReceive('slugify')
            ->with($title)
            ->andReturn(''); // slugify returns empty for special chars only

        // Assert
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage('Title contains no valid characters for slug generation');

        // Act
        $this->service->generateSlug($title);
    }

    #[Test]
    public function generateSlugWithVeryLongTitleShouldTruncate(): void
    {
        // Arrange - title that generates slug exceeding MAX_BASE_SLUG_LENGTH (245)
        $title = str_repeat('a', 300);
        $longSlug = str_repeat('a', 300);
        $expectedSlug = str_repeat('a', 245); // Truncated to MAX_BASE_SLUG_LENGTH

        $this->slugifyMock
            ->shouldReceive('slugify')
            ->with($title)
            ->andReturn($longSlug);

        $this->uniquenessCheckerMock
            ->shouldReceive('slugExists')
            ->with($expectedSlug, null)
            ->andReturn(false);

        // Act
        $result = $this->service->generateSlug($title);

        // Assert
        $this->assertSame($expectedSlug, $result);
        $this->assertLessThanOrEqual(245, strlen($result));
    }

    #[Test]
    public function generateSlugWithLongTitleEndingInHyphenShouldTrimHyphen(): void
    {
        // Arrange - long slug that ends with hyphen after truncation
        $title = str_repeat('word-', 60); // Produces long slug with hyphens
        $longSlug = str_repeat('word-', 60);
        // After truncation at 245, we might have trailing hyphen
        $truncatedWithHyphen = substr($longSlug, 0, 245);
        $expectedSlug = rtrim($truncatedWithHyphen, '-');

        $this->slugifyMock
            ->shouldReceive('slugify')
            ->with($title)
            ->andReturn($longSlug);

        $this->uniquenessCheckerMock
            ->shouldReceive('slugExists')
            ->with($expectedSlug, null)
            ->andReturn(false);

        // Act
        $result = $this->service->generateSlug($title);

        // Assert
        $this->assertStringEndsNotWith('-', $result);
    }

    #[Test]
    public function generateSlugWithExistingSlugWithCounterShouldPreserve(): void
    {
        // Arrange - existing slug has counter suffix matching base slug
        $title = 'Test Title';
        $baseSlug = 'test-title';
        $existingSlug = 'test-title-2'; // Matches pattern: baseSlug + counter
        $excludeUuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->slugifyMock
            ->shouldReceive('slugify')
            ->with($title)
            ->andReturn($baseSlug);

        $this->uniquenessCheckerMock
            ->shouldReceive('slugExists')
            ->with($existingSlug, $excludeUuid)
            ->andReturn(false);

        // Act
        $result = $this->service->generateSlug($title, $existingSlug, $excludeUuid);

        // Assert
        $this->assertSame($existingSlug, $result);
    }

    #[Test]
    public function generateSlugWithExistingSlugThatExistsShouldRegenerate(): void
    {
        // Arrange - existing slug matches title but is no longer unique
        $title = 'Test Title';
        $baseSlug = 'test-title';
        $existingSlug = 'test-title';
        $excludeUuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->slugifyMock
            ->shouldReceive('slugify')
            ->with($title)
            ->andReturn($baseSlug);

        // Existing slug exists (collision)
        $this->uniquenessCheckerMock
            ->shouldReceive('slugExists')
            ->with($existingSlug, $excludeUuid)
            ->andReturn(true);

        // Base slug also exists
        $this->uniquenessCheckerMock
            ->shouldReceive('slugExists')
            ->with($baseSlug, $excludeUuid)
            ->andReturn(true);

        // First counter suffix is available
        $this->uniquenessCheckerMock
            ->shouldReceive('slugExists')
            ->with('test-title-1', $excludeUuid)
            ->andReturn(false);

        // Act
        $result = $this->service->generateSlug($title, $existingSlug, $excludeUuid);

        // Assert
        $this->assertSame('test-title-1', $result);
    }

    #[Test]
    public function generateSlugWithNonMatchingExistingSlugShouldGenerate(): void
    {
        // Arrange - existing slug does not match the title pattern
        $title = 'New Title';
        $baseSlug = 'new-title';
        $existingSlug = 'old-completely-different-slug';
        $excludeUuid = '550e8400-e29b-41d4-a716-446655440000';

        $this->slugifyMock
            ->shouldReceive('slugify')
            ->with($title)
            ->andReturn($baseSlug);

        // Base slug is available
        $this->uniquenessCheckerMock
            ->shouldReceive('slugExists')
            ->with($baseSlug, $excludeUuid)
            ->andReturn(false);

        // Act
        $result = $this->service->generateSlug($title, $existingSlug, $excludeUuid);

        // Assert
        $this->assertSame($baseSlug, $result);
    }

    #[Test]
    public function validateSlugFormatWithTooLongSlugShouldReturnFalse(): void
    {
        // Arrange - slug exceeds MAX_SLUG_LENGTH (255)
        $slug = str_repeat('a', 256);

        // Act
        $result = $this->service->validateSlugFormat($slug);

        // Assert
        $this->assertFalse($result);
    }

    #[Test]
    public function generateSlugWithSlugifyReturningHyphensOnlyShouldThrowException(): void
    {
        // Arrange - slugify returns only hyphens which get trimmed to empty
        $title = 'Test Title';

        $this->slugifyMock
            ->shouldReceive('slugify')
            ->with($title)
            ->andReturn('---');

        // Assert
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage('Title contains no valid characters for slug generation');

        // Act
        $this->service->generateSlug($title);
    }

    #[Test]
    public function generateSlugWithSlugifyReturningMixedCharactersShouldNormalize(): void
    {
        // Arrange - slugify returns string with multiple hyphens and special chars
        $title = 'Test--Title++Special';

        $this->slugifyMock
            ->shouldReceive('slugify')
            ->with($title)
            ->andReturn('test--title++special'); // With double hyphens and special chars

        $this->uniquenessCheckerMock
            ->shouldReceive('slugExists')
            ->with('test-titlespecial', null) // Normalized: special chars removed, hyphens collapsed
            ->andReturn(false);

        // Act
        $result = $this->service->generateSlug($title);

        // Assert - should have single hyphens and no special chars
        $this->assertStringNotContainsString('--', $result);
        $this->assertStringNotContainsString('+', $result);
    }
}
