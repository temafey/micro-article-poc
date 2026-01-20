<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Security;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Redis-based nonce storage for replay attack prevention.
 *
 * Stores used nonces in Redis with automatic expiration matching
 * the message lifetime. This enables distributed nonce validation
 * across multiple application instances.
 *
 * Features:
 *   - Atomic nonce registration (SETNX)
 *   - Automatic TTL-based cleanup
 *   - Distributed validation across instances
 *   - Configurable key prefix for namespacing
 */
final readonly class RedisNonceStore
{
    private const string KEY_PREFIX = 'message_nonce:';
    private const int DEFAULT_TTL = 300; // 5 minutes

    public function __construct(
        private \Redis|\RedisCluster $redis,
        private int $ttl = self::DEFAULT_TTL,
        private string $keyPrefix = self::KEY_PREFIX,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * Check if a nonce has already been used.
     *
     * @param string $nonce The nonce to check
     *
     * @return bool True if nonce was already used, false if new
     */
    public function isUsed(string $nonce): bool
    {
        $key = $this->getKey($nonce);

        try {
            return (bool) $this->redis->exists($key);
        } catch (\RedisException $e) {
            $this->logger->error('Redis error checking nonce', [
                'nonce' => $nonce,
                'error' => $e->getMessage(),
            ]);

            // Fail open on Redis errors (allow the request)
            // Consider fail-closed for high-security requirements
            return false;
        }
    }

    /**
     * Mark a nonce as used.
     *
     * Uses SETNX (SET if Not eXists) for atomic registration.
     * Returns false if nonce was already registered (race condition).
     *
     * @param string $nonce The nonce to register
     *
     * @return bool True if successfully registered, false if already used
     */
    public function markUsed(string $nonce): bool
    {
        $key = $this->getKey($nonce);

        try {
            // SETNX with expiration - atomic operation
            $result = $this->redis->set($key, (string) time(), [
                'NX',
                'EX' => $this->ttl,
            ]);

            if ($result === false) {
                $this->logger->warning('Nonce already used (concurrent request)', [
                    'nonce' => $nonce,
                ]);

                return false;
            }

            $this->logger->debug('Nonce registered', [
                'nonce' => $nonce,
                'ttl' => $this->ttl,
            ]);

            return true;
        } catch (\RedisException $e) {
            $this->logger->error('Redis error marking nonce', [
                'nonce' => $nonce,
                'error' => $e->getMessage(),
            ]);

            // Fail open on Redis errors
            return true;
        }
    }

    /**
     * Atomically check and mark a nonce as used.
     *
     * Combines isUsed() and markUsed() in a single atomic operation.
     * Returns true only if the nonce was new and successfully registered.
     *
     * @param string $nonce The nonce to validate and register
     *
     * @return bool True if nonce is valid (new), false if already used
     */
    public function validateAndMark(string $nonce): bool
    {
        return $this->markUsed($nonce);
    }

    /**
     * Get the number of active nonces (for monitoring).
     */
    public function count(): int
    {
        try {
            $pattern = $this->keyPrefix . '*';
            $keys = $this->redis->keys($pattern);

            return is_array($keys) ? count($keys) : 0;
        } catch (\RedisException $e) {
            $this->logger->error('Redis error counting nonces', [
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Build the Redis key for a nonce.
     */
    private function getKey(string $nonce): string
    {
        return $this->keyPrefix . $nonce;
    }
}
