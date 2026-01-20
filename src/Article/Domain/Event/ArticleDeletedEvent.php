<?php

declare(strict_types=1);

namespace Micro\Article\Domain\Event;

use Assert\Assertion;
use MicroModule\Base\Domain\Event\AbstractEvent;
use MicroModule\Base\Domain\ValueObject\Payload;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;

/**
 * @class ArticleDeletedEvent
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ArticleDeletedEvent extends AbstractEvent
{
    public function __construct(ProcessUuid $processUuid, Uuid $uuid, ?Payload $payload = null)
    {
        parent::__construct($processUuid, $uuid, $payload);
    }

    /**
     * Initialize event from data array.
     */
    #[\Override]
    public static function deserialize(array $data): static
    {
        Assertion::keyExists($data, 'process_uuid');
        Assertion::keyExists($data, 'uuid');
        $event = new static(ProcessUuid::fromNative($data['process_uuid']), Uuid::fromNative($data['uuid']));

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
        ];

        if ($this->payload instanceof Payload) {
            $data['payload'] = $this->payload->toNative();
        }

        return $data;
    }
}
