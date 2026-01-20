<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Observability\Profiling;

/**
 * HTTP client for sending profiling data to Pyroscope.
 *
 * Handles the HTTP transport layer for continuous profiling,
 * keeping URL construction and HTTP concerns separate from profiling logic.
 *
 * ADR-014 Phase 4.2: Continuous Profiling Setup
 */
final class PyroscopeHttpClient
{
    private const INGEST_ENDPOINT = '/ingest';
    private const TIMEOUT_SECONDS = 5;
    private const CONNECT_TIMEOUT_SECONDS = 2;

    private readonly string $ingestBaseUrl;

    public function __construct(
        string $serverUrl,
    ) {
        $this->ingestBaseUrl = $this->validateAndBuildBaseUrl($serverUrl);
    }

    /**
     * Send profiling data to Pyroscope server.
     *
     * @param string $profileData The collapsed stack trace data
     * @param array{
     *     name: string,
     *     sampleRate: int,
     *     from: int,
     *     until: int,
     *     format: string,
     *     spyName: string,
     *     labels: array<string, string>
     * } $params Profiling parameters
     */
    public function sendProfile(string $profileData, array $params): bool
    {
        if (empty($this->ingestBaseUrl)) {
            return false;
        }

        $queryString = $this->buildQueryString($params);
        $fullUrl = $this->ingestBaseUrl . '?' . $queryString;

        return $this->executeHttpPost($fullUrl, $profileData);
    }

    /**
     * Check if the client is properly configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->ingestBaseUrl);
    }

    private function validateAndBuildBaseUrl(string $serverUrl): string
    {
        $trimmedUrl = rtrim($serverUrl, '/');
        $parsedUrl = parse_url($trimmedUrl);

        if ($parsedUrl === false) {
            return '';
        }

        $scheme = $parsedUrl['scheme'] ?? '';
        if (!in_array($scheme, ['http', 'https'], true)) {
            return '';
        }

        $host = $parsedUrl['host'] ?? '';
        if (empty($host)) {
            return '';
        }

        $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
        $path = $parsedUrl['path'] ?? '';

        return $scheme . '://' . $host . $port . $path . self::INGEST_ENDPOINT;
    }

    /**
     * @param array{
     *     name: string,
     *     sampleRate: int,
     *     from: int,
     *     until: int,
     *     format: string,
     *     spyName: string,
     *     labels: array<string, string>
     * } $params
     */
    private function buildQueryString(array $params): string
    {
        // Build name with embedded labels: appname{label1=value1,label2=value2}
        $nameWithLabels = $this->buildNameWithLabels($params['name'], $params['labels']);

        $queryParams = [
            'name' => $nameWithLabels,
            'sampleRate' => (string) $params['sampleRate'],
            'from' => (string) $params['from'],
            'until' => (string) $params['until'],
            'format' => $params['format'],
            'spyName' => $params['spyName'],
        ];

        return http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * Build Pyroscope name with embedded labels.
     *
     * Pyroscope expects labels in the format: appname{label1=value1,label2=value2}
     *
     * @param array<string, string> $labels
     */
    private function buildNameWithLabels(string $baseName, array $labels): string
    {
        if (empty($labels)) {
            return $baseName;
        }

        $labelParts = [];
        foreach ($labels as $key => $value) {
            $safeKey = preg_replace('/[^a-zA-Z0-9_]/', '_', $key);
            $safeValue = preg_replace('/[{}=,]/', '_', $value);
            $labelParts[] = ($safeKey ?? $key) . '=' . ($safeValue ?? $value);
        }

        return $baseName . '{' . implode(',', $labelParts) . '}';
    }

    private function executeHttpPost(string $url, string $body): bool
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: text/plain\r\n",
                'content' => $body,
                'timeout' => self::TIMEOUT_SECONDS,
                'ignore_errors' => true,
            ],
        ]);

        $result = @file_get_contents($url, false, $context);

        return $result !== false;
    }
}
