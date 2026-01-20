<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\Query;

use Micro\Article\Application\Query\FindOneByArticleQuery;
use MicroModule\Base\Domain\ValueObject\FindCriteria;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Application\Query\FindOneByArticleQueryDataProvider;

/**
 * Unit tests for FindOneByArticleQuery.
 */
#[CoversClass(FindOneByArticleQuery::class)]
final class FindOneByArticleQueryTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(FindOneByArticleQueryDataProvider::class, 'provideValidConstructionData')]
    public function constructWithValidDataShouldCreateQuery(string $processUuid, array $criteria): void
    {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $findCriteria = FindCriteria::fromNative($criteria);

        // Act
        $query = new FindOneByArticleQuery($processUuidVo, $findCriteria);

        // Assert
        $this->assertInstanceOf(FindOneByArticleQuery::class, $query);
    }

    #[Test]
    public function getFindCriteriaShouldReturnFindCriteriaValueObject(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $criteriaData = [
            'slug' => 'test-article',
        ];
        $findCriteria = FindCriteria::fromNative($criteriaData);
        $query = new FindOneByArticleQuery($processUuid, $findCriteria);

        // Act
        $result = $query->getFindCriteria();

        // Assert
        $this->assertInstanceOf(FindCriteria::class, $result);
        $this->assertSame($criteriaData, $result->toNative());
    }

    #[Test]
    public function getProcessUuidShouldReturnProcessUuidValueObject(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $findCriteria = FindCriteria::fromNative([
            'event_id' => 12345,
        ]);
        $query = new FindOneByArticleQuery($processUuid, $findCriteria);

        // Act
        $result = $query->getProcessUuid();

        // Assert
        $this->assertInstanceOf(ProcessUuid::class, $result);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $result->toNative());
    }
}
