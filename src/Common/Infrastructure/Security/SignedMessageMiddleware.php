<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Security;

use League\Tactician\Middleware;

/**
 * Tactician middleware for validating signed messages.
 *
 * This middleware intercepts commands that implement SignedMessageInterface
 * and validates their cryptographic signatures before allowing execution.
 *
 * Workflow:
 * 1. Check if command implements SignedMessageInterface
 * 2. If not signed, pass through (allows unsigned commands)
 * 3. If signed, validate signature using MessageSignerService
 * 4. Reject invalid signatures with InvalidSignatureException
 *
 * Configuration:
 *   - requireSignature: If true, reject unsigned SignedMessageInterface commands
 *
 * @see SignedMessageInterface For message contract
 * @see MessageSignerService For signature validation
 */
final readonly class SignedMessageMiddleware implements Middleware
{
    public function __construct(
        private MessageSignerService $messageSigner,
        private bool $requireSignature = true,
    ) {
    }

    /**
     * Validate signed messages before passing to next middleware.
     *
     * @param object   $command The command being executed
     * @param callable $next    The next middleware in the chain
     *
     * @return mixed The result from the command handler
     */
    #[\Override]
    public function execute($command, callable $next)
    {
        // Only validate commands that implement SignedMessageInterface
        if (! $command instanceof SignedMessageInterface) {
            return $next($command);
        }

        $signature = $command->getSignature();

        // Handle unsigned messages
        if ($signature === null) {
            if ($this->requireSignature) {
                throw InvalidSignatureException::missingSignature($command::class);
            }

            // Allow unsigned messages when not required
            return $next($command);
        }

        // Validate the signature
        if (! $this->messageSigner->verify($command)) {
            throw InvalidSignatureException::invalidSignature($command::class);
        }

        // Signature is valid, proceed to handler
        return $next($command);
    }
}
