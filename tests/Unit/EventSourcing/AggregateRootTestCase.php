<?php

declare(strict_types=1);

namespace Tests\Unit\EventSourcing;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Tests\Unit\UnitTestCase;

/**
 * Base class for testing Event Sourced Aggregates.
 *
 * Provides:
 * - Given-When-Then test pattern for event sourcing
 * - Domain event assertions
 * - Aggregate reconstitution helpers
 * - Event metadata handling
 */
abstract class AggregateRootTestCase extends UnitTestCase
{
    /**
     * Events to apply as "given" state.
     *
     * @var array<object>
     */
    protected array $givenEvents = [];

    /**
     * The aggregate under test.
     */
    protected ?object $aggregate = null;

    /**
     * The aggregate ID.
     */
    protected ?string $aggregateId = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->givenEvents = [];
        $this->aggregate = null;
        $this->aggregateId = null;
    }

    /**
     * Set up the aggregate with given past events.
     *
     * @param object ...$events Past events to apply
     */
    protected function given(object ...$events): static
    {
        $this->givenEvents = $events;

        return $this;
    }

    /**
     * Execute a command/action on the aggregate.
     *
     * @param callable $action The action to execute
     */
    protected function when(callable $action): static
    {
        // Create and reconstitute aggregate from given events if any
        if ($this->givenEvents !== []) {
            $this->aggregate = $this->reconstituteAggregate($this->givenEvents);
        }

        // Execute the action
        $action($this->aggregate);

        return $this;
    }

    /**
     * Assert that specific events were recorded.
     *
     * @param object ...$expectedEvents Expected events
     */
    protected function then(object ...$expectedEvents): void
    {
        $recordedEvents = $this->getRecordedEvents();

        self::assertCount(
            count($expectedEvents),
            $recordedEvents,
            sprintf('Expected %d events, got %d', count($expectedEvents), count($recordedEvents))
        );

        foreach ($expectedEvents as $index => $expected) {
            $actual = $recordedEvents[$index] ?? null;

            self::assertNotNull($actual, sprintf('Event at index %d was not recorded', $index));
            self::assertInstanceOf(
                $expected::class,
                $actual,
                sprintf('Event at index %d is not of expected type', $index)
            );

            $this->assertEventEquals($expected, $actual);
        }
    }

    /**
     * Assert that no events were recorded.
     */
    protected function thenNothing(): void
    {
        $recordedEvents = $this->getRecordedEvents();

        self::assertEmpty($recordedEvents, sprintf('Expected no events, got %d', count($recordedEvents)));
    }

    /**
     * Assert that a specific exception was thrown.
     *
     * @param class-string<\Throwable> $exceptionClass
     */
    protected function thenException(string $exceptionClass, ?string $message = null): void
    {
        $this->expectException($exceptionClass);

        if ($message !== null) {
            $this->expectExceptionMessage($message);
        }
    }

    /**
     * Get recorded (uncommitted) events from the aggregate.
     *
     * @return array<object>
     */
    protected function getRecordedEvents(): array
    {
        if ($this->aggregate === null) {
            return [];
        }

        if (method_exists($this->aggregate, 'getUncommittedEvents')) {
            $stream = $this->aggregate->getUncommittedEvents();

            if ($stream instanceof DomainEventStream) {
                $events = [];
                foreach ($stream as $message) {
                    if ($message instanceof DomainMessage) {
                        $events[] = $message->getPayload();
                    }
                }

                return $events;
            }

            return is_array($stream) ? $stream : iterator_to_array($stream);
        }

        return [];
    }

    /**
     * Reconstitute an aggregate from past events.
     *
     * @param array<object> $events
     */
    abstract protected function reconstituteAggregate(array $events): object;

    /**
     * Create the aggregate class being tested.
     *
     * @return class-string
     */
    abstract protected function getAggregateClass(): string;

    /**
     * Assert that two events are equal.
     */
    protected function assertEventEquals(object $expected, object $actual): void
    {
        // Compare event properties
        $expectedProps = $this->getEventProperties($expected);
        $actualProps = $this->getEventProperties($actual);

        foreach ($expectedProps as $prop => $value) {
            if ($prop === 'occurredOn' || $prop === 'recordedOn') {
                // Skip timestamp comparisons
                continue;
            }

            self::assertArrayHasKey($prop, $actualProps, sprintf('Event missing property: %s', $prop));

            // Handle value objects
            $expectedValue = $this->normalizeValue($value);
            $actualValue = $this->normalizeValue($actualProps[$prop]);

            self::assertEquals($expectedValue, $actualValue, sprintf('Event property %s does not match', $prop));
        }
    }

    /**
     * Get event properties for comparison.
     *
     * @return array<string, mixed>
     */
    protected function getEventProperties(object $event): array
    {
        $properties = [];

        // Use reflection to get all properties
        $reflection = new \ReflectionClass($event);

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $properties[$property->getName()] = $property->getValue($event);
        }

        return $properties;
    }

    /**
     * Normalize a value for comparison.
     */
    protected function normalizeValue(mixed $value): mixed
    {
        if (is_object($value)) {
            // Check for common value object methods
            if (method_exists($value, 'toNative')) {
                return $value->toNative();
            }
            if (method_exists($value, 'toString')) {
                return $value->toString();
            }
            if (method_exists($value, '__toString')) {
                return (string) $value;
            }
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d H:i:s');
            }
        }

        return $value;
    }

    /**
     * Create a domain event stream from events.
     *
     * @param array<object> $events
     */
    protected function createEventStream(string $aggregateId, array $events): DomainEventStream
    {
        $messages = [];
        $playhead = -1;

        foreach ($events as $event) {
            ++$playhead;
            $messages[] = DomainMessage::recordNow($aggregateId, $playhead, new Metadata([]), $event);
        }

        return new DomainEventStream($messages);
    }

    /**
     * Assert that the aggregate is in a specific state.
     *
     * @param array<string, mixed> $expectedState
     */
    protected function assertAggregateState(array $expectedState): void
    {
        self::assertNotNull($this->aggregate, 'Aggregate not created');

        $aggregate = $this->aggregate;

        foreach ($expectedState as $property => $expectedValue) {
            // Try getter method first
            $getter = 'get' . ucfirst($property);
            if (method_exists($aggregate, $getter)) {
                /** @phpstan-ignore method.dynamicName */
                $actualValue = $aggregate->{$getter}();
            } else {
                // Use reflection
                $reflection = new \ReflectionClass($aggregate);
                $prop = $reflection->getProperty($property);
                $prop->setAccessible(true);
                $actualValue = $prop->getValue($aggregate);
            }

            $normalizedExpected = $this->normalizeValue($expectedValue);
            $normalizedActual = $this->normalizeValue($actualValue);

            self::assertEquals(
                $normalizedExpected,
                $normalizedActual,
                sprintf('Aggregate state %s does not match expected value', $property)
            );
        }
    }
}
