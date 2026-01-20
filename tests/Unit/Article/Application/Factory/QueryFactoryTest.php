<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\Factory;

use Micro\Article\Application\Factory\QueryFactory;
use Micro\Article\Application\Factory\QueryFactoryInterface;
use Micro\Article\Application\Query\FetchOneArticleQuery;
use Micro\Article\Application\Query\FindByCriteriaArticleQuery;
use Micro\Article\Application\Query\FindOneByArticleQuery;
use MicroModule\Base\Domain\Exception\FactoryException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for QueryFactory.
 */
#[CoversClass(QueryFactory::class)]
final class QueryFactoryTest extends TestCase
{
    private QueryFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new QueryFactory();
    }

    #[Test]
    public function isQueryAllowedShouldReturnTrueForValidQuery(): void
    {
        // Assert
        $this->assertTrue($this->factory->isQueryAllowed(QueryFactoryInterface::FETCH_ONE_ARTICLE_QUERY));
        $this->assertTrue($this->factory->isQueryAllowed(QueryFactoryInterface::FIND_BY_CRITERIA_ARTICLE_QUERY));
        $this->assertTrue($this->factory->isQueryAllowed(QueryFactoryInterface::FIND_ONE_BY_ARTICLE_QUERY));
    }

    #[Test]
    public function isQueryAllowedShouldReturnFalseForInvalidQuery(): void
    {
        // Assert
        $this->assertFalse($this->factory->isQueryAllowed('invalid_query'));
    }

    #[Test]
    public function makeFetchOneArticleQueryShouldCreateQuery(): void
    {
        // Arrange
        $processUuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

        // Act
        $result = $this->factory->makeFetchOneArticleQuery($processUuid, $uuid);

        // Assert
        $this->assertInstanceOf(FetchOneArticleQuery::class, $result);
    }

    #[Test]
    public function makeFindByCriteriaArticleQueryShouldCreateQuery(): void
    {
        // Arrange
        $processUuid = '550e8400-e29b-41d4-a716-446655440000';
        $criteria = [
            'status' => 'published',
        ];

        // Act
        $result = $this->factory->makeFindByCriteriaArticleQuery($processUuid, $criteria);

        // Assert
        $this->assertInstanceOf(FindByCriteriaArticleQuery::class, $result);
    }

    #[Test]
    public function makeFindOneByArticleQueryShouldCreateQuery(): void
    {
        // Arrange
        $processUuid = '550e8400-e29b-41d4-a716-446655440000';
        $criteria = [
            'slug' => 'test-article',
        ];

        // Act
        $result = $this->factory->makeFindOneByArticleQuery($processUuid, $criteria);

        // Assert
        $this->assertInstanceOf(FindOneByArticleQuery::class, $result);
    }

    #[Test]
    public function makeQueryInstanceByTypeShouldCreateCorrectQuery(): void
    {
        // Arrange
        $processUuid = '550e8400-e29b-41d4-a716-446655440000';
        $uuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

        // Act
        $result = $this->factory->makeQueryInstanceByType(
            QueryFactoryInterface::FETCH_ONE_ARTICLE_QUERY,
            $processUuid,
            $uuid
        );

        // Assert
        $this->assertInstanceOf(FetchOneArticleQuery::class, $result);
    }

    #[Test]
    public function makeQueryInstanceByTypeShouldThrowExceptionForInvalidType(): void
    {
        // Assert
        $this->expectException(FactoryException::class);

        // Act
        $this->factory->makeQueryInstanceByType('invalid_type');
    }

    #[Test]
    public function factoryShouldImplementInterface(): void
    {
        // Assert
        $this->assertInstanceOf(QueryFactoryInterface::class, $this->factory);
    }

    #[Test]
    public function makeQueryInstanceByTypeShouldCreateFindByCriteriaQuery(): void
    {
        // Arrange
        $criteria = ['status' => 'published'];

        // Act
        $result = $this->factory->makeQueryInstanceByType(
            QueryFactoryInterface::FIND_BY_CRITERIA_ARTICLE_QUERY,
            $criteria
        );

        // Assert
        $this->assertInstanceOf(FindByCriteriaArticleQuery::class, $result);
    }

    #[Test]
    public function makeQueryInstanceByTypeShouldCreateFindOneByQuery(): void
    {
        // Arrange
        $criteria = ['slug' => 'test-article'];

        // Act
        $result = $this->factory->makeQueryInstanceByType(
            QueryFactoryInterface::FIND_ONE_BY_ARTICLE_QUERY,
            $criteria
        );

        // Assert
        $this->assertInstanceOf(FindOneByArticleQuery::class, $result);
    }

    #[Test]
    public function makeQueryInstanceByTypeShouldHandleArrayWithUuid(): void
    {
        // Arrange - test array pattern with uuid key
        $data = ['uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8'];

        // Act
        $result = $this->factory->makeQueryInstanceByType(
            QueryFactoryInterface::FETCH_ONE_ARTICLE_QUERY,
            $data
        );

        // Assert
        $this->assertInstanceOf(FetchOneArticleQuery::class, $result);
        $this->assertSame('6ba7b810-9dad-11d1-80b4-00c04fd430c8', $result->getUuid()->toNative());
    }

    #[Test]
    public function makeQueryInstanceByTypeShouldHandleSingleStringUuid(): void
    {
        // Arrange - test single string uuid pattern
        $uuid = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

        // Act
        $result = $this->factory->makeQueryInstanceByType(
            QueryFactoryInterface::FETCH_ONE_ARTICLE_QUERY,
            $uuid
        );

        // Assert
        $this->assertInstanceOf(FetchOneArticleQuery::class, $result);
        $this->assertSame('6ba7b810-9dad-11d1-80b4-00c04fd430c8', $result->getUuid()->toNative());
    }

    #[Test]
    public function makeQueryInstanceByTypeFromDtoShouldCreateFetchOneQuery(): void
    {
        // Arrange
        $dto = new \Micro\Article\Application\Dto\ArticleDto(
            uuid: '6ba7b810-9dad-11d1-80b4-00c04fd430c8'
        );

        // Act
        $result = $this->factory->makeQueryInstanceByTypeFromDto(
            QueryFactoryInterface::FETCH_ONE_ARTICLE_QUERY,
            $dto
        );

        // Assert
        $this->assertInstanceOf(FetchOneArticleQuery::class, $result);
    }

    #[Test]
    public function makeQueryInstanceByTypeFromDtoShouldCreateFindByCriteriaQuery(): void
    {
        // Arrange
        $dto = new \Micro\Article\Application\Dto\ArticleDto(
            status: 'published',
            title: 'Test Article'
        );

        // Act
        $result = $this->factory->makeQueryInstanceByTypeFromDto(
            QueryFactoryInterface::FIND_BY_CRITERIA_ARTICLE_QUERY,
            $dto
        );

        // Assert
        $this->assertInstanceOf(FindByCriteriaArticleQuery::class, $result);
    }

    #[Test]
    public function makeQueryInstanceByTypeFromDtoShouldCreateFindOneByQuery(): void
    {
        // Arrange
        $dto = new \Micro\Article\Application\Dto\ArticleDto(
            slug: 'test-article'
        );

        // Act
        $result = $this->factory->makeQueryInstanceByTypeFromDto(
            QueryFactoryInterface::FIND_ONE_BY_ARTICLE_QUERY,
            $dto
        );

        // Assert
        $this->assertInstanceOf(FindOneByArticleQuery::class, $result);
    }

    #[Test]
    public function makeQueryInstanceByTypeFromDtoShouldThrowExceptionForInvalidType(): void
    {
        // Arrange
        $dto = new \Micro\Article\Application\Dto\ArticleDto();

        // Assert
        $this->expectException(FactoryException::class);

        // Act
        $this->factory->makeQueryInstanceByTypeFromDto('invalid_type', $dto);
    }

    #[Test]
    public function makeQueryInstanceByTypeFromDtoShouldUseProcessUuidFromDto(): void
    {
        // Arrange - Create a DTO that includes process_uuid
        $dto = $this->createMock(\MicroModule\Base\Application\Dto\DtoInterface::class);
        $dto->method('normalize')->willReturn([
            'process_uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'uuid' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
        ]);

        // Act
        $result = $this->factory->makeQueryInstanceByTypeFromDto(
            QueryFactoryInterface::FETCH_ONE_ARTICLE_QUERY,
            $dto
        );

        // Assert
        $this->assertInstanceOf(FetchOneArticleQuery::class, $result);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $result->getProcessUuid()->toNative());
    }
}
