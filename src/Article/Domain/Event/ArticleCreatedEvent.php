<?php

declare(strict_types=1);

namespace Micro\Article\Domain\Event;

use Assert\Assertion;
use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Domain\Event\AbstractEvent;
use MicroModule\Base\Domain\ValueObject\Payload;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;

/**
 * @class ArticleCreatedEvent
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class ArticleCreatedEvent extends AbstractEvent
{
    public function __construct(
        ProcessUuid $processUuid,
        Uuid $uuid,
        protected Article $article,
        ?Payload $payload = null,
    ) {
        parent::__construct($processUuid, $uuid, $payload);
    }

    /**
     * Return Article value object.
     */
    public function getArticle(): Article
    {
        return $this->article;
    }

    /**
     * Initialize event from data array.
     */
    #[\Override]
    public static function deserialize(array $data): static
    {
        Assertion::keyExists($data, 'process_uuid');
        Assertion::keyExists($data, 'uuid');
        Assertion::keyExists($data, 'article');
        $event = new static(
            ProcessUuid::fromNative($data['process_uuid']),
            Uuid::fromNative($data['uuid']),
            Article::fromNative($data['article'])
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
            'article' => $this->getArticle()
                ->toNative(),
        ];

        if ($this->payload instanceof Payload) {
            $data['payload'] = $this->payload->toNative();
        }

        return $data;
    }
}
