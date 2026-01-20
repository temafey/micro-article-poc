<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Console;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Test command to verify Redis tracing is working correctly.
 *
 * This command performs various Redis operations to generate
 * OpenTelemetry spans that can be verified in Grafana Tempo.
 *
 * @internal For testing purposes only
 */
#[AsCommand(
    name: 'app:test-redis-tracing',
    description: 'Test Redis tracing by performing various operations',
)]
final class TestRedisTracingCommand extends Command
{
    public function __construct(
        private readonly \Redis $redis,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Testing Redis Tracing');

        $testKey = 'test:tracing:' . time();

        try {
            // Test SET operation
            $io->section('Testing SET operation');
            $result = $this->redis->set($testKey, 'test_value');
            $io->success(sprintf('SET %s = %s', $testKey, $result ? 'OK' : 'FAILED'));

            // Test GET operation
            $io->section('Testing GET operation');
            $value = $this->redis->get($testKey);
            $io->success(sprintf('GET %s = %s', $testKey, $value));

            // Test EXISTS operation
            $io->section('Testing EXISTS operation');
            $exists = $this->redis->exists($testKey);
            $io->success(sprintf('EXISTS %s = %s', $testKey, $exists ? 'true' : 'false'));

            // Test INCR operation (need integer value)
            $counterKey = 'test:counter:' . time();
            $io->section('Testing INCR operation');
            $this->redis->set($counterKey, '0');
            $newValue = $this->redis->incr($counterKey);
            $io->success(sprintf('INCR %s = %d', $counterKey, $newValue));

            // Test DEL operation
            $io->section('Testing DEL operation');
            $deleted = $this->redis->del($testKey, $counterKey);
            $io->success(sprintf('DEL = %d keys deleted', $deleted));

            // Test SETEX (SET with expiration)
            $io->section('Testing SETEX operation');
            $expireKey = 'test:expire:' . time();
            $result = $this->redis->setex($expireKey, 60, 'expires_in_60s');
            $io->success(sprintf('SETEX %s = %s', $expireKey, $result ? 'OK' : 'FAILED'));

            // Test TTL
            $io->section('Testing TTL operation');
            $ttl = $this->redis->ttl($expireKey);
            $io->success(sprintf('TTL %s = %d seconds', $expireKey, $ttl));

            // Cleanup
            $this->redis->del($expireKey);

            $io->newLine();
            $io->success([
                'All Redis operations completed successfully!',
                'Check Grafana Tempo for spans with db.system=redis',
                'TraceQL: { db.system = "redis" }',
            ]);

            return Command::SUCCESS;
        } catch (\RedisException $e) {
            $io->error(sprintf('Redis error: %s', $e->getMessage()));

            return Command::FAILURE;
        }
    }
}
