<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Security;

/**
 * Exception thrown when message signature validation fails.
 *
 * This exception is thrown by SignedMessageMiddleware when:
 * - A signed message has an invalid/tampered signature
 * - A message requiring signature is submitted unsigned
 * - The message timestamp has expired
 * - The message nonce has been reused (replay attack)
 */
final class InvalidSignatureException extends \RuntimeException
{
    private function __construct(
        string $message,
        private readonly string $commandClass,
        private readonly string $reason,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * Create exception for missing signature.
     */
    public static function missingSignature(string $commandClass): self
    {
        return new self(
            sprintf('Message signature required but not provided for command: %s', $commandClass),
            $commandClass,
            'missing_signature'
        );
    }

    /**
     * Create exception for invalid signature.
     */
    public static function invalidSignature(string $commandClass): self
    {
        return new self(
            sprintf('Invalid message signature for command: %s', $commandClass),
            $commandClass,
            'invalid_signature'
        );
    }

    /**
     * Create exception for expired message.
     */
    public static function expiredMessage(string $commandClass, \DateTimeImmutable $timestamp): self
    {
        return new self(
            sprintf(
                'Message timestamp expired for command: %s (timestamp: %s)',
                $commandClass,
                $timestamp->format(\DateTimeInterface::ATOM)
            ),
            $commandClass,
            'expired_timestamp'
        );
    }

    /**
     * Create exception for replay attack (duplicate nonce).
     */
    public static function replayDetected(string $commandClass, string $nonce): self
    {
        return new self(
            sprintf('Replay attack detected for command: %s (nonce: %s)', $commandClass, $nonce),
            $commandClass,
            'replay_attack'
        );
    }

    /**
     * Get the command class that failed validation.
     */
    public function getCommandClass(): string
    {
        return $this->commandClass;
    }

    /**
     * Get the reason code for the failure.
     *
     * @return string One of: missing_signature, invalid_signature, expired_timestamp, replay_attack
     */
    public function getReason(): string
    {
        return $this->reason;
    }
}
