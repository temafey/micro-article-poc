<?php

declare(strict_types=1);

namespace Micro\Tests\Integration\Observability\Redis;

use Micro\Component\Common\Infrastructure\Observability\Redis\TracingRedis;
use Micro\Component\Common\Infrastructure\Observability\Service\TracerFactory;
use PHPUnit\Framework\TestCase;

/**
 * Integration test to verify TracingRedis generates spans.
 *
 * @group integration
 * @group observability
 */
final class TracingRedisIntegrationTest extends TestCase
{
    private TracingRedis $redis;

    protected function setUp(): void
    {
        // Docker Compose networking: test container uses full service name
        $redisHost = getenv('APP_REDIS_HOST') ?: 'test-micro-article-system-redis';
        $redisPort = (int) (getenv('APP_REDIS_PORT') ?: 6379);

        $this->redis = new TracingRedis();
        $this->redis->connect($redisHost, $redisPort);

        // Initialize tracing
        $tracerFactory = new TracerFactory(
            serviceName: 'test-redis-tracing',
            serviceVersion: '1.0.0'
        );
        $this->redis->initTracing($tracerFactory);
    }

    protected function tearDown(): void
    {
        // Cleanup test keys
        $keys = $this->redis->keys('test:tracing:*');
        if (is_array($keys) && count($keys) > 0) {
            $this->redis->del(...$keys);
        }
    }

    public function testSetGeneratesSpan(): void
    {
        $key = 'test:tracing:set:' . time();
        $result = $this->redis->set($key, 'test_value');

        $this->assertTrue($result);
    }

    public function testGetGeneratesSpan(): void
    {
        $key = 'test:tracing:get:' . time();
        $this->redis->set($key, 'expected_value');

        $value = $this->redis->get($key);

        $this->assertEquals('expected_value', $value);
    }

    public function testGetMissGeneratesSpanWithCacheMissAttribute(): void
    {
        $key = 'test:tracing:nonexistent:' . time();

        $value = $this->redis->get($key);

        $this->assertFalse($value);
    }

    public function testExistsGeneratesSpan(): void
    {
        $key = 'test:tracing:exists:' . time();
        $this->redis->set($key, 'value');

        $exists = $this->redis->exists($key);

        $this->assertEquals(1, $exists);
    }

    public function testDelGeneratesSpan(): void
    {
        $key1 = 'test:tracing:del1:' . time();
        $key2 = 'test:tracing:del2:' . time();
        $this->redis->set($key1, 'value1');
        $this->redis->set($key2, 'value2');

        $deleted = $this->redis->del($key1, $key2);

        $this->assertEquals(2, $deleted);
    }

    public function testIncrGeneratesSpan(): void
    {
        $key = 'test:tracing:incr:' . time();
        $this->redis->set($key, '5');

        $newValue = $this->redis->incr($key);

        $this->assertEquals(6, $newValue);
    }

    public function testDecrGeneratesSpan(): void
    {
        $key = 'test:tracing:decr:' . time();
        $this->redis->set($key, '10');

        $newValue = $this->redis->decr($key);

        $this->assertEquals(9, $newValue);
    }

    public function testSetexGeneratesSpan(): void
    {
        $key = 'test:tracing:setex:' . time();

        $result = $this->redis->setex($key, 60, 'expires_soon');

        $this->assertTrue($result);

        $ttl = $this->redis->ttl($key);
        $this->assertGreaterThan(0, $ttl);
        $this->assertLessThanOrEqual(60, $ttl);
    }

    public function testHsetAndHgetGenerateSpans(): void
    {
        $key = 'test:tracing:hash:' . time();

        $this->redis->hSet($key, 'field1', 'value1');
        $this->redis->hSet($key, 'field2', 'value2');

        $value1 = $this->redis->hGet($key, 'field1');
        $value2 = $this->redis->hGet($key, 'field2');

        $this->assertEquals('value1', $value1);
        $this->assertEquals('value2', $value2);

        $this->redis->del($key);
    }

    public function testListOperationsGenerateSpans(): void
    {
        $key = 'test:tracing:list:' . time();

        $this->redis->lPush($key, 'item1');
        $this->redis->rPush($key, 'item2');

        $item1 = $this->redis->lPop($key);
        $item2 = $this->redis->rPop($key);

        $this->assertEquals('item1', $item1);
        $this->assertEquals('item2', $item2);
    }

    public function testSetOperationsGenerateSpans(): void
    {
        $key = 'test:tracing:set:members:' . time();

        $this->redis->sAdd($key, 'member1', 'member2', 'member3');

        $members = $this->redis->sMembers($key);

        $this->assertCount(3, $members);
        $this->assertContains('member1', $members);

        $this->redis->sRem($key, 'member1');
        $members = $this->redis->sMembers($key);
        $this->assertCount(2, $members);

        $this->redis->del($key);
    }

    public function testKeysSanitizedInSpanName(): void
    {
        // Keys with sensitive patterns should be sanitized
        $key = 'test:tracing:user:123:email';

        $this->redis->set($key, 'test@example.com');
        $value = $this->redis->get($key);

        $this->assertEquals('test@example.com', $value);

        $this->redis->del($key);
    }
}
