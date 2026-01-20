<?php

declare(strict_types=1);

namespace Tests\Unit\Traits;

use Mockery\MockInterface;

/**
 * Trait for testing CQRS Query Handlers.
 *
 * Provides:
 * - Read model repository mocking
 * - Query result assertions
 * - Pagination and filtering test helpers
 */
trait QueryHandlerTestTrait
{
    /**
     * Mock storage for query-related mocks.
     *
     * @var array<string, MockInterface>
     */
    protected array $queryMocks = [];

    /**
     * Create a mock read model.
     *
     * @param array<string, mixed> $data
     */
    protected function createReadModelMock(string $class, array $data = []): MockInterface
    {
        $mock = \Mockery::mock($class);

        foreach ($data as $property => $value) {
            $getter = 'get' . ucfirst($property);
            $mock->shouldReceive($getter)
                ->andReturn($value)
                ->byDefault();
        }

        // Also support toArray/toNative
        $mock->shouldReceive('toArray')
            ->andReturn($data)
            ->byDefault();
        $mock->shouldReceive('toNative')
            ->andReturn($data)
            ->byDefault();

        return $mock;
    }

    /**
     * Create multiple read model mocks.
     *
     * @param array<array<string, mixed>> $dataSet
     *
     * @return array<MockInterface>
     */
    protected function createReadModelMocks(string $class, array $dataSet): array
    {
        return array_map(fn (array $data) => $this->createReadModelMock($class, $data), $dataSet);
    }

    /**
     * Configure repository to return read models.
     *
     * @param array<MockInterface> $readModels
     */
    protected function expectRepositoryReturns(MockInterface $repository, string $method, array $readModels): void
    {
        $repository->shouldReceive($method)
            ->andReturn($readModels);
    }

    /**
     * Configure repository to return single read model.
     */
    protected function expectRepositoryReturnsSingle(
        MockInterface $repository,
        string $method,
        ?MockInterface $readModel,
    ): void {
        $repository->shouldReceive($method)
            ->andReturn($readModel);
    }

    /**
     * Configure repository to return empty results.
     */
    protected function expectRepositoryReturnsEmpty(MockInterface $repository, string $method): void
    {
        $repository->shouldReceive($method)
            ->andReturn([]);
    }

    /**
     * Assert query result is a collection.
     *
     * @param iterable<mixed> $result
     */
    protected function assertQueryResultIsCollection(iterable $result): void
    {
        self::assertTrue(is_array($result) || $result instanceof \Traversable, 'Query result is not a collection');
    }

    /**
     * Assert query result count.
     *
     * @param iterable<mixed> $result
     */
    protected function assertQueryResultCount(int $expected, iterable $result): void
    {
        $count = is_array($result) ? count($result) : iterator_count($result);
        self::assertEquals($expected, $count, 'Query result count mismatch');
    }

    /**
     * Assert paginated query result structure.
     *
     * @param array<string, mixed> $result
     */
    protected function assertPaginatedQueryResult(array $result): void
    {
        self::assertArrayHasKey('items', $result, 'Paginated result missing items');
        self::assertArrayHasKey('total', $result, 'Paginated result missing total');
        self::assertArrayHasKey('page', $result, 'Paginated result missing page');
        self::assertArrayHasKey('perPage', $result, 'Paginated result missing perPage');
    }

    /**
     * Assert query returns null for not found.
     */
    protected function assertQueryReturnsNull(callable $handler, object $query): void
    {
        $result = $handler($query);
        self::assertNull($result, 'Expected null result from query');
    }

    /**
     * Assert query returns expected DTO.
     *
     * @param array<string, mixed> $expectedData
     */
    protected function assertQueryReturnsDto(
        callable $handler,
        object $query,
        string $dtoClass,
        array $expectedData = [],
    ): void {
        $result = $handler($query);

        self::assertInstanceOf($dtoClass, $result, 'Query did not return expected DTO type');

        foreach ($expectedData as $property => $expectedValue) {
            $getter = 'get' . ucfirst($property);
            if (method_exists($result, $getter)) {
                self::assertEquals(
                    $expectedValue,
                    $result->{$getter}(),
                    sprintf('DTO property %s does not match', $property)
                );
            }
        }
    }

    /**
     * Create a paginated result mock.
     *
     * @param array<MockInterface> $items
     *
     * @return array<string, mixed>
     */
    protected function createPaginatedResult(array $items, int $total, int $page = 1, int $perPage = 10): array
    {
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => (int) ceil($total / $perPage),
        ];
    }

    /**
     * Create filter criteria mock.
     *
     * @param array<string, mixed> $filters
     */
    protected function createFilterCriteria(array $filters): MockInterface
    {
        $criteria = \Mockery::mock('FilterCriteria');

        foreach ($filters as $field => $value) {
            $getter = 'get' . ucfirst($field);
            $criteria->shouldReceive($getter)
                ->andReturn($value)
                ->byDefault();
            $criteria->shouldReceive('has' . ucfirst($field))->andReturn(true)->byDefault();
        }

        $criteria->shouldReceive('toArray')
            ->andReturn($filters)
            ->byDefault();

        return $criteria;
    }
}
