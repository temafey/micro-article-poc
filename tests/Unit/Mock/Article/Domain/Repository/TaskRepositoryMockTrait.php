<?php

declare(strict_types=1);

namespace Tests\Unit\Mock\Article\Domain\Repository;

use Micro\Article\Domain\Repository\TaskRepositoryInterface;
use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Mockery\MockInterface;

/**
 * Mock Factory Trait for TaskRepositoryInterface.
 */
trait TaskRepositoryMockTrait
{
    protected MockInterface|TaskRepositoryInterface $taskRepositoryMock;

    /**
     * Create a mock for TaskRepositoryInterface.
     */
    protected function createTaskRepositoryMock(): MockInterface|TaskRepositoryInterface
    {
        $this->taskRepositoryMock = \Mockery::mock(TaskRepositoryInterface::class);

        return $this->taskRepositoryMock;
    }

    /**
     * Configure mock to expect addArticleCreateTask method call.
     */
    protected function expectTaskRepositoryAddArticleCreateTask(int $times = 1): void
    {
        $this->taskRepositoryMock
            ->shouldReceive('addArticleCreateTask')
            ->with(\Mockery::type(ProcessUuid::class), \Mockery::type(Article::class))
            ->times($times);
    }

    /**
     * Configure mock to expect addArticleUpdateTask method call.
     */
    protected function expectTaskRepositoryAddArticleUpdateTask(int $times = 1): void
    {
        $this->taskRepositoryMock
            ->shouldReceive('addArticleUpdateTask')
            ->with(
                \Mockery::type(ProcessUuid::class),
                \Mockery::type(Uuid::class),
                \Mockery::type(Article::class)
            )
            ->times($times);
    }

    /**
     * Configure mock to expect addArticlePublishTask method call.
     */
    protected function expectTaskRepositoryAddArticlePublishTask(int $times = 1): void
    {
        $this->taskRepositoryMock
            ->shouldReceive('addArticlePublishTask')
            ->with(\Mockery::type(ProcessUuid::class), \Mockery::type(Uuid::class))
            ->times($times);
    }

    /**
     * Configure mock to expect addArticleUnpublishTask method call.
     */
    protected function expectTaskRepositoryAddArticleUnpublishTask(int $times = 1): void
    {
        $this->taskRepositoryMock
            ->shouldReceive('addArticleUnpublishTask')
            ->with(\Mockery::type(ProcessUuid::class), \Mockery::type(Uuid::class))
            ->times($times);
    }

    /**
     * Configure mock to expect addArticleArchiveTask method call.
     */
    protected function expectTaskRepositoryAddArticleArchiveTask(int $times = 1): void
    {
        $this->taskRepositoryMock
            ->shouldReceive('addArticleArchiveTask')
            ->with(\Mockery::type(ProcessUuid::class), \Mockery::type(Uuid::class))
            ->times($times);
    }

    /**
     * Configure mock to expect addArticleDeleteTask method call.
     */
    protected function expectTaskRepositoryAddArticleDeleteTask(int $times = 1): void
    {
        $this->taskRepositoryMock
            ->shouldReceive('addArticleDeleteTask')
            ->with(\Mockery::type(ProcessUuid::class), \Mockery::type(Uuid::class))
            ->times($times);
    }

    /**
     * Configure mock to throw exception on any task method.
     */
    protected function expectTaskRepositoryThrowsException(\Throwable $exception, string $method, int $times = 1): void
    {
        $this->taskRepositoryMock
            ->shouldReceive($method)
            ->times($times)
            ->andThrow($exception);
    }
}
