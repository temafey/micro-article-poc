<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Observability\Redis;

use Micro\Component\Common\Infrastructure\Observability\Service\TracerFactory;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;

/**
 * Tracing Redis that instruments operations with OpenTelemetry spans.
 *
 * Extends \Redis directly to be type-compatible with all Redis consumers.
 * This allows Symfony to use TracingRedis as a drop-in replacement for \Redis.
 *
 * Traces all Redis operations including:
 * - String operations (GET, SET, DEL, EXISTS, INCR, DECR)
 * - Hash operations (HGET, HSET, HDEL, HGETALL)
 * - List operations (LPUSH, RPUSH, LPOP, RPOP)
 * - Set operations (SADD, SREM, SMEMBERS)
 * - TTL operations (EXPIRE, TTL, PERSIST)
 * - Connection operations (CONNECT, PING)
 *
 * @see ADR-014: Observability Stack Modernization - Phase 3.3
 */
class TracingRedis extends \Redis
{
    /** @var array<string, string> Operation type mapping */
    private const array OPERATION_TYPES = [
        // String operations
        'get' => 'GET',
        'set' => 'SET',
        'del' => 'DEL',
        'delete' => 'DEL',
        'exists' => 'EXISTS',
        'incr' => 'INCR',
        'decr' => 'DECR',
        'incrby' => 'INCRBY',
        'decrby' => 'DECRBY',
        'incrbyfloat' => 'INCRBYFLOAT',
        'append' => 'APPEND',
        'getset' => 'GETSET',
        'mget' => 'MGET',
        'mset' => 'MSET',
        'setnx' => 'SETNX',
        'setex' => 'SETEX',
        'psetex' => 'PSETEX',
        'strlen' => 'STRLEN',

        // Hash operations
        'hget' => 'HGET',
        'hset' => 'HSET',
        'hdel' => 'HDEL',
        'hgetall' => 'HGETALL',
        'hexists' => 'HEXISTS',
        'hincrby' => 'HINCRBY',
        'hincrbyfloat' => 'HINCRBYFLOAT',
        'hkeys' => 'HKEYS',
        'hvals' => 'HVALS',
        'hlen' => 'HLEN',
        'hmget' => 'HMGET',
        'hmset' => 'HMSET',
        'hsetnx' => 'HSETNX',

        // List operations
        'lpush' => 'LPUSH',
        'rpush' => 'RPUSH',
        'lpop' => 'LPOP',
        'rpop' => 'RPOP',
        'llen' => 'LLEN',
        'lrange' => 'LRANGE',
        'lindex' => 'LINDEX',
        'lset' => 'LSET',
        'lrem' => 'LREM',
        'ltrim' => 'LTRIM',
        'blpop' => 'BLPOP',
        'brpop' => 'BRPOP',

        // Set operations
        'sadd' => 'SADD',
        'srem' => 'SREM',
        'smembers' => 'SMEMBERS',
        'sismember' => 'SISMEMBER',
        'scard' => 'SCARD',
        'sdiff' => 'SDIFF',
        'sinter' => 'SINTER',
        'sunion' => 'SUNION',
        'spop' => 'SPOP',
        'srandmember' => 'SRANDMEMBER',

        // Sorted set operations
        'zadd' => 'ZADD',
        'zrem' => 'ZREM',
        'zrange' => 'ZRANGE',
        'zrevrange' => 'ZREVRANGE',
        'zrangebyscore' => 'ZRANGEBYSCORE',
        'zcard' => 'ZCARD',
        'zscore' => 'ZSCORE',
        'zincrby' => 'ZINCRBY',
        'zrank' => 'ZRANK',
        'zrevrank' => 'ZREVRANK',

        // TTL operations
        'expire' => 'EXPIRE',
        'expireat' => 'EXPIREAT',
        'pexpire' => 'PEXPIRE',
        'pexpireat' => 'PEXPIREAT',
        'ttl' => 'TTL',
        'pttl' => 'PTTL',
        'persist' => 'PERSIST',

        // Key operations
        'keys' => 'KEYS',
        'scan' => 'SCAN',
        'type' => 'TYPE',
        'rename' => 'RENAME',
        'renamenx' => 'RENAMENX',
        'randomkey' => 'RANDOMKEY',
        'dbsize' => 'DBSIZE',
        'dump' => 'DUMP',
        'restore' => 'RESTORE',

        // Connection
        'connect' => 'CONNECT',
        'pconnect' => 'PCONNECT',
        'ping' => 'PING',
        'auth' => 'AUTH',
        'select' => 'SELECT',
        'close' => 'CLOSE',
        'quit' => 'QUIT',

        // Server
        'flushdb' => 'FLUSHDB',
        'flushall' => 'FLUSHALL',
        'info' => 'INFO',
        'time' => 'TIME',

        // Transactions
        'multi' => 'MULTI',
        'exec' => 'EXEC',
        'discard' => 'DISCARD',
        'watch' => 'WATCH',
        'unwatch' => 'UNWATCH',

        // Pub/Sub
        'publish' => 'PUBLISH',
        'subscribe' => 'SUBSCRIBE',
        'unsubscribe' => 'UNSUBSCRIBE',
        'psubscribe' => 'PSUBSCRIBE',
        'punsubscribe' => 'PUNSUBSCRIBE',

        // Scripts
        'eval' => 'EVAL',
        'evalsha' => 'EVALSHA',
        'script' => 'SCRIPT',
    ];

