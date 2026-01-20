<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\OpenTelemetry\CircuitBreaker;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Builder;
use Ackintosh\Ganesha\Storage\Adapter\Redis as RedisAdapter;
use Predis\Client as PredisClient;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Factory for creating Ganesha circuit breaker instances.
 *
 * Uses Redis for state storage to share circuit state across PHP-FPM workers.
 * Falls back to in-memory storage if Redis is unavailable.
 *
 * Part of TASK-15: OpenTelemetry Circuit Breaker Resilience
 *
 * @see https://github.com/ackintosh/ganesha
 */
final class GaneshaCircuitBreakerFactory
{
    public function __construct(
        private readonly string $redisHost,
        private readonly int $redisPort,
        private readonly int $timeWindowSeconds = 30,
        private readonly int $failureRateThreshold = 50,
        private readonly int $minimumRequests = 3,
        private readonly int $intervalToHalfOpenSeconds = 10,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Create a circuit breaker instance for OTEL export operations.
     *
     * The circuit breaker uses the Rate strategy:
     * - Tracks failure rate within a rolling time window
     * - Opens circuit when failure rate exceeds threshold
     * - Transitions to half-open after interval to test recovery
     * - Closes circuit on successful half-open request
     */
    public function create(): Ganesha
    {
        $logger = $this->logger ?? new NullLogger();

        $builder = Builder::withRateStrategy()
            ->timeWindow($this->timeWindowSeconds)
            ->failureRateThreshold($this->failureRateThreshold)
            ->minimumRequests($this->minimumRequests)
            ->intervalToHalfOpen($this->intervalToHalfOpenSeconds);

        try {
            $redis = new PredisClient([
                'host' => $this->redisHost,
                'port' => $this->redisPort,
                'timeout' => 1.0,
                'read_write_timeout' => 1.0,
            ]);

            // Test connection - this throws if Redis is unavailable
            $redis->ping();

            $builder->adapter(new RedisAdapter($redis));

            $logger->debug('Circuit breaker initialized with Redis storage', [
                'host' => $this->redisHost,
                'port' => $this->redisPort,
            ]);
        } catch (\Throwable $e) {
            // Fall back to in-memory if Redis unavailable
            // Note: In-memory won't share state across workers, but it's better
            // than failing completely when Redis is down
            $builder->adapter(new InMemoryStorageAdapter());

            $logger->warning('Circuit breaker falling back to in-memory storage', [
                'reason' => $e->getMessage(),
                'host' => $this->redisHost,
                'port' => $this->redisPort,
            ]);
        }

        return $builder->build();
    }
}
