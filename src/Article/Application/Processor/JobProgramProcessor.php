<?php

declare(strict_types=1);

namespace Micro\Article\Application\Processor;

use MicroModule\Task\Application\Processor\JobCommandBusProcessor;

class JobProgramProcessor extends JobCommandBusProcessor
{
    public const TOPIC = 'article.add.command.run';

    /**
     * Get processor topic name.
     */
    public static function getTopic(): string
    {
        return self::TOPIC;
    }
}
