<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Domain\Factory;

use Micro\Article\Domain\Factory\ValueObjectFactory;
use Micro\Article\Domain\ValueObject\ArchivedAt;
use Micro\Article\Domain\ValueObject\Description;
use Micro\Article\Domain\ValueObject\EventId;
use Micro\Article\Domain\ValueObject\Article;
use Micro\Article\Domain\ValueObject\PublishedAt;
use Micro\Article\Domain\ValueObject\ShortDescription;
use Micro\Article\Domain\ValueObject\Slug;
use Micro\Article\Domain\ValueObject\Status;
use Micro\Article\Domain\ValueObject\Title;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Domain\Factory\ValueObjectFactoryDataProvider;

/**
 * Unit tests for ValueObjectFactory.
 */
#[CoversClass(ValueObjectFactory::class)]
final class ValueObjectFactoryTest extends TestCase
{
    private ValueObjectFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new ValueObjectFactory();
    }

    #[Test]
    #[DataProviderExternal(ValueObjectFactoryDataProvider::class, 'provideTitleValues')]
    public function makeTitleShouldCreateTitleValueObject(string $title): void
    {
        // Act
        $result = $this->factory->makeTitle($title);

        // Assert
        $this->assertInstanceOf(Title::class, $result);
        $this->assertSame($title, $result->toNative());
    }

    #[Test]
    #[DataProviderExternal(ValueObjectFactoryDataProvider::class, 'provideShortDescriptionValues')]
    public function makeShortDescriptionShouldCreateShortDescriptionValueObject(string $shortDescription): void
    {
        // Act
        $result = $this->factory->makeShortDescription($shortDescription);

        // Assert
        $this->assertInstanceOf(ShortDescription::class, $result);
        $this->assertSame($shortDescription, $result->toNative());
    }

    #[Test]
    #[DataProviderExternal(ValueObjectFactoryDataProvider::class, 'provideDescriptionValues')]
    public function makeDescriptionShouldCreateDescriptionValueObject(string $description): void
    {
        // Act
        $result = $this->factory->makeDescription($description);

        // Assert
        $this->assertInstanceOf(Description::class, $result);
        $this->assertSame($description, $result->toNative());
    }

    #[Test]
    #[DataProviderExternal(ValueObjectFactoryDataProvider::class, 'provideSlugValues')]
    public function makeSlugShouldCreateSlugValueObject(string $slug): void
    {
        // Act
        $result = $this->factory->makeSlug($slug);

        // Assert
        $this->assertInstanceOf(Slug::class, $result);
        $this->assertSame($slug, $result->toNative());
    }

    #[Test]
    #[DataProviderExternal(ValueObjectFactoryDataProvider::class, 'provideEventIdValues')]
    public function makeEventIdShouldCreateEventIdValueObject(int $eventId): void
    {
        // Act
        $result = $this->factory->makeEventId($eventId);

        // Assert
        $this->assertInstanceOf(EventId::class, $result);
        $this->assertSame($eventId, $result->toNative());
    }

    #[Test]
    #[DataProviderExternal(ValueObjectFactoryDataProvider::class, 'provideStatusValues')]
    public function makeStatusShouldCreateStatusValueObject(string $status): void
    {
        // Act
        $result = $this->factory->makeStatus($status);

        // Assert
        $this->assertInstanceOf(Status::class, $result);
        $this->assertSame($status, $result->toNative());
    }

    #[Test]
    public function makePublishedAtShouldCreatePublishedAtValueObject(): void
    {
        // Arrange
        $dateTime = new \DateTime('2024-01-15T10:30:00+00:00');

        // Act
        $result = $this->factory->makePublishedAt($dateTime);

        // Assert
        $this->assertInstanceOf(PublishedAt::class, $result);
    }

    #[Test]
    public function makeArchivedAtShouldCreateArchivedAtValueObject(): void
    {
        // Arrange
        $dateTime = new \DateTime('2024-02-01T00:00:00+00:00');

        // Act
        $result = $this->factory->makeArchivedAt($dateTime);

        // Assert
        $this->assertInstanceOf(ArchivedAt::class, $result);
    }

    #[Test]
    #[DataProviderExternal(ValueObjectFactoryDataProvider::class, 'provideArticleData')]
    public function makeArticleShouldCreateArticleValueObject(array $articleData): void
    {
        // Act
        $result = $this->factory->makeArticle($articleData);

        // Assert
        $this->assertInstanceOf(Article::class, $result);
    }

    #[Test]
    public function factoryShouldImplementValueObjectFactoryInterface(): void
    {
        // Assert
        $this->assertInstanceOf(\Micro\Article\Domain\Factory\ValueObjectFactoryInterface::class, $this->factory);
    }
}