    private ?TracerFactory $tracerFactory = null;
    private string $tracingHost = 'unknown';
    private int $tracingPort = 6379;

    /**
     * Initialize tracing capabilities.
     * Called by Symfony after construction via configurator or method call.
     */
    public function initTracing(TracerFactory $tracerFactory): void
    {
        $this->tracerFactory = $tracerFactory;
    }

    /**
     * Connect to Redis server with tracing.
     *
     * @param string $host Redis host
     * @param int $port Redis port
     * @param float $timeout Connection timeout
     * @param string|null $persistent_id Persistent connection ID
     * @param int $retry_interval Retry interval
     * @param float $read_timeout Read timeout
     * @param array|null $context SSL context options
     */
    public function connect(
        string $host,
        int $port = 6379,
        float $timeout = 0.0,
        ?string $persistent_id = null,
        int $retry_interval = 0,
        float $read_timeout = 0.0,
        ?array $context = null,
    ): bool {
        // Store connection info for span attributes
        $this->tracingHost = $host;
        $this->tracingPort = $port;

        return $this->traceOperation('connect', function () use ($host, $port, $timeout, $persistent_id, $retry_interval, $read_timeout, $context): bool {
            return parent::connect($host, $port, $timeout, $persistent_id, $retry_interval, $read_timeout, $context);
        });
    }

    /**
     * Get a key's value with tracing.
     */
    public function get(string $key): mixed
    {
        return $this->traceOperation('get', function () use ($key): mixed {
            return parent::get($key);
        }, $key, true);
    }

    /**
     * Set a key's value with tracing.
     *
     * @param array|null $options Options array (EX, PX, NX, XX, etc.)
     */
    public function set(string $key, mixed $value, mixed $options = null): \Redis|bool|string
    {
        return $this->traceOperation('set', function () use ($key, $value, $options): \Redis|bool|string {
            if ($options !== null) {
                return parent::set($key, $value, $options);
            }

            return parent::set($key, $value);
        }, $key);
    }

    /**
     * Delete one or more keys with tracing.
     *
     * @param mixed $key Key or array of keys
     * @param mixed ...$other_keys Additional keys
     */
    public function del(mixed $key, mixed ...$other_keys): \Redis|int|false
    {
        $keyForTrace = is_array($key) ? implode(',', array_slice($key, 0, 3)) : (string) $key;

        return $this->traceOperation('del', function () use ($key, $other_keys): \Redis|int|false {
            return parent::del($key, ...$other_keys);
        }, $keyForTrace);
    }

