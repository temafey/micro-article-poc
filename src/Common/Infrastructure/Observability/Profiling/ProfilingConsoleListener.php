<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Observability\Profiling;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Automatically profiles console commands using Pyroscope.
 *
 * Starts profiling when a command begins and uploads profile
 * data when the command terminates. Includes periodic flushing
 * for long-running commands.
 *
 * ADR-014 Phase 4.2: Continuous Profiling Setup
 */
#[AsEventListener(event: ConsoleEvents::COMMAND, method: 'onCommand', priority: 1000)]
#[AsEventListener(event: ConsoleEvents::TERMINATE, method: 'onTerminate', priority: -1000)]
final class ProfilingConsoleListener
{
    /** @var string[] Commands to skip profiling */
    private const SKIP_COMMANDS = [
        'cache:clear',
        'cache:warmup',
        'debug:container',
        'debug:router',
        'list',
        'help',
        'about',
    ];

    public function __construct(
        private readonly PyroscopeProfiler $profiler,
    ) {
    }

    public function onCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();
        if ($command === null) {
            return;
        }

        $commandName = $command->getName() ?? 'unknown';

        if ($this->shouldSkipCommand($commandName)) {
            return;
        }

        $this->profiler->addLabel('command_name', $commandName);
        $this->profiler->addLabel('command_class', $command::class);

        $this->profiler->start();
    }

    public function onTerminate(ConsoleTerminateEvent $event): void
    {
        $command = $event->getCommand();
        if ($command === null) {
            return;
        }

        $commandName = $command->getName() ?? 'unknown';

        if ($this->shouldSkipCommand($commandName)) {
            return;
        }

        $exitCode = $event->getExitCode();
        $this->profiler->addLabel('exit_code', (string) $exitCode);
        $this->profiler->addLabel('exit_status', $exitCode === 0 ? 'success' : 'failure');

        $this->profiler->stop();
    }

    private function shouldSkipCommand(string $commandName): bool
    {
        return in_array($commandName, self::SKIP_COMMANDS, true);
    }
}
