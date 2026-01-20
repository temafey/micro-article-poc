<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\OpenTelemetry\CircuitBreaker;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Configuration;
use Ackintosh\Ganesha\Context;
use Ackintosh\Ganesha\Storage\Adapter\TumblingTimeWindowInterface;
use Ackintosh\Ganesha\Storage\AdapterInterface;

/**
 * In-memory storage adapter for Ganesha circuit breaker.
 *
 * This is a fallback adapter used when Redis is unavailable.
 * Note: State is NOT shared between PHP-FPM workers or requests.
 * This means the circuit breaker will operate independently per-worker,
 * but it's better than failing completely when Redis is down.
 *
 * Part of TASK-15: OpenTelemetry Circuit Breaker Resilience
 */
final class InMemoryStorageAdapter implements AdapterInterface, TumblingTimeWindowInterface
{
    /**
     * Static storage to persist state within a single PHP process.
     *
     * @var array<string, int>
     */
    private static array $storage = [];

    public function supportCountStrategy(): bool
    {
        return true;
    }

    public function supportRateStrategy(): bool
    {
        return true;
    }

    public function setContext(Context $context): void
    {
        // No-op: We don't need configuration for in-memory storage
    }

    /**
     * @deprecated This method will be removed in the next major release.
     */
    public function setConfiguration(Configuration $configuration): void
    {
        // No-op for backward compatibility
    }

    /**
     * Load the current failure count for a service.
     */
    public function load(string $service): int
    {
        return self::$storage[$service] ?? 0;
    }

    /**
     * Save the failure count for a service.
     */
    public function save(string $service, int $count): void
    {
        self::$storage[$service] = $count;
    }

    /**
     * Increment the failure count for a service.
     */
    public function increment(string $service): void
    {
        self::$storage[$service] = (self::$storage[$service] ?? 0) + 1;
    }

    /**
     * Decrement the failure count for a service.
     */
    public function decrement(string $service): void
    {
        $current = self::$storage[$service] ?? 0;
        self::$storage[$service] = max(0, $current - 1);
    }

    /**
     * Save the last failure timestamp for a service.
     */
    public function saveLastFailureTime(string $service, int $lastFailureTime): void
    {
        self::$storage[$service . '_lastFailureTime'] = $lastFailureTime;
    }

    /**
     * Load the last failure timestamp for a service.
     */
    public function loadLastFailureTime(string $service): ?int
    {
        return self::$storage[$service . '_lastFailureTime'] ?? null;
    }

    /**
     * Save the circuit status for a service.
     *
     * @param int $status One of Ganesha::STATUS_* constants
     */
    public function saveStatus(string $service, int $status): void
    {
        self::$storage[$service . '_status'] = $status;
    }

    /**
     * Load the circuit status for a service.
     *
     * @return int One of Ganesha::STATUS_* constants
     */
    public function loadStatus(string $service): int
    {
        return self::$storage[$service . '_status'] ?? Ganesha::STATUS_CALMED_DOWN;
    }

    /**
     * Reset all stored state.
     *
     * Useful for testing.
     */
    public function reset(): void
    {
        self::$storage = [];
    }
}
