<?php

declare(strict_types=1);

namespace Tests\Integration;

use Doctrine\DBAL\Connection;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Base class for integration tests requiring the Symfony Kernel.
 *
 * Provides:
 * - Access to the service container (public and private services via test container)
 * - Database transaction rollback for test isolation
 * - Mockery integration
 * - Helper methods for common service access
 */
abstract class IntegrationTestCase extends KernelTestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Whether to wrap tests in a database transaction.
     */
    protected bool $useTransaction = true;

    /**
     * The database connection for transaction management.
     */
    protected ?Connection $connection = null;

    protected function setUp(): void
    {
        parent::setUp();

        self::bootKernel();

        if ($this->useTransaction) {
            $this->beginTransaction();
        }
    }

    protected function tearDown(): void
    {
        if ($this->useTransaction && $this->connection !== null) {
            $this->rollbackTransaction();
        }

        $this->connection = null;

        \Mockery::close();
        parent::tearDown();
    }

    /**
     * Get a service from the test container.
     *
     * @template T of object
     *
     * @param class-string<T> $id
     *
     * @return T
     */
    protected function getService(string $id): object
    {
        return self::getContainer()->get($id);
    }

    /**
     * Check if a service exists in the container.
     */
    protected function hasService(string $id): bool
    {
        return self::getContainer()->has($id);
    }

    /**
     * Get a parameter from the container.
     */
    protected function getParameter(string $name): mixed
    {
        return self::getContainer()->getParameter($name);
    }

    /**
     * Get the database connection.
     */
    protected function getConnection(): Connection
    {
        if ($this->connection === null) {
            $this->connection = $this->getService(Connection::class);
        }

        return $this->connection;
    }

    /**
     * Begin a database transaction for test isolation.
     */
    protected function beginTransaction(): void
    {
        $this->getConnection()
            ->beginTransaction();
    }

    /**
     * Rollback the database transaction.
     */
    protected function rollbackTransaction(): void
    {
        if ($this->connection?->isTransactionActive()) {
            $this->connection->rollBack();
        }
    }

    /**
     * Execute a database query and return results.
     *
     * @param array<string, mixed> $params
     *
     * @return array<int, array<string, mixed>>
     */
    protected function executeQuery(string $sql, array $params = []): array
    {
        return $this->getConnection()
            ->fetchAllAssociative($sql, $params);
    }

    /**
     * Execute a database statement (INSERT, UPDATE, DELETE).
     *
     * @param array<string, mixed> $params
     */
    protected function executeStatement(string $sql, array $params = []): int
    {
        return $this->getConnection()
            ->executeStatement($sql, $params);
    }

    /**
     * Assert that a database table contains a row matching criteria.
     *
     * @param array<string, mixed> $criteria
     */
    protected function assertDatabaseHas(string $table, array $criteria): void
    {
        $conditions = [];
        $params = [];

        foreach ($criteria as $column => $value) {
            $conditions[] = sprintf('%s = :%s', $column, $column);
            $params[$column] = $value;
        }

        $sql = sprintf('SELECT COUNT(*) FROM %s WHERE %s', $table, implode(' AND ', $conditions));

        $count = (int) $this->getConnection()
            ->fetchOne($sql, $params);

        self::assertGreaterThan(
            0,
            $count,
            sprintf('Failed asserting that table [%s] contains matching row', $table)
        );
    }

    /**
     * Assert that a database table does not contain a row matching criteria.
     *
     * @param array<string, mixed> $criteria
     */
    protected function assertDatabaseMissing(string $table, array $criteria): void
    {
        $conditions = [];
        $params = [];

        foreach ($criteria as $column => $value) {
            $conditions[] = sprintf('%s = :%s', $column, $column);
            $params[$column] = $value;
        }

        $sql = sprintf('SELECT COUNT(*) FROM %s WHERE %s', $table, implode(' AND ', $conditions));

        $count = (int) $this->getConnection()
            ->fetchOne($sql, $params);

        self::assertEquals(
            0,
            $count,
            sprintf('Failed asserting that table [%s] does not contain matching row', $table)
        );
    }

    /**
     * Clear a database table.
     */
    protected function truncateTable(string $table): void
    {
        $this->executeStatement(sprintf('TRUNCATE TABLE %s CASCADE', $table));
    }

    /**
     * Create an entity in the database and return its ID.
     *
     * @param array<string, mixed> $data
     */
    protected function insertRow(string $table, array $data): string|int
    {
        $this->getConnection()
            ->insert($table, $data);

        return $this->getConnection()
            ->lastInsertId();
    }
}
