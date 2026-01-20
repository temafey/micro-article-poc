<?php

declare(strict_types=1);

namespace Tests\Unit;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\PhpUnit\ClockMock;

/**
 * Enhanced base class for unit tests.
 *
 * Provides:
 * - Mockery integration with automatic cleanup
 * - Clock mocking for time-sensitive tests
 * - Common assertion helpers for DDD patterns
 * - PHPUnit 10.5+ attribute support
 */
abstract class UnitTestCase extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Whether clock mocking is enabled for this test.
     */
    protected bool $clockMockEnabled = false;

    /**
     * Fixed timestamp for clock mocking.
     */
    protected ?int $fixedTime = null;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->clockMockEnabled) {
            ClockMock::register(static::class);
            ClockMock::withClockMock($this->fixedTime ?? time());
        }
    }

    protected function tearDown(): void
    {
        if ($this->clockMockEnabled) {
            ClockMock::withClockMock(false);
        }

        \Mockery::close();
        parent::tearDown();
    }

    /**
     * Enable clock mocking at a fixed time.
     *
     * @param int|string|\DateTimeInterface $time The time to freeze at
     */
    protected function freezeTime(int|string|\DateTimeInterface $time): void
    {
        $timestamp = match (true) {
            $time instanceof \DateTimeInterface => $time->getTimestamp(),
            is_string($time) => strtotime($time),
            default => $time,
        };

        ClockMock::register(static::class);
        ClockMock::withClockMock($timestamp);
        $this->clockMockEnabled = true;
        $this->fixedTime = $timestamp;
    }

    /**
     * Advance time by a given number of seconds.
     */
    protected function advanceTime(int $seconds): void
    {
        if (! $this->clockMockEnabled) {
            $this->freezeTime(time());
        }

        $this->fixedTime += $seconds;
        ClockMock::withClockMock($this->fixedTime);
    }

    /**
     * Assert that a value is a valid UUID v4.
     */
    protected function assertValidUuid(string $uuid): void
    {
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';
        self::assertMatchesRegularExpression($pattern, $uuid, 'Value is not a valid UUID v4');
    }

    /**
     * Assert that a value object equals expected value.
     *
     * @param object $expected Expected value object
     * @param object $actual   Actual value object
     */
    protected function assertValueObjectEquals(object $expected, object $actual): void
    {
        self::assertEquals(
            method_exists($expected, 'toNative') ? $expected->toNative() : $expected,
            method_exists($actual, 'toNative') ? $actual->toNative() : $actual,
            'Value objects do not match'
        );
    }

    /**
     * Assert that a domain event was recorded.
     *
     * @param object $aggregate  The aggregate that should have recorded the event
     * @param string $eventClass The expected event class name
     */
    protected function assertDomainEventRecorded(object $aggregate, string $eventClass): void
    {
        if (! method_exists($aggregate, 'getUncommittedEvents')) {
            self::fail('Aggregate does not have getUncommittedEvents method');
        }

        $events = $aggregate->getUncommittedEvents();
        $found = false;

        foreach ($events as $event) {
            if ($event instanceof $eventClass) {
                $found = true;
                break;
            }
        }

        self::assertTrue($found, sprintf('Expected domain event %s was not recorded', $eventClass));
    }

    /**
     * Assert that no domain events were recorded.
     *
     * @param object $aggregate The aggregate to check
     */
    protected function assertNoDomainEventsRecorded(object $aggregate): void
    {
        if (! method_exists($aggregate, 'getUncommittedEvents')) {
            self::fail('Aggregate does not have getUncommittedEvents method');
        }

        $events = $aggregate->getUncommittedEvents();
        self::assertEmpty($events, 'Expected no domain events but some were recorded');
    }

    /**
     * Create a Mockery mock with default configuration.
     *
     * Note: Named mockeryMock to avoid conflict with PHPUnit's createMock().
     *
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T&Mockery\MockInterface
     */
    protected function mockeryMock(string $class): Mockery\MockInterface
    {
        return \Mockery::mock($class);
    }

    /**
     * Create a Mockery spy for verifying interactions.
     *
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T&Mockery\MockInterface
     */
    protected function mockerySpy(string $class): Mockery\MockInterface
    {
        return \Mockery::spy($class);
    }

    /**
     * Create a partial Mockery mock allowing real method calls.
     *
     * Note: Named mockeryPartial to avoid conflict with PHPUnit's createPartialMock().
     *
     * @template T of object
     *
     * @param class-string<T> $class
     * @param array<string>   $methods Methods to mock (others call real implementation)
     *
     * @return T&Mockery\MockInterface
     */
    protected function mockeryPartial(string $class, array $methods = []): Mockery\MockInterface
    {
        $mock = \Mockery::mock($class)->makePartial();

        foreach ($methods as $method) {
            $mock->shouldReceive($method)
                ->byDefault();
        }

        return $mock;
    }
}
