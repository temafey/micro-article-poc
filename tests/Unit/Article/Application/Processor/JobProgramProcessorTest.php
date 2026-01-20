<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\Processor;

use Micro\Article\Application\Processor\JobProgramProcessor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for JobProgramProcessor.
 */
#[CoversClass(JobProgramProcessor::class)]
final class JobProgramProcessorTest extends TestCase
{
    #[Test]
    public function getTopicShouldReturnCorrectTopicName(): void
    {
        // Act
        $result = JobProgramProcessor::getTopic();

        // Assert
        $this->assertSame('article.add.command.run', $result);
    }

    #[Test]
    public function topicConstantShouldHaveCorrectValue(): void
    {
        // Assert
        $this->assertSame('article.add.command.run', JobProgramProcessor::TOPIC);
    }
}
