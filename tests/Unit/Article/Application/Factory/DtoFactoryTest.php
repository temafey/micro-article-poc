<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\Factory;

use Micro\Component\Common\Infrastructure\Mapper\DtoMapperInterface;
use Micro\Article\Application\Dto\ArticleDto;
use Micro\Article\Application\Factory\DtoFactory;
use Micro\Article\Application\Factory\DtoFactoryInterface;
use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use MicroModule\Base\Domain\Exception\FactoryException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DtoFactory.
 */
#[CoversClass(DtoFactory::class)]
final class DtoFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private DtoFactory $factory;
    private DtoMapperInterface&Mockery\MockInterface $dtoMapperMock;

    protected function setUp(): void
    {
        $this->dtoMapperMock = \Mockery::mock(DtoMapperInterface::class);
        $this->factory = new DtoFactory($this->dtoMapperMock);
    }

    #[Test]
    public function makeArticleDtoShouldCreateDtoWithAllFields(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $title = 'Test Article';
        $shortDescription = 'Short description.';
        $description = 'Full description that meets the minimum requirements of fifty characters for validation testing purposes.';
        $slug = 'test-article';
        $eventId = 12345;
        $status = 'published';
        $publishedAt = new \DateTime('2024-01-15T10:30:00+00:00');
        $archivedAt = new \DateTime('2024-02-01T00:00:00+00:00');
        $createdAt = new \DateTime('2024-01-01T00:00:00+00:00');
        $updatedAt = new \DateTime('2024-01-15T10:30:00+00:00');

        // Act
        $result = $this->factory->makeArticleDto(
            $uuid,
            $title,
            $shortDescription,
            $description,
            $slug,
            $eventId,
            $status,
            $publishedAt,
            $archivedAt,
            $createdAt,
            $updatedAt
        );

        // Assert
        $this->assertInstanceOf(ArticleDto::class, $result);
        $this->assertSame($uuid, $result->uuid);
        $this->assertSame($title, $result->title);
        $this->assertSame($shortDescription, $result->shortDescription);
        $this->assertSame($description, $result->description);
        $this->assertSame($slug, $result->slug);
        $this->assertSame($eventId, $result->eventId);
        $this->assertSame($status, $result->status);
    }

    #[Test]
    public function makeArticleDtoFromDataShouldCreateDtoFromArray(): void
    {
        // Arrange
        $data = [
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'title' => 'Test Article',
            'short_description' => 'Short description.',
            'description' => 'Full description.',
            'slug' => 'test-article',
            'event_id' => 12345,
            'status' => 'draft',
        ];

        // Act
        $result = $this->factory->makeArticleDtoFromData($data);

        // Assert
        $this->assertInstanceOf(ArticleDto::class, $result);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $result->uuid);
        $this->assertSame('Test Article', $result->title);
    }

    #[Test]
    public function makeArticleDtoFromReadModelShouldUseDtoMapper(): void
    {
        // Arrange
        $readModelMock = \Mockery::mock(ArticleReadModelInterface::class);
        $expectedDto = new ArticleDto();

        $this->dtoMapperMock
            ->shouldReceive('map')
            ->once()
            ->with($readModelMock, ArticleDto::class)
            ->andReturn($expectedDto);

        // Act
        $result = $this->factory->makeArticleDtoFromReadModel($readModelMock);

        // Assert
        $this->assertSame($expectedDto, $result);
    }

    #[Test]
    public function makeArticleDtosFromReadModelsShouldUseDtoMapperCollection(): void
    {
        // Arrange
        $readModel1 = \Mockery::mock(ArticleReadModelInterface::class);
        $readModel2 = \Mockery::mock(ArticleReadModelInterface::class);
        $readModels = [$readModel1, $readModel2];
        $expectedDtos = [new ArticleDto(), new ArticleDto()];

        $this->dtoMapperMock
            ->shouldReceive('mapCollection')
            ->once()
            ->with($readModels, ArticleDto::class)
            ->andReturn($expectedDtos);

        // Act
        $result = $this->factory->makeArticleDtosFromReadModels($readModels);

        // Assert
        $this->assertCount(2, $result);
        $this->assertSame($expectedDtos, $result);
    }

    #[Test]
    public function makeDtoByTypeShouldCreateArticleDtoForValidType(): void
    {
        // Arrange
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $title = 'Test Article';
        $shortDescription = 'Short description.';
        $description = 'Full description that meets the minimum requirements of fifty characters.';
        $slug = 'test-article';
        $eventId = 12345;
        $status = 'draft';
        $publishedAt = new \DateTime();
        $archivedAt = new \DateTime();
        $createdAt = new \DateTime();
        $updatedAt = new \DateTime();

        // Act
        $result = $this->factory->makeDtoByType(
            DtoFactoryInterface::ARTICLE_DTO,
            $uuid,
            $title,
            $shortDescription,
            $description,
            $slug,
            $eventId,
            $status,
            $publishedAt,
            $archivedAt,
            $createdAt,
            $updatedAt
        );

        // Assert
        $this->assertInstanceOf(ArticleDto::class, $result);
    }

    #[Test]
    public function makeDtoByTypeShouldThrowExceptionForInvalidType(): void
    {
        // Assert
        $this->expectException(FactoryException::class);

        // Act
        $this->factory->makeDtoByType('invalid_dto_type');
    }

    #[Test]
    public function factoryShouldImplementInterface(): void
    {
        // Assert
        $this->assertInstanceOf(DtoFactoryInterface::class, $this->factory);
    }

    #[Test]
    public function constructorShouldAcceptDependencies(): void
    {
        // Arrange & Act
        $factory = new DtoFactory($this->dtoMapperMock);

        // Assert
        $this->assertInstanceOf(DtoFactory::class, $factory);
    }
}
