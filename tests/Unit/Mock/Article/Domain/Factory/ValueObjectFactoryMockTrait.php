<?php

declare(strict_types=1);

namespace Tests\Unit\Mock\Article\Domain\Factory;

use Micro\Article\Domain\Factory\ValueObjectFactoryInterface;
use Micro\Article\Domain\ValueObject\ArchivedAt;
use Micro\Article\Domain\ValueObject\Description;
use Micro\Article\Domain\ValueObject\EventId;
use Micro\Article\Domain\ValueObject\Article;
use Micro\Article\Domain\ValueObject\PublishedAt;
use Micro\Article\Domain\ValueObject\ShortDescription;
use Micro\Article\Domain\ValueObject\Slug;
use Micro\Article\Domain\ValueObject\Status;
use Micro\Article\Domain\ValueObject\Title;
use Mockery\MockInterface;

/**
 * Mock Factory Trait for ValueObjectFactoryInterface.
 */
trait ValueObjectFactoryMockTrait
{
    protected MockInterface|ValueObjectFactoryInterface $valueObjectFactoryMock;

    /**
     * Create a mock for ValueObjectFactoryInterface.
     */
    protected function createValueObjectFactoryMock(): MockInterface|ValueObjectFactoryInterface
    {
        $this->valueObjectFactoryMock = \Mockery::mock(ValueObjectFactoryInterface::class);

        return $this->valueObjectFactoryMock;
    }

    /**
     * Configure mock to expect makeTitle method call.
     */
    protected function expectValueObjectFactoryMakeTitle(Title $returnValue, int $times = 1): void
    {
        $this->valueObjectFactoryMock
            ->shouldReceive('makeTitle')
            ->with(\Mockery::type('string'))
            ->times($times)
            ->andReturn($returnValue);
    }

    /**
     * Configure mock to expect makeShortDescription method call.
     */
    protected function expectValueObjectFactoryMakeShortDescription(ShortDescription $returnValue, int $times = 1): void
    {
        $this->valueObjectFactoryMock
            ->shouldReceive('makeShortDescription')
            ->with(\Mockery::type('string'))
            ->times($times)
            ->andReturn($returnValue);
    }

    /**
     * Configure mock to expect makeDescription method call.
     */
    protected function expectValueObjectFactoryMakeDescription(Description $returnValue, int $times = 1): void
    {
        $this->valueObjectFactoryMock
            ->shouldReceive('makeDescription')
            ->with(\Mockery::type('string'))
            ->times($times)
            ->andReturn($returnValue);
    }

    /**
     * Configure mock to expect makeSlug method call.
     */
    protected function expectValueObjectFactoryMakeSlug(Slug $returnValue, int $times = 1): void
    {
        $this->valueObjectFactoryMock
            ->shouldReceive('makeSlug')
            ->with(\Mockery::type('string'))
            ->times($times)
            ->andReturn($returnValue);
    }

    /**
     * Configure mock to expect makeEventId method call.
     */
    protected function expectValueObjectFactoryMakeEventId(EventId $returnValue, int $times = 1): void
    {
        $this->valueObjectFactoryMock
            ->shouldReceive('makeEventId')
            ->with(\Mockery::type('int'))
            ->times($times)
            ->andReturn($returnValue);
    }

    /**
     * Configure mock to expect makeStatus method call.
     */
    protected function expectValueObjectFactoryMakeStatus(Status $returnValue, int $times = 1): void
    {
        $this->valueObjectFactoryMock
            ->shouldReceive('makeStatus')
            ->with(\Mockery::type('string'))
            ->times($times)
            ->andReturn($returnValue);
    }

    /**
     * Configure mock to expect makePublishedAt method call.
     */
    protected function expectValueObjectFactoryMakePublishedAt(PublishedAt $returnValue, int $times = 1): void
    {
        $this->valueObjectFactoryMock
            ->shouldReceive('makePublishedAt')
            ->with(\Mockery::type(\DateTimeInterface::class))
            ->times($times)
            ->andReturn($returnValue);
    }

    /**
     * Configure mock to expect makeArchivedAt method call.
     */
    protected function expectValueObjectFactoryMakeArchivedAt(ArchivedAt $returnValue, int $times = 1): void
    {
        $this->valueObjectFactoryMock
            ->shouldReceive('makeArchivedAt')
            ->with(\Mockery::type(\DateTimeInterface::class))
            ->times($times)
            ->andReturn($returnValue);
    }

    /**
     * Configure mock to expect makeArticle method call.
     */
    protected function expectValueObjectFactoryMakeArticle(Article $returnValue, int $times = 1): void
    {
        $this->valueObjectFactoryMock
            ->shouldReceive('makeArticle')
            ->with(\Mockery::type('array'))
            ->times($times)
            ->andReturn($returnValue);
    }

    /**
     * Configure mock to throw exception.
     */
    protected function expectValueObjectFactoryThrowsException(\Throwable $exception, string $method, int $times = 1): void
    {
        $this->valueObjectFactoryMock
            ->shouldReceive($method)
            ->times($times)
            ->andThrow($exception);
    }
}
