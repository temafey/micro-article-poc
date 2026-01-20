<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\Query;

use Micro\Article\Application\Query\FetchOneArticleQuery;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Application\Query\FetchOneArticleQueryDataProvider;

/**
 * Unit tests for FetchOneArticleQuery.
 */
#[CoversClass(FetchOneArticleQuery::class)]
final class FetchOneArticleQueryTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(FetchOneArticleQueryDataProvider::class, 'provideValidConstructionData')]
    public function constructWithValidDataShouldCreateQuery(string $processUuid, string $uuid): void
    {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $uuidVo = Uuid::fromNative($uuid);

        // Act
        $query = new FetchOneArticleQuery($processUuidVo, $uuidVo);

        // Assert
        $this->assertInstanceOf(FetchOneArticleQuery::class, $query);
    }

    #[Test]
    public function getUuidShouldReturnUuidValueObject(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $query = new FetchOneArticleQuery($processUuid, $uuid);

        // Act
        $result = $query->getUuid();

        // Assert
        $this->assertInstanceOf(Uuid::class, $result);
        $this->assertSame('6ba7b810-9dad-11d1-80b4-00c04fd430c8', $result->toNative());
    }

    #[Test]
    public function getProcessUuidShouldReturnProcessUuidValueObject(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $uuid = Uuid::fromNative('6ba7b810-9dad-11d1-80b4-00c04fd430c8');
        $query = new FetchOneArticleQuery($processUuid, $uuid);

        // Act
        $result = $query->getProcessUuid();

        // Assert
        $this->assertInstanceOf(ProcessUuid::class, $result);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $result->toNative());
    }
}
