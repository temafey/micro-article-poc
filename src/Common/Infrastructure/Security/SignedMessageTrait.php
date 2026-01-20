<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Security;

/**
 * Trait providing default implementation of SignedMessageInterface.
 *
 * Use this trait in commands or events that need signature support.
 * The trait provides all required properties and methods for signing.
 *
 * Usage:
 *   class MyCommand implements SignedMessageInterface
 *   {
 *       use SignedMessageTrait;
 *
 *       public function __construct(private string $data) {}
 *
 *       public function getSignablePayload(): array
 *       {
 *           return ['data' => $this->data];
 *       }
 *   }
 */
trait SignedMessageTrait
{
    private ?string $signature = null;

    private ?\DateTimeImmutable $timestamp = null;

    private ?string $nonce = null;

    /**
     * @see SignedMessageInterface::getSignature()
     */
    public function getSignature(): ?string
    {
        return $this->signature;
    }

    /**
     * @see SignedMessageInterface::setSignature()
     */
    public function setSignature(string $signature): void
    {
        $this->signature = $signature;
    }

    /**
     * @see SignedMessageInterface::getTimestamp()
     */
    public function getTimestamp(): ?\DateTimeImmutable
    {
        return $this->timestamp;
    }

    /**
     * @see SignedMessageInterface::setTimestamp()
     */
    public function setTimestamp(\DateTimeImmutable $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    /**
     * @see SignedMessageInterface::getNonce()
     */
    public function getNonce(): ?string
    {
        return $this->nonce;
    }

    /**
     * @see SignedMessageInterface::setNonce()
     */
    public function setNonce(string $nonce): void
    {
        $this->nonce = $nonce;
    }
}
