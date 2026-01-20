<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\Processor;

use Micro\Article\Application\Processor\QueueEventProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for QueueEventProcessor.
 */
#[CoversClass(QueueEventProcessor::class)]
final class QueueEventProcessorTest extends TestCase
{
    #[Test]
    public function getTopicShouldReturnCorrectTopicName(): void
    {
        // Act
        $result = QueueEventProcessor::getTopic();

        // Assert
        $this->assertSame('micro-platform.article.queueevent', $result);
    }

    #[Test]
    public function topicConstantShouldHaveCorrectValue(): void
    {
        // Assert
        $this->assertSame('micro-platform.article.queueevent', QueueEventProcessor::TOPIC);
    }
}
