<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Security;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Random\Randomizer;

/**
 * Service for cryptographic signing and verification of messages.
 *
 * Uses HMAC-SHA256 for generating tamper-proof signatures.
 * Includes replay attack prevention via nonce and timestamp validation.
 *
 * Security features:
 *   - HMAC-SHA256 signature algorithm
 *   - Nonce-based replay protection
 *   - Timestamp-based message expiration
 *   - Timing-safe signature comparison
 *
 * Configuration:
 *   - secret: Shared secret key (min 32 bytes recommended)
 *   - messageLifetime: Maximum age of valid messages (default: 300 seconds)
 *
 * @see SignedMessageInterface For message contract
 * @see SignedMessageMiddleware For command bus integration
 */
final class MessageSignerService
{
    private const string HASH_ALGORITHM = 'sha256';
    private const int DEFAULT_MESSAGE_LIFETIME = 300; // 5 minutes
    private const int NONCE_LENGTH = 32;

    /**
     * @var array<string, true> Cache of used nonces to prevent replay
     */
    private array $usedNonces = [];

    public function __construct(
        private readonly string $secret,
        private readonly int $messageLifetime = self::DEFAULT_MESSAGE_LIFETIME,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
        if (strlen($this->secret) < 32) {
            $this->logger->warning(
                'Message signing secret is shorter than recommended 32 bytes',
                [
                    'length' => strlen($this->secret),
                ]
            );
        }
    }

    /**
     * Sign a message by computing its HMAC signature.
     *
     * This method:
     * 1. Sets timestamp if not already set
     * 2. Generates a random nonce for replay protection
     * 3. Computes HMAC-SHA256 of the payload
     * 4. Stores the signature on the message
     *
     * @param SignedMessageInterface $message The message to sign
     *
     * @return SignedMessageInterface The signed message (same instance)
     */
    public function sign(SignedMessageInterface $message): SignedMessageInterface
    {
        // Set timestamp if not already set
        if ($message->getTimestamp() === null) {
            $message->setTimestamp(new \DateTimeImmutable());
        }

        // Generate nonce for replay protection
        if ($message->getNonce() === null) {
            $message->setNonce($this->generateNonce());
        }

        $signature = $this->computeSignature($message);
        $message->setSignature($signature);

        $this->logger->debug('Message signed', [
            'timestamp' => $message->getTimestamp()?->format(\DateTimeInterface::ATOM),
            'nonce' => $message->getNonce(),
            'signature_prefix' => substr($signature, 0, 8) . '...',
        ]);

        return $message;
    }

    /**
     * Verify a message's signature.
     *
     * Performs the following validations:
     * 1. Signature presence check
     * 2. Timestamp freshness (within message lifetime)
     * 3. Nonce uniqueness (replay protection)
     * 4. Signature correctness (HMAC comparison)
     *
     * @param SignedMessageInterface $message The message to verify
     *
     * @return bool True if the message is valid, false otherwise
     */
    public function verify(SignedMessageInterface $message): bool
    {
        $signature = $message->getSignature();

        if ($signature === null) {
            $this->logger->warning('Message verification failed: no signature');

            return false;
        }

        // Validate timestamp (prevent replay of old messages)
        if (! $this->isTimestampValid($message)) {
            $this->logger->warning('Message verification failed: timestamp expired', [
                'timestamp' => $message->getTimestamp()?->format(\DateTimeInterface::ATOM),
                'lifetime' => $this->messageLifetime,
            ]);

            return false;
        }

        // Validate nonce (prevent replay of same message)
        if (! $this->isNonceValid($message)) {
            $this->logger->warning('Message verification failed: nonce already used', [
                'nonce' => $message->getNonce(),
            ]);

            return false;
        }

        // Compute expected signature and compare
        $expectedSignature = $this->computeSignature($message);

        if (! hash_equals($expectedSignature, $signature)) {
            $this->logger->warning('Message verification failed: signature mismatch');

            return false;
        }

        // Mark nonce as used
        $nonce = $message->getNonce();
        if ($nonce !== null) {
            $this->usedNonces[$nonce] = true;
        }

        $this->logger->debug('Message verified successfully', [
            'timestamp' => $message->getTimestamp()?->format(\DateTimeInterface::ATOM),
            'nonce' => $message->getNonce(),
        ]);

        return true;
    }

    /**
     * Compute the HMAC signature for a message.
     *
     * The signature covers:
     * - Signable payload (JSON-encoded)
     * - Timestamp (ISO 8601 format)
     * - Nonce
     */
    private function computeSignature(SignedMessageInterface $message): string
    {
        $payload = $message->getSignablePayload();

        // Ensure consistent ordering for deterministic signatures
        ksort($payload, SORT_STRING);

        $data = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);

        // Include timestamp and nonce in signature
        $timestamp = $message->getTimestamp()?->format(\DateTimeInterface::ATOM) ?? '';
        $nonce = $message->getNonce() ?? '';

        $signatureInput = $data . '|' . $timestamp . '|' . $nonce;

        return hash_hmac(self::HASH_ALGORITHM, $signatureInput, $this->secret);
    }

    /**
     * Check if the message timestamp is within the allowed lifetime.
     */
    private function isTimestampValid(SignedMessageInterface $message): bool
    {
        $timestamp = $message->getTimestamp();

        if ($timestamp === null) {
            return false;
        }

        $now = new \DateTimeImmutable();
        $age = $now->getTimestamp() - $timestamp->getTimestamp();

        return $age >= 0 && $age <= $this->messageLifetime;
    }

    /**
     * Check if the message nonce has not been used before.
     */
    private function isNonceValid(SignedMessageInterface $message): bool
    {
        $nonce = $message->getNonce();

        if ($nonce === null) {
            return false;
        }

        return ! isset($this->usedNonces[$nonce]);
    }

    /**
     * Generate a cryptographically secure random nonce.
     */
    private function generateNonce(): string
    {
        $randomizer = new Randomizer();

        return bin2hex($randomizer->getBytes(self::NONCE_LENGTH));
    }

    /**
     * Clear the used nonces cache.
     *
     * In production, nonces should be stored in a distributed cache
     * (Redis) with TTL matching the message lifetime.
     */
    public function clearNonceCache(): void
    {
        $this->usedNonces = [];
    }

    /**
     * Get the count of cached nonces (for monitoring).
     */
    public function getNonceCacheCount(): int
    {
        return count($this->usedNonces);
    }
}
