<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Observability\Profiling;

use ExcimerProfiler;

/**
 * Pyroscope continuous profiling integration using ext-excimer.
 *
 * This class provides CPU profiling for PHP applications using the excimer
 * extension and sends profiling data to a Pyroscope server for visualization.
 *
 * ADR-014 Phase 4.2: Continuous Profiling Setup
 *
 * @see https://grafana.com/docs/pyroscope/latest/configure-client/language-sdks/php/
 */
final class PyroscopeProfiler
{
    private const DEFAULT_SAMPLE_RATE = 100;
    private const DEFAULT_UPLOAD_INTERVAL = 10;

    private ?ExcimerProfiler $profiler = null;
    private int $lastUpload = 0;
    private bool $isRunning = false;

    /** @var array<string, string> */
    private array $labels = [];

    public function __construct(
        private readonly string $applicationName,
        private readonly PyroscopeHttpClient $httpClient,
        private readonly int $sampleRate = self::DEFAULT_SAMPLE_RATE,
        private readonly int $uploadInterval = self::DEFAULT_UPLOAD_INTERVAL,
        private readonly bool $enabled = true,
    ) {
        $this->labels = [
            'service_name' => $this->applicationName,
        ];
    }

    /**
     * Start profiling the current request/process.
     */
    public function start(): void
    {
        if (!$this->enabled || !$this->isExcimerAvailable()) {
            return;
        }

        if ($this->isRunning) {
            return;
        }

        $this->profiler = new ExcimerProfiler();
        $this->profiler->setPeriod(1.0 / $this->sampleRate);
        $this->profiler->setEventType(EXCIMER_REAL);
        $this->profiler->start();

        $this->lastUpload = time();
        $this->isRunning = true;
    }

    /**
     * Stop profiling and upload the final data.
     */
    public function stop(): void
    {
        if (!$this->isRunning || $this->profiler === null) {
            return;
        }

        $this->profiler->stop();
        $this->uploadProfile();
        $this->profiler = null;
        $this->isRunning = false;
    }

    /**
     * Add a label to profiling data for filtering in Pyroscope.
     */
    public function addLabel(string $key, string $value): void
    {
        $sanitizedKey = preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
        $this->labels[$sanitizedKey ?? $key] = $value;
    }

    /**
     * Check if it's time to upload and do so if needed.
     * Call this periodically for long-running processes.
     */
    public function flush(): void
    {
        if (!$this->isRunning || $this->profiler === null) {
            return;
        }

        $now = time();
        if (($now - $this->lastUpload) >= $this->uploadInterval) {
            $this->uploadProfile();
            $this->lastUpload = $now;
        }
    }

    /**
     * Get current profiling statistics.
     *
     * @return array{running: bool, samples: int, labels: array<string, string>}
     */
    public function getStats(): array
    {
        return [
            'running' => $this->isRunning,
            'samples' => $this->profiler?->getLog()?->count() ?? 0,
            'labels' => $this->labels,
        ];
    }

    private function uploadProfile(): void
    {
        if ($this->profiler === null) {
            return;
        }

        // flush() returns the log AND clears internal state for continued profiling
        $log = $this->profiler->flush();
        if ($log === null || $log->count() === 0) {
            return;
        }

        $pprofData = $log->formatCollapsed();
        if (empty($pprofData)) {
            return;
        }

        $params = [
            'name' => $this->applicationName . '.cpu',
            'sampleRate' => $this->sampleRate,
            'from' => $this->lastUpload * 1000000000,
            'until' => time() * 1000000000,
            'format' => 'folded',
            'spyName' => 'phpspy',
            'labels' => $this->labels,
        ];

        $this->httpClient->sendProfile($pprofData, $params);
    }

    private function isExcimerAvailable(): bool
    {
        return extension_loaded('excimer') && class_exists(ExcimerProfiler::class);
    }
}
