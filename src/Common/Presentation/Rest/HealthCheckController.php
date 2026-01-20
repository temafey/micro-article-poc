<?php

declare(strict_types=1);

namespace Micro\Component\Common\Presentation\Rest;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Health check controller for application-level health monitoring.
 *
 * Provides endpoints for:
 * - Basic liveness probe (/health/live)
 * - Full readiness probe with dependency checks (/health/ready)
 * - Combined health status (/health)
 *
 * Works alongside RoadRunner's built-in status endpoint (port 2114).
 */
final class HealthCheckController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * Basic liveness probe - checks if the application is running.
     */
    #[Route('/health/live', name: 'health_live', methods: ['GET'])]
    public function live(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'timestamp' => new \DateTimeImmutable()
                ->format(\DateTimeInterface::RFC3339),
        ]);
    }

    /**
     * Readiness probe - checks if the application is ready to serve requests.
     * Includes database connectivity check.
     */
    #[Route('/health/ready', name: 'health_ready', methods: ['GET'])]
    public function ready(): JsonResponse
    {
        $checks = [];
        $isHealthy = true;

        // Database connectivity check
        $dbStatus = $this->checkDatabase();
        $checks['database'] = $dbStatus;
        if ($dbStatus['status'] !== 'ok') {
            $isHealthy = false;
        }

        $statusCode = $isHealthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE;

        return new JsonResponse([
            'status' => $isHealthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'timestamp' => new \DateTimeImmutable()
                ->format(\DateTimeInterface::RFC3339),
        ], $statusCode);
    }

    /**
     * Combined health endpoint - full status with all checks.
     */
    #[Route('/health', name: 'health', methods: ['GET'])]
    public function health(): JsonResponse
    {
        $checks = [];
        $isHealthy = true;

        // Database connectivity check
        $dbStatus = $this->checkDatabase();
        $checks['database'] = $dbStatus;
        if ($dbStatus['status'] !== 'ok') {
            $isHealthy = false;
        }

        // PHP runtime info
        $checks['runtime'] = [
            'status' => 'ok',
            'php_version' => PHP_VERSION,
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        ];

        // RoadRunner worker info (if available via SAPI)
        $checks['server'] = [
            'status' => 'ok',
            'sapi' => PHP_SAPI,
            'roadrunner' => PHP_SAPI === 'cli' && isset($_SERVER['RR_MODE']),
        ];

        $statusCode = $isHealthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE;

        return new JsonResponse([
            'status' => $isHealthy ? 'ok' : 'degraded',
            'checks' => $checks,
            'timestamp' => new \DateTimeImmutable()
                ->format(\DateTimeInterface::RFC3339),
        ], $statusCode);
    }

    /**
     * Check database connectivity.
     *
     * @return array{status: string, latency_ms?: float, error?: string}
     */
    private function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            $this->connection->executeQuery('SELECT 1');
            $latency = (microtime(true) - $start) * 1000;

            return [
                'status' => 'ok',
                'latency_ms' => round($latency, 2),
            ];
        } catch (\Throwable $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }
}
