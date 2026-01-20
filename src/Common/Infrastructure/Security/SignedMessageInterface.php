<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Security;

/**
 * Interface for messages that support cryptographic signing.
 *
 * Implement this interface on commands, events, or other messages
 * that require tamper-proof verification. The signature ensures
 * message integrity and authenticity.
 *
 * Usage:
 *   - Commands submitted via external APIs
 *   - Events propagated across service boundaries
 *   - Webhook payloads from third-party systems
 *
 * @see MessageSignerService For signature generation and verification
 * @see SignedMessageMiddleware For automatic validation in command bus
 */
interface SignedMessageInterface
{
    /**
     * Get the cryptographic signature of this message.
     *
     * The signature is typically an HMAC-SHA256 hash of the
     * message payload, encoded as a hexadecimal string.
     *
     * @return string|null The signature, or null if not yet signed
     */
    public function getSignature(): ?string;

    /**
     * Set the cryptographic signature for this message.
     *
     * This method should be called by the MessageSignerService
     * after computing the HMAC signature of the payload.
     */
    public function setSignature(string $signature): void;

    /**
     * Get the payload data to be signed.
     *
     * Returns an associative array of the message's signable content.
     * This should include all fields that need tamper protection,
     * but exclude the signature itself.
     *
     * Important: The returned array must be consistently ordered
     * to ensure deterministic signature generation.
     *
     * @return array<string, mixed> The payload data for signing
     */
    public function getSignablePayload(): array;

    /**
     * Get the timestamp when this message was created/signed.
     *
     * Used for replay attack prevention - messages older than
     * a configurable threshold can be rejected.
     *
     * @return \DateTimeImmutable|null The timestamp, or null if not set
     */
    public function getTimestamp(): ?\DateTimeImmutable;

    /**
     * Set the timestamp for this message.
     *
     * Should be set at message creation time, before signing.
     */
    public function setTimestamp(\DateTimeImmutable $timestamp): void;

    /**
     * Get the nonce (number used once) for replay protection.
     *
     * A unique identifier that prevents the same signed message
     * from being replayed multiple times.
     *
     * @return string|null The nonce, or null if not set
     */
    public function getNonce(): ?string;

    /**
     * Set the nonce for this message.
     *
     * Should be a cryptographically random string, generated
     * at message creation time.
     */
    public function setNonce(string $nonce): void;
}
