<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Observability\Redis;

use Micro\Component\Common\Infrastructure\Observability\Service\TracerFactory;

/**
 * Factory for creating TracingRedis instances.
 *
 * Creates a TracingRedis decorator that wraps the native Redis client
 * with OpenTelemetry instrumentation.
 *
 * @see ADR-014: Observability Stack Modernization - Phase 3.3
 */
final readonly class TracingRedisFactory
{
    public function __construct(
        private TracerFactory $tracerFactory,
        private string $host,
        private int $port,
    ) {
    }

    /**
     * Create a TracingRedis instance wrapping the given Redis client.
     */
    public function create(\Redis|\RedisCluster $redis): TracingRedis
    {
        return new TracingRedis(
            $redis,
            $this->tracerFactory,
            $this->host,
            $this->port,
        );
    }

    /**
     * Create a new Redis connection with tracing enabled.
     *
     * Useful when you need a fresh connection rather than decorating an existing one.
     */
    public function createWithConnection(): TracingRedis
    {
        $redis = new \Redis();
        $redis->connect($this->host, $this->port);

        return new TracingRedis(
            $redis,
            $this->tracerFactory,
            $this->host,
            $this->port,
        );
    }
}