    /**
     * Check if one or more keys exist with tracing.
     *
     * @param mixed $key Key or array of keys
     * @param mixed ...$other_keys Additional keys
     */
    public function exists(mixed $key, mixed ...$other_keys): \Redis|int|bool
    {
        $keyForTrace = is_array($key) ? implode(',', array_slice($key, 0, 3)) : (string) $key;

        return $this->traceOperation('exists', function () use ($key, $other_keys): \Redis|int|bool {
            return parent::exists($key, ...$other_keys);
        }, $keyForTrace);
    }

    /**
     * Increment a key's value with tracing.
     */
    public function incr(string $key, int $by = 1): \Redis|int|false
    {
        return $this->traceOperation('incr', function () use ($key, $by): \Redis|int|false {
            return parent::incr($key, $by);
        }, $key);
    }

    /**
     * Decrement a key's value with tracing.
     */
    public function decr(string $key, int $by = 1): \Redis|int|false
    {
        return $this->traceOperation('decr', function () use ($key, $by): \Redis|int|false {
            return parent::decr($key, $by);
        }, $key);
    }

    /**
     * Set a key's TTL in seconds with tracing.
     */
    public function expire(string $key, int $timeout, ?string $mode = null): \Redis|bool
    {
        return $this->traceOperation('expire', function () use ($key, $timeout, $mode): \Redis|bool {
            if ($mode !== null) {
                return parent::expire($key, $timeout, $mode);
            }

            return parent::expire($key, $timeout);
        }, $key);
    }

    /**
     * Get TTL of a key with tracing.
     */
    public function ttl(string $key): \Redis|int|false
    {
        return $this->traceOperation('ttl', function () use ($key): \Redis|int|false {
            return parent::ttl($key);
        }, $key);
    }

    /**
     * Find keys matching a pattern with tracing.
     */
    public function keys(string $pattern): \Redis|array|false
    {
        return $this->traceOperation('keys', function () use ($pattern): \Redis|array|false {
            return parent::keys($pattern);
        }, $pattern);
    }

    /**
     * Ping the Redis server with tracing.
     */
    public function ping(?string $message = null): \Redis|bool|string
    {
        return $this->traceOperation('ping', function () use ($message): \Redis|bool|string {
            return parent::ping($message);
        });
    }

    /**
     * Get a hash field with tracing.
     */
    public function hGet(string $key, string $member): \Redis|string|false
    {
        return $this->traceOperation('hget', function () use ($key, $member): \Redis|string|false {
            return parent::hGet($key, $member);
        }, $key);
    }

    /**
     * Set a hash field with tracing.
     */
    public function hSet(string $key, mixed ...$fields_and_vals): \Redis|int|false
    {
        return $this->traceOperation('hset', function () use ($key, $fields_and_vals): \Redis|int|false {
            return parent::hSet($key, ...$fields_and_vals);
        }, $key);
    }

    /**
     * Get all hash fields with tracing.
     */
    public function hGetAll(string $key): \Redis|array|false
    {
        return $this->traceOperation('hgetall', function () use ($key): \Redis|array|false {
            return parent::hGetAll($key);
        }, $key);
    }

    /**
     * Push values to the left of a list with tracing.
     */
    public function lPush(string $key, mixed ...$elements): \Redis|int|false
    {
        return $this->traceOperation('lpush', function () use ($key, $elements): \Redis|int|false {
            return parent::lPush($key, ...$elements);
        }, $key);
    }

    /**
     * Push values to the right of a list with tracing.
     */
    public function rPush(string $key, mixed ...$elements): \Redis|int|false
    {
        return $this->traceOperation('rpush', function () use ($key, $elements): \Redis|int|false {
            return parent::rPush($key, ...$elements);
        }, $key);
    }

    /**
     * Pop value from the left of a list with tracing.
     */
    public function lPop(string $key, int $count = 0): \Redis|bool|string|array
    {
        return $this->traceOperation('lpop', function () use ($key, $count): \Redis|bool|string|array {
            return parent::lPop($key, $count);
        }, $key);
    }

    /**
     * Pop value from the right of a list with tracing.
     */
    public function rPop(string $key, int $count = 0): \Redis|array|string|bool
    {
        return $this->traceOperation('rpop', function () use ($key, $count): \Redis|array|string|bool {
            return parent::rPop($key, $count);
        }, $key);
    }

