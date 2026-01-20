<?php

declare(strict_types=1);

namespace Micro\Tests\Unit\Common\Infrastructure\Outbox\Publisher;

use Enqueue\Client\ProducerInterface;
use Micro\Component\Common\Domain\Outbox\OutboxEntryInterface;
use Micro\Component\Common\Domain\Outbox\OutboxMessageType;
use Micro\Component\Common\Infrastructure\Outbox\OutboxEntry;
use Micro\Component\Common\Infrastructure\Outbox\Publisher\OutboxPublishException;
use Micro\Component\Common\Infrastructure\Outbox\Publisher\TaskPublisher;
use MicroModule\Task\Application\Processor\JobCommandBusProcessor;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Unit tests for TaskPublisher.
 *
 * @see TaskPublisher
 */
#[CoversClass(TaskPublisher::class)]
final class TaskPublisherTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ProducerInterface&MockInterface $taskProducer;
    private LoggerInterface&MockInterface $logger;
    private TaskPublisher $publisher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->taskProducer = Mockery::mock(ProducerInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->publisher = new TaskPublisher(
            $this->taskProducer,
            $this->logger,
        );
    }

    // =========================================================================
    // supports() Tests
    // =========================================================================

    #[Test]
    public function supportsTrueForTaskMessageType(): void
    {
        self::assertTrue($this->publisher->supports(OutboxMessageType::TASK->value));
    }

    #[Test]
    public function supportsFalseForEventMessageType(): void
    {
        self::assertFalse($this->publisher->supports(OutboxMessageType::EVENT->value));
    }

    #[Test]
    public function supportsFalseForUnknownMessageType(): void
    {
        self::assertFalse($this->publisher->supports('unknown'));
    }

    // =========================================================================
    // publish() Tests
    // =========================================================================

    #[Test]
    public function publishValidTaskCommand(): void
    {
        $payload = json_encode([
            'type' => 'article.create.command',
            'args' => ['process-uuid', 'entity-uuid', ['title' => 'Test']],
        ], JSON_THROW_ON_ERROR);

        $entry = $this->createTaskEntry($payload);
        $expectedRoute = JobCommandBusProcessor::getRoute();

        $this->taskProducer
            ->shouldReceive('sendCommand')
            ->once()
            ->with($expectedRoute, Mockery::on(function (array $p) {
                return $p['type'] === 'article.create.command'
                    && is_array($p['args'])
                    && count($p['args']) === 3;
            }));

        $this->logger
            ->shouldReceive('debug')
            ->once()
            ->with('Task published from outbox', Mockery::type('array'));

        $this->publisher->publish($entry);
    }

    #[Test]
    public function publishUsesJobCommandBusProcessorRoute(): void
    {
        $payload = json_encode([
            'type' => 'task.command',
            'args' => ['arg1'],
        ], JSON_THROW_ON_ERROR);

        $entry = $this->createTaskEntry($payload);
        $expectedRoute = JobCommandBusProcessor::getRoute();

        $this->taskProducer
            ->shouldReceive('sendCommand')
            ->once()
            ->with($expectedRoute, Mockery::any());

        $this->logger->shouldReceive('debug')->once();

        $this->publisher->publish($entry);
    }

    #[Test]
    public function publishThrowsOnInvalidJsonPayload(): void
    {
        $entry = $this->createTaskEntry('invalid{json');

        $this->expectException(OutboxPublishException::class);
        $this->expectExceptionMessage('Invalid JSON payload');

        $this->publisher->publish($entry);
    }

    #[Test]
    public function publishThrowsOnMissingTypeKey(): void
    {
        $payload = json_encode([
            'args' => ['arg1', 'arg2'],
        ], JSON_THROW_ON_ERROR);

        $entry = $this->createTaskEntry($payload);

        $this->expectException(OutboxPublishException::class);
        $this->expectExceptionMessage('Missing required key: type');

        $this->publisher->publish($entry);
    }

    #[Test]
    public function publishThrowsOnMissingArgsKey(): void
    {
        $payload = json_encode([
            'type' => 'article.create.command',
        ], JSON_THROW_ON_ERROR);

        $entry = $this->createTaskEntry($payload);

        $this->expectException(OutboxPublishException::class);
        $this->expectExceptionMessage('Missing or invalid key: args');

        $this->publisher->publish($entry);
    }

    #[Test]
    public function publishThrowsOnArgsNotArray(): void
    {
        $payload = json_encode([
            'type' => 'article.create.command',
            'args' => 'string-not-array',
        ], JSON_THROW_ON_ERROR);

        $entry = $this->createTaskEntry($payload);

        $this->expectException(OutboxPublishException::class);
        $this->expectExceptionMessage('Missing or invalid key: args (expected array)');

        $this->publisher->publish($entry);
    }

    #[Test]
    public function publishLogsCorrectContext(): void
    {
        $payload = json_encode([
            'type' => 'article.update.command',
            'args' => ['uuid-1', 'uuid-2'],
        ], JSON_THROW_ON_ERROR);

        $entry = $this->createTaskEntry($payload);

        $this->taskProducer->shouldReceive('sendCommand')->once();

        $this->logger
            ->shouldReceive('debug')
            ->once()
            ->with('Task published from outbox', Mockery::on(function (array $context) use ($entry) {
                return $context['message_id'] === $entry->getId()
                    && $context['command_type'] === 'article.update.command'
                    && $context['routing_key'] === $entry->getRoutingKey()
                    && $context['route'] === JobCommandBusProcessor::getRoute();
            }));

        $this->publisher->publish($entry);
    }

    #[Test]
    public function publishPreservesFullPayload(): void
    {
        $payload = json_encode([
            'type' => 'complex.command',
            'args' => ['arg1', 'arg2', ['nested' => 'data']],
            'extra' => 'field',
        ], JSON_THROW_ON_ERROR);

        $entry = $this->createTaskEntry($payload);

        $this->taskProducer
            ->shouldReceive('sendCommand')
            ->once()
            ->with(Mockery::any(), Mockery::on(function (array $p) {
                // Full payload should be sent including extra fields
                return isset($p['type'])
                    && isset($p['args'])
                    && isset($p['extra']);
            }));

        $this->logger->shouldReceive('debug')->once();

        $this->publisher->publish($entry);
    }

    #[Test]
    public function publishWithEmptyArgsArray(): void
    {
        $payload = json_encode([
            'type' => 'simple.command',
            'args' => [],
        ], JSON_THROW_ON_ERROR);

        $entry = $this->createTaskEntry($payload);

        $this->taskProducer->shouldReceive('sendCommand')->once();
        $this->logger->shouldReceive('debug')->once();

        // Empty args array is valid
        $this->publisher->publish($entry);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    private function createTaskEntry(string $payload): OutboxEntryInterface
    {
        return OutboxEntry::createForTask(
            aggregateType: 'Article',
            aggregateId: 'aggregate-123',
            commandType: 'article.command',
            commandPayload: $payload,
            topic: 'job_command_bus',
            routingKey: JobCommandBusProcessor::getRoute(),
        );
    }
}
