<?php

declare(strict_types=1);

namespace Tests\Unit\Traits;

use Mockery\MockInterface;

/**
 * Trait for testing CQRS Command Handlers.
 *
 * Provides:
 * - Common mock factory patterns
 * - Command/Handler wiring helpers
 * - Assertion methods for handler behavior
 */
trait CommandHandlerTestTrait
{
    /**
     * Mock storage for created mocks.
     *
     * @var array<string, MockInterface>
     */
    protected array $mocks = [];

    /**
     * Register a mock for later retrieval.
     *
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T&MockInterface
     */
    protected function registerMock(string $class, ?string $alias = null): MockInterface
    {
        $mock = \Mockery::mock($class);
        $key = $alias ?? $class;
        $this->mocks[$key] = $mock;

        return $mock;
    }

    /**
     * Get a previously registered mock.
     *
     * @template T of object
     *
     * @param class-string<T>|string $classOrAlias
     *
     * @return T&MockInterface
     */
    protected function getMock(string $classOrAlias): MockInterface
    {
        if (! isset($this->mocks[$classOrAlias])) {
            throw new \RuntimeException(sprintf('Mock for %s not registered', $classOrAlias));
        }

        return $this->mocks[$classOrAlias];
    }

    /**
     * Configure mock to expect a method call.
     *
     * @param array<mixed>|null $withArgs
     */
    protected function expectMockMethod(
        MockInterface $mock,
        string $method,
        mixed $returnValue = null,
        ?array $withArgs = null,
        int $times = 1,
    ): MockInterface {
        $expectation = $mock->shouldReceive($method)
            ->times($times);

        if ($withArgs !== null) {
            $expectation->withArgs($withArgs);
        }

        if ($returnValue !== null) {
            $expectation->andReturn($returnValue);
        }

        return $mock;
    }

    /**
     * Configure mock to throw an exception.
     *
     * @param array<mixed>|null $withArgs
     */
    protected function expectMockException(
        MockInterface $mock,
        string $method,
        \Throwable $exception,
        ?array $withArgs = null,
        int $times = 1,
    ): MockInterface {
        $expectation = $mock->shouldReceive($method)
            ->times($times);

        if ($withArgs !== null) {
            $expectation->withArgs($withArgs);
        }

        $expectation->andThrow($exception);

        return $mock;
    }

    /**
     * Assert that handler completes without exception.
     */
    protected function assertHandlerSucceeds(callable $handler, object $command): void
    {
        $exception = null;

        try {
            $handler($command);
        } catch (\Throwable $e) {
            $exception = $e;
        }

        self::assertNull(
            $exception,
            sprintf('Handler threw exception: %s', $exception?->getMessage() ?? 'Unknown')
        );
    }

    /**
     * Assert that handler throws specific exception.
     *
     * @param class-string<\Throwable> $exceptionClass
     */
    protected function assertHandlerThrows(
        callable $handler,
        object $command,
        string $exceptionClass,
        ?string $message = null,
    ): void {
        $this->expectException($exceptionClass);

        if ($message !== null) {
            $this->expectExceptionMessage($message);
        }

        $handler($command);
    }

    /**
     * Assert that handler returns expected result.
     */
    protected function assertHandlerReturns(callable $handler, object $command, mixed $expected): void
    {
        $result = $handler($command);

        self::assertEquals($expected, $result);
    }

    /**
     * Create command handler with injected dependencies.
     *
     * @param class-string         $handlerClass
     * @param array<string, mixed> $dependencies Dependency name => mock/value pairs
     */
    protected function createHandler(string $handlerClass, array $dependencies = []): object
    {
        $reflection = new \ReflectionClass($handlerClass);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return new $handlerClass();
        }

        $args = [];
        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType();

            if (isset($dependencies[$name])) {
                $args[] = $dependencies[$name];
            } elseif ($type instanceof \ReflectionNamedType && ! $type->isBuiltin()) {
                $typeName = $type->getName();
                $args[] = $this->mocks[$typeName] ?? \Mockery::mock($typeName);
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            } else {
                throw new \RuntimeException(sprintf('Cannot resolve parameter %s for %s', $name, $handlerClass));
            }
        }

        return $reflection->newInstanceArgs($args);
    }

    /**
     * Verify all mock expectations.
     */
    protected function verifyMockExpectations(): void
    {
        foreach ($this->mocks as $mock) {
            $mock->shouldHaveReceived();
        }
    }
}