    /**
     * Add members to a set with tracing.
     */
    public function sAdd(string $key, mixed ...$members): \Redis|int|false
    {
        return $this->traceOperation('sadd', function () use ($key, $members): \Redis|int|false {
            return parent::sAdd($key, ...$members);
        }, $key);
    }

    /**
     * Get all members of a set with tracing.
     */
    public function sMembers(string $key): \Redis|array|false
    {
        return $this->traceOperation('smembers', function () use ($key): \Redis|array|false {
            return parent::sMembers($key);
        }, $key);
    }

    /**
     * Publish a message to a channel with tracing.
     */
    public function publish(string $channel, string $message): \Redis|int|false
    {
        return $this->traceOperation('publish', function () use ($channel, $message): \Redis|int|false {
            return parent::publish($channel, $message);
        }, $channel);
    }

    /**
     * Set value with expiry in seconds with tracing.
     */
    public function setex(string $key, int $expire, mixed $value): \Redis|bool
    {
        return $this->traceOperation('setex', function () use ($key, $expire, $value): \Redis|bool {
            return parent::setex($key, $expire, $value);
        }, $key);
    }

    /**
     * Set value only if key doesn't exist with tracing.
     */
    public function setnx(string $key, mixed $value): \Redis|bool
    {
        return $this->traceOperation('setnx', function () use ($key, $value): \Redis|bool {
            return parent::setnx($key, $value);
        }, $key);
    }

    /**
     * Execute an operation with tracing.
     *
     * @param string $operation Operation name
     * @param callable $callback Operation callback
     * @param string|null $key Optional key for attributes
     * @param bool $trackCacheHit Whether to track cache hit/miss
     *
     * @return mixed Operation result
     */
    private function traceOperation(string $operation, callable $callback, ?string $key = null, bool $trackCacheHit = false): mixed
    {
        // If tracing is not initialized, just execute the operation
        if ($this->tracerFactory === null) {
            return $callback();
        }

        $operationName = $this->getOperation($operation);
        $tracer = $this->tracerFactory->getTracer();

        $span = $tracer->spanBuilder("cache.{$operationName}")
            ->setSpanKind(SpanKind::KIND_CLIENT)
            ->setAttribute('db.system', 'redis')
            ->setAttribute('db.operation', $operationName)
            ->setAttribute('server.address', $this->tracingHost)
            ->setAttribute('server.port', $this->tracingPort)
            ->startSpan();

        if ($key !== null) {
            $span->setAttribute('db.redis.key', $this->sanitizeKey($key));
        }

        $scope = $span->activate();

        try {
            /** @var mixed $result */
            $result = $callback();

            $span->setStatus(StatusCode::STATUS_OK);

            // Add cache hit/miss for GET operations
            if ($trackCacheHit) {
                $hit = $result !== false && $result !== null;
                $span->setAttribute('cache.hit', $hit);
            }

            return $result;
        } catch (\Throwable $e) {
            $span->recordException($e);
            $span->setStatus(StatusCode::STATUS_ERROR, $e->getMessage());
            throw $e;
        } finally {
            $scope->detach();
            $span->end();
        }
    }

    /**
     * Get operation name from method name.
     */
    private function getOperation(string $method): string
    {
        $lowercaseMethod = strtolower($method);

        return self::OPERATION_TYPES[$lowercaseMethod] ?? strtoupper($method);
    }

    /**
     * Sanitize key to prevent PII leakage.
     * Masks potential sensitive parts of keys.
     */
    private function sanitizeKey(string $key): string
    {
        // Keep only the prefix/pattern, mask any UUID-like or ID-like suffixes
        // Example: "session:abc123-def456" -> "session:***"
        // Example: "user:12345:profile" -> "user:***:profile"

        // Mask UUID patterns
        $key = preg_replace(
            '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/i',
            '***',
            $key,
        ) ?? $key;

        // Mask long numeric sequences (likely IDs)
        $key = preg_replace('/:\d{4,}:/', ':***:', $key) ?? $key;
        $key = preg_replace('/:\d{4,}$/', ':***', $key) ?? $key;

        return $key;
    }
}
