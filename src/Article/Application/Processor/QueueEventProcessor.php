<?php

declare(strict_types=1);

namespace Micro\Article\Application\Processor;

use MicroModule\EventQueue\Application\EventHandling\QueueEventProcessor as BaseQueueEventProcessor;

class QueueEventProcessor extends BaseQueueEventProcessor
{
    public const TOPIC = 'micro-platform.article.queueevent';

    /**
     * Get processor topic name.
     */
    public static function getTopic(): string
    {
        return self::TOPIC;
    }
}
