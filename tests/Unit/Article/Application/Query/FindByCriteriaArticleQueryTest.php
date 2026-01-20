<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\Query;

use Micro\Article\Application\Query\FindByCriteriaArticleQuery;
use MicroModule\Base\Domain\ValueObject\FindCriteria;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Application\Query\FindByCriteriaArticleQueryDataProvider;

/**
 * Unit tests for FindByCriteriaArticleQuery.
 */
#[CoversClass(FindByCriteriaArticleQuery::class)]
final class FindByCriteriaArticleQueryTest extends TestCase
{
    #[Test]
    #[DataProviderExternal(FindByCriteriaArticleQueryDataProvider::class, 'provideValidConstructionData')]
    public function constructWithValidDataShouldCreateQuery(string $processUuid, array $criteria): void
    {
        // Arrange
        $processUuidVo = ProcessUuid::fromNative($processUuid);
        $findCriteria = FindCriteria::fromNative($criteria);

        // Act
        $query = new FindByCriteriaArticleQuery($processUuidVo, $findCriteria);

        // Assert
        $this->assertInstanceOf(FindByCriteriaArticleQuery::class, $query);
    }

    #[Test]
    public function getFindCriteriaShouldReturnFindCriteriaValueObject(): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative('550e8400-e29b-41d4-a716-446655440000');
        $criteriaData = [
            'status' => 'published',
        ];
        $findCriteria = FindCriteria::fromNative($criteriaData);
        $query = new FindByCriteriaArticleQuery($processUuid, $findCriteria);

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
            'status' => 'draft',
        ]);
        $query = new FindByCriteriaArticleQuery($processUuid, $findCriteria);

        // Act
        $result = $query->getProcessUuid();

        // Assert
        $this->assertInstanceOf(ProcessUuid::class, $result);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $result->toNative());
    }
}
