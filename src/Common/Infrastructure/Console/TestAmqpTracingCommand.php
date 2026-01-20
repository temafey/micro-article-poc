<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Console;

use Broadway\Serializer\Serializable;
use Micro\Component\Common\Infrastructure\Observability\Service\TracerFactory;
use MicroModule\EventQueue\Domain\EventHandling\QueueEventInterface;
use MicroModule\Base\Domain\ValueObject\Payload;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Ramsey\Uuid\Uuid as RamseyUuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Test command to verify AMQP tracing is working correctly.
 *
 * This command publishes test events to RabbitMQ to generate
 * OpenTelemetry spans that can be verified in Grafana Tempo.
 *
 * @internal For testing purposes only
 */
#[AsCommand(
    name: 'app:test-amqp-tracing',
    description: 'Test AMQP tracing by publishing test events to RabbitMQ',
)]
final class TestAmqpTracingCommand extends Command
{
    public function __construct(
        private readonly QueueEventInterface $queueEventProducer,
        private readonly TracerFactory $tracerFactory,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Testing AMQP Tracing');

        // Verify we have TracingQueueEventProducer
        $producerClass = get_class($this->queueEventProducer);
        $io->info(sprintf('Queue Event Producer class: %s', $producerClass));

        if (!str_contains($producerClass, 'TracingQueueEventProducer')) {
            $io->warning('Not using TracingQueueEventProducer - tracing may not work');
        }

        try {
            // Create a parent span to demonstrate trace context propagation
            $tracer = $this->tracerFactory->getTracer();
            $parentSpan = $tracer->spanBuilder('amqp.test.parent')
                ->setAttribute('test.type', 'amqp_tracing')
                ->setAttribute('test.timestamp', time())
                ->startSpan();

            $scope = $parentSpan->activate();

            $traceId = $parentSpan->getContext()->getTraceId();
            $io->info(sprintf('Parent Trace ID: %s', $traceId));

            // Publish multiple test events
            $io->section('Publishing test events');

            for ($i = 1; $i <= 3; $i++) {
                $io->write(sprintf('Publishing event %d... ', $i));

                $testEvent = new TestAmqpEvent(
                    processUuid: ProcessUuid::fromNative(RamseyUuid::uuid4()->toString()),
                    uuid: Uuid::fromNative(RamseyUuid::uuid4()->toString()),
                    testData: sprintf('Test event %d at %s', $i, date('Y-m-d H:i:s'))
                );

                $this->queueEventProducer->publishEventToQueue($testEvent);
                $io->writeln('<info>OK</info>');

                // Small delay between events
                usleep(100000);
            }

            $scope->detach();
            $parentSpan->end();

            $io->newLine();
            $io->success([
                'All test events published successfully!',
                sprintf('Trace ID: %s', $traceId),
                '',
                'Check Grafana Tempo for spans:',
                '  TraceQL: { messaging.system = "rabbitmq" }',
                '  Or search by trace ID: ' . $traceId,
                '',
                'Expected spans:',
                '  - amqp.test.parent (parent span)',
                '  - amqp.micro-platform.article.queueevent.publish (producer spans)',
            ]);

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $io->error(sprintf('AMQP error: %s', $e->getMessage()));
            $io->writeln($e->getTraceAsString());

            return Command::FAILURE;
        }
    }
}

/**
 * Simple test event for AMQP tracing verification.
 *
 * @internal
 */
final class TestAmqpEvent implements Serializable
{
    public function __construct(
        private readonly ProcessUuid $processUuid,
        private readonly Uuid $uuid,
        private readonly string $testData,
    ) {
    }

    public function getProcessUuid(): ProcessUuid
    {
        return $this->processUuid;
    }

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function getTestData(): string
    {
        return $this->testData;
    }

    public function serialize(): array
    {
        return [
            'process_uuid' => $this->processUuid->toNative(),
            'uuid' => $this->uuid->toNative(),
            'test_data' => $this->testData,
        ];
    }

    public static function deserialize(array $data): self
    {
        return new self(
            ProcessUuid::fromNative($data['process_uuid']),
            Uuid::fromNative($data['uuid']),
            $data['test_data']
        );
    }
}
