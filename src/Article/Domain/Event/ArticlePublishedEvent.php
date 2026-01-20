<?php

declare(strict_types=1);

namespace Micro\Article\Domain\Event;

use Assert\Assertion;
use Micro\Article\Domain\ValueObject\PublishedAt;
use Micro\Article\Domain\ValueObject\Status;
use MicroModule\Base\Domain\Event\AbstractEvent;
use MicroModule\Base\Domain\ValueObject\Payload;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\UpdatedAt;
use MicroModule\Base\Domain\ValueObject\Uuid;

/**
 * @class ArticlePublishedEvent
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ArticlePublishedEvent extends AbstractEvent
{
    public function __construct(
        ProcessUuid $processUuid,
        Uuid $uuid,
        protected Status $status,
        protected PublishedAt $publishedAt,
        protected UpdatedAt $updatedAt,
        ?Payload $payload = null,
    ) {
        parent::__construct($processUuid, $uuid, $payload);
    }

    /**
     * Return Status value object.
     */
    public function getStatus(): Status
    {
        return $this->status;
    }

    /**
     * Return PublishedAt value object.
     */
    public function getPublishedAt(): PublishedAt
    {
        return $this->publishedAt;
    }

    /**
     * Return UpdatedAt value object.
     */
    public function getUpdatedAt(): UpdatedAt
    {
        return $this->updatedAt;
    }

    /**
     * Initialize event from data array.
     */
    #[\Override]
    public static function deserialize(array $data): static
    {
        Assertion::keyExists($data, 'process_uuid');
        Assertion::keyExists($data, 'uuid');
        Assertion::keyExists($data, 'status');
        Assertion::keyExists($data, 'published_at');
        Assertion::keyExists($data, 'updated_at');
        $event = new static(
            ProcessUuid::fromNative($data['process_uuid']),
            Uuid::fromNative($data['uuid']),
            Status::fromNative($data['status']),
            PublishedAt::fromNative($data['published_at']),
            UpdatedAt::fromNative($data['updated_at'])
        );

        if (isset($data['payload'])) {
            $event->setPayload(Payload::fromNative($data['payload']));
        }

        return $event;
    }

    /**
     * Convert event object to array.
     */
    #[\Override]
    public function serialize(): array
    {
        $data = [
            'process_uuid' => $this->getProcessUuid()
                ->toNative(),
            'uuid' => $this->getUuid()
                ->toNative(),
            'status' => $this->getStatus()
                ->toNative(),
            'published_at' => $this->getPublishedAt()
                ->toNative(),
            'updated_at' => $this->getUpdatedAt()
                ->toNative(),
        ];

        if ($this->payload instanceof Payload) {
            $data['payload'] = $this->payload->toNative();
        }

        return $data;
    }
}
