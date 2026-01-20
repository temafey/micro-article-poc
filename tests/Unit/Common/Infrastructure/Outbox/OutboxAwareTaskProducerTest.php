<?php

declare(strict_types=1);

namespace Tests\Unit\Common\Infrastructure\Outbox;

use Enqueue\Client\Message;
use Enqueue\Client\ProducerInterface;
use Enqueue\Rpc\Promise;
use InvalidArgumentException;
use Micro\Component\Common\Domain\Outbox\OutboxEntryInterface;
use Micro\Component\Common\Domain\Outbox\OutboxMessageType;
use Micro\Component\Common\Domain\Outbox\OutboxRepositoryInterface;
use Micro\Component\Common\Infrastructure\Outbox\OutboxAwareTaskProducer;
use MicroModule\Task\Application\Processor\JobCommandBusProcessor;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

/**
 * Unit tests for OutboxAwareTaskProducer decorator.
 *
 * Tests the transactional outbox pattern for task commands.
 */
#[CoversClass(OutboxAwareTaskProducer::class)]
final class OutboxAwareTaskProducerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ProducerInterface&MockInterface $innerProducer;
    private OutboxRepositoryInterface&MockInterface $outboxRepository;
    private LoggerInterface&MockInterface $logger;
    private OutboxAwareTaskProducer $producer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->innerProducer = Mockery::mock(ProducerInterface::class);
        $this->outboxRepository = Mockery::mock(OutboxRepositoryInterface::class);
        $this->logger = Mockery::mock(LoggerInterface::class);

        $this->producer = new OutboxAwareTaskProducer(
            $this->innerProducer,
            $this->outboxRepository,
            $this->logger,
            true, // enabled by default
        );
    }

    // ========================================================================
    // Constructor and Default State Tests
    // ========================================================================

    #[Test]
    public function isEnabledByDefault(): void
    {
        self::assertTrue($this->producer->isOutboxEnabled());
    }

    #[Test]
    public function canBeConstructedAsDisabled(): void
    {
        $producer = new OutboxAwareTaskProducer(
            $this->innerProducer,
            $this->outboxRepository,
            $this->logger,
            false,
        );

        self::assertFalse($producer->isOutboxEnabled());
    }

    // ========================================================================
    // enable() / disable() / isOutboxEnabled() Tests
    // ========================================================================

    #[Test]
    public function disableSetsEnabledToFalse(): void
    {
        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Outbox task producer disabled');

        $this->producer->disable();

        self::assertFalse($this->producer->isOutboxEnabled());
    }

    #[Test]
    public function enableSetsEnabledToTrue(): void
    {
        // First disable it
        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Outbox task producer disabled');
        $this->producer->disable();

        // Then enable it
        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Outbox task producer enabled');
        $this->producer->enable();

        self::assertTrue($this->producer->isOutboxEnabled());
    }

    // ========================================================================
    // sendCommand() Tests - Outbox Writing
    // ========================================================================

    #[Test]
    public function sendCommandWritesToOutboxWhenEnabled(): void
    {
        $processUuid = Uuid::uuid4()->toString();
        $entityUuid = Uuid::uuid4()->toString();

        $command = 'job_command_bus';
        $message = [
            'type' => 'article.create.command',
            'args' => [
                $processUuid,
                $entityUuid,
                ['title' => 'Test Title'],
            ],
        ];

        $this->outboxRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (OutboxEntryInterface $entry) use ($entityUuid) {
                $data = $entry->toArray();

                return $data['message_type'] === 'TASK'
                    && $data['aggregate_type'] === 'Article'
                    && $data['aggregate_id'] === $entityUuid
                    && $data['event_type'] === 'article.create.command'
                    && $data['topic'] === 'job_command_bus';
            }));

        $this->logger
            ->shouldReceive('debug')
            ->twice(); // Task written + entry created

        $result = $this->producer->sendCommand($command, $message);

        self::assertNull($result);
    }

    #[Test]
    public function sendCommandDelegatesToInnerWhenDisabled(): void
    {
        $command = 'job_command_bus';
        $message = ['type' => 'article.create.command'];

        $this->logger
            ->shouldReceive('info')
            ->once()
            ->with('Outbox task producer disabled');
        $this->producer->disable();

        $this->logger
            ->shouldReceive('debug')
            ->once()
            ->with('Outbox disabled, sending command directly', Mockery::any());

        $this->innerProducer
            ->shouldReceive('sendCommand')
            ->once()
            ->with($command, $message, false)
            ->andReturn(null);

        // Outbox repository should NOT be called
        $this->outboxRepository->shouldNotReceive('save');

        $result = $this->producer->sendCommand($command, $message);

        self::assertNull($result);
    }

    #[Test]
    public function sendCommandThrowsExceptionWhenNeedReplyIsTrue(): void
    {
        $command = 'job_command_bus';
        $message = ['type' => 'test.command'];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Reply-based commands are not supported with outbox pattern');

        $this->producer->sendCommand($command, $message, true);
    }

    #[Test]
    public function sendCommandAllowsNeedReplyWhenDisabled(): void
    {
        $command = 'job_command_bus';
        $message = ['type' => 'test.command'];

        $this->logger
            ->shouldReceive('info')
            ->once();
        $this->producer->disable();

        $this->logger
            ->shouldReceive('debug')
            ->once();

        $mockPromise = Mockery::mock(Promise::class);

        $this->innerProducer
            ->shouldReceive('sendCommand')
            ->once()
            ->with($command, $message, true)
            ->andReturn($mockPromise);

        $result = $this->producer->sendCommand($command, $message, true);

        self::assertSame($mockPromise, $result);
    }

    // ========================================================================
    // sendEvent() Tests - Always Delegates
    // ========================================================================

    #[Test]
    public function sendEventDelegatesToInnerProducer(): void
    {
        $topic = 'events.article';
        $message = ['event' => 'data'];

        $this->innerProducer
            ->shouldReceive('sendEvent')
            ->once()
            ->with($topic, $message);

        // No outbox interaction for events
        $this->outboxRepository->shouldNotReceive('save');

        $this->producer->sendEvent($topic, $message);
    }

    #[Test]
    public function sendEventAlwaysDelegatesEvenWhenOutboxEnabled(): void
    {
        // Ensure outbox is enabled
        self::assertTrue($this->producer->isOutboxEnabled());

        $topic = 'events.article';
        $message = ['event' => 'data'];

        $this->innerProducer
            ->shouldReceive('sendEvent')
            ->once()
            ->with($topic, $message);

        $this->producer->sendEvent($topic, $message);
    }

    // ========================================================================
    // Payload Normalization Tests
    // ========================================================================

    #[Test]
    public function sendCommandNormalizesArrayPayload(): void
    {
        $command = 'job_command_bus';
        $message = ['type' => 'article.create.command', 'data' => 'test'];

        $this->outboxRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (OutboxEntryInterface $entry) {
                $data = $entry->toArray();
                $payload = json_decode($data['event_payload'], true);

                return $payload['type'] === 'article.create.command'
                    && $payload['data'] === 'test';
            }));

        $this->logger->shouldReceive('debug')->twice();

        $this->producer->sendCommand($command, $message);
    }

    #[Test]
    public function sendCommandNormalizesMessageObject(): void
    {
        $command = 'job_command_bus';
        $message = new Message(['type' => 'article.create.command', 'value' => 42]);

        $this->outboxRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (OutboxEntryInterface $entry) {
                $data = $entry->toArray();
                $payload = json_decode($data['event_payload'], true);

                return $payload['type'] === 'article.create.command'
                    && $payload['value'] === 42;
            }));

        $this->logger->shouldReceive('debug')->twice();

        $this->producer->sendCommand($command, $message);
    }

    #[Test]
    public function sendCommandNormalizesJsonStringPayload(): void
    {
        $command = 'job_command_bus';
        $message = '{"type":"article.create.command","key":"value"}';

        $this->outboxRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (OutboxEntryInterface $entry) {
                $data = $entry->toArray();
                $payload = json_decode($data['event_payload'], true);

                return $payload['type'] === 'article.create.command'
                    && $payload['key'] === 'value';
            }));

        $this->logger->shouldReceive('debug')->twice();

        $this->producer->sendCommand($command, $message);
    }

    #[Test]
    public function sendCommandNormalizesNonJsonStringPayload(): void
    {
        $command = 'job_command_bus';
        $message = 'plain-text-payload';

        $this->outboxRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (OutboxEntryInterface $entry) {
                $data = $entry->toArray();
                $payload = json_decode($data['event_payload'], true);

                return $payload['data'] === 'plain-text-payload';
            }));

        $this->logger->shouldReceive('debug')->twice();

        $this->producer->sendCommand($command, $message);
    }

    #[Test]
    public function sendCommandNormalizesScalarPayload(): void
    {
        $command = 'job_command_bus';
        $message = 12345;

        $this->outboxRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (OutboxEntryInterface $entry) {
                $data = $entry->toArray();
                $payload = json_decode($data['event_payload'], true);

                return $payload['data'] === 12345;
            }));

        $this->logger->shouldReceive('debug')->twice();

        $this->producer->sendCommand($command, $message);
    }

    #[Test]
    public function sendCommandNormalizesMessageObjectWithStringBody(): void
    {
        $command = 'job_command_bus';
        $message = new Message('string-body');

        $this->outboxRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (OutboxEntryInterface $entry) {
                $data = $entry->toArray();
                $payload = json_decode($data['event_payload'], true);

                return $payload['data'] === 'string-body';
            }));

        $this->logger->shouldReceive('debug')->twice();

        $this->producer->sendCommand($command, $message);
    }

    // ========================================================================
    // Aggregate ID Extraction Tests
    // ========================================================================

    #[Test]
    public function extractsEntityUuidAsAggregateIdFromSecondArg(): void
    {
        $entityUuid = Uuid::uuid4()->toString();
        $processUuid = Uuid::uuid4()->toString();

        $message = [
            'type' => 'article.update.command',
            'args' => [$processUuid, $entityUuid, ['data' => 'test']],
        ];

        $this->outboxRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (OutboxEntryInterface $entry) use ($entityUuid) {
                $data = $entry->toArray();

                return $data['aggregate_id'] === $entityUuid;
            }));

        $this->logger->shouldReceive('debug')->twice();

        $this->producer->sendCommand('job_command_bus', $message);
    }

    #[Test]
    public function extractsProcessUuidAsAggregateIdFromFirstArgIfSecondIsNotUuid(): void
    {
        $processUuid = Uuid::uuid4()->toString();

        $message = [
            'type' => 'article.create.command',
            'args' => [$processUuid, 'not-a-uuid', ['data' => 'test']],
        ];

        $this->outboxRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (OutboxEntryInterface $entry) use ($processUuid) {
                $data = $entry->toArray();

                return $data['aggregate_id'] === $processUuid;
            }));

        $this->logger->shouldReceive('debug')->twice();

        $this->producer->sendCommand('job_command_bus', $message);
    }

    #[Test]
    public function generatesUuidWhenNoValidUuidInArgs(): void
    {
        $message = [
            'type' => 'article.create.command',
            'args' => ['not-a-uuid', 'also-not-a-uuid'],
        ];

        $this->outboxRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (OutboxEntryInterface $entry) {
                $data = $entry->toArray();
                // Should be a valid UUID (generated)

                return Uuid::isValid($data['aggregate_id']);
            }));

        $this->logger->shouldReceive('debug')->twice();

        $this->producer->sendCommand('job_command_bus', $message);
    }

    #[Test]
    public function generatesUuidWhenArgsIsEmpty(): void
    {
        $message = [
            'type' => 'article.create.command',
            'args' => [],
        ];

        $this->outboxRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (OutboxEntryInterface $entry) {
                $data = $entry->toArray();

                return Uuid::isValid($data['aggregate_id']);
            }));

        $this->logger->shouldReceive('debug')->twice();

        $this->producer->sendCommand('job_command_bus', $message);
    }

    // ========================================================================
    // Aggregate Type Extraction Tests
    // ========================================================================

    #[Test]
    #[DataProvider('aggregateTypeExtractionDataProvider')]
    public function extractsAggregateTypeFromCommandType(string $commandType, string $expectedType): void
    {
        $message = ['type' => $commandType, 'args' => []];

        $this->outboxRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (OutboxEntryInterface $entry) use ($expectedType) {
                $data = $entry->toArray();

                return $data['aggregate_type'] === $expectedType;
            }));

        $this->logger->shouldReceive('debug')->twice();

        $this->producer->sendCommand('job_command_bus', $message);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function aggregateTypeExtractionDataProvider(): array
    {
        return [
            'article.create.command' => ['article.create.command', 'Article'],
            'identity.user.create.command' => ['identity.user.create.command', 'Identity'],
            'order.process.command' => ['order.process.command', 'Order'],
            'UPPERCASE.command' => ['UPPERCASE.command', 'UPPERCASE'],
            'single-word' => ['single', 'Single'],
            'empty-parts' => ['.create.command', 'Unknown'],
            'empty-string' => ['', 'Unknown'],
        ];
    }

    // ========================================================================
    // Topic and Routing Key Tests
    // ========================================================================

    #[Test]
    public function usesCommandAsTopicName(): void
    {
        $command = 'custom_command_route';
        $message = ['type' => 'test.command'];

        $this->outboxRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (OutboxEntryInterface $entry) use ($command) {
                $data = $entry->toArray();

                return $data['topic'] === $command;
            }));

        $this->logger->shouldReceive('debug')->twice();

        $this->producer->sendCommand($command, $message);
    }

    #[Test]
    public function setsCorrectRoutingKey(): void
    {
        $message = ['type' => 'article.create.command'];
        $expectedRoutingKey = JobCommandBusProcessor::getRoute();

        $this->outboxRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (OutboxEntryInterface $entry) use ($expectedRoutingKey) {
                $data = $entry->toArray();
                // Should use JobCommandBusProcessor::getRoute()

                return $data['routing_key'] === $expectedRoutingKey;
            }));

        $this->logger->shouldReceive('debug')->twice();

        $this->producer->sendCommand('job_command_bus', $message);
    }

    // ========================================================================
    // Message Type Tests
    // ========================================================================

    #[Test]
    public function createsTaskTypeOutboxEntry(): void
    {
        $message = ['type' => 'article.create.command'];

        $this->outboxRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (OutboxEntryInterface $entry) {
                $data = $entry->toArray();

                return $data['message_type'] === OutboxMessageType::TASK->value;
            }));

        $this->logger->shouldReceive('debug')->twice();

        $this->producer->sendCommand('job_command_bus', $message);
    }

    // ========================================================================
    // Uses Command Type Fallback Tests
    // ========================================================================

    #[Test]
    public function usesCommandAsEventTypeWhenTypeNotInPayload(): void
    {
        $command = 'job_command_bus';
        $message = ['args' => ['data' => 'test']]; // No 'type' key

        $this->outboxRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (OutboxEntryInterface $entry) use ($command) {
                $data = $entry->toArray();

                return $data['event_type'] === $command;
            }));

        $this->logger->shouldReceive('debug')->twice();

        $this->producer->sendCommand($command, $message);
    }

    // ========================================================================
    // Error Handling Tests
    // ========================================================================

    #[Test]
    public function propagatesRepositoryException(): void
    {
        $message = ['type' => 'article.create.command'];

        $this->outboxRepository
            ->shouldReceive('save')
            ->once()
            ->andThrow(new \RuntimeException('Database error'));

        // No debug log happens before save() - exception propagates immediately

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Database error');

        $this->producer->sendCommand('job_command_bus', $message);
    }

    // ========================================================================
    // Complete Flow Tests
    // ========================================================================

    #[Test]
    public function completeFlowWithValidTaskCommand(): void
    {
        $processUuid = Uuid::uuid4()->toString();
        $entityUuid = Uuid::uuid4()->toString();
        $expectedRoutingKey = JobCommandBusProcessor::getRoute();

        $command = 'job_command_bus';
        $message = [
            'type' => 'article.publish.command',
            'args' => [
                $processUuid,
                $entityUuid,
                ['status' => 'published'],
            ],
        ];

        $this->outboxRepository
            ->shouldReceive('save')
            ->once()
            ->with(Mockery::on(function (OutboxEntryInterface $entry) use ($entityUuid, $expectedRoutingKey) {
                $data = $entry->toArray();

                // Verify all fields
                self::assertNotEmpty($data['id']);
                self::assertEquals('TASK', $data['message_type']);
                self::assertEquals('Article', $data['aggregate_type']);
                self::assertEquals($entityUuid, $data['aggregate_id']);
                self::assertEquals('article.publish.command', $data['event_type']);
                self::assertEquals('job_command_bus', $data['topic']);
                self::assertEquals($expectedRoutingKey, $data['routing_key']);
                self::assertEquals(0, $data['retry_count']);
                self::assertNull($data['published_at']);

                // Verify payload is valid JSON
                $payload = json_decode($data['event_payload'], true);
                self::assertEquals('article.publish.command', $payload['type']);
                self::assertEquals(['status' => 'published'], $payload['args'][2]);

                return true;
            }));

        $this->logger
            ->shouldReceive('debug')
            ->twice();

        $result = $this->producer->sendCommand($command, $message);

        self::assertNull($result);
    }
}
