<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\Saga;

use Broadway\Saga\Metadata\StaticallyConfiguredSagaInterface;
use Broadway\Saga\State;
use Micro\Article\Application\Saga\ArticleCreationPublicationWorkflowSaga;
use Micro\Article\Domain\Event\ArticleCreatedEvent;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Application\Saga\ArticleCreationPublicationWorkflowSagaDataProvider;

/**
 * Unit tests for ArticleCreationPublicationWorkflowSaga.
 */
#[CoversClass(ArticleCreationPublicationWorkflowSaga::class)]
final class ArticleCreationPublicationWorkflowSagaTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private ArticleCreationPublicationWorkflowSaga $saga;

    protected function setUp(): void
    {
        $this->saga = new ArticleCreationPublicationWorkflowSaga();
    }

    #[Test]
    public function configurationShouldReturnEventMapping(): void
    {
        // Act
        $configuration = ArticleCreationPublicationWorkflowSaga::configuration();

        // Assert
        $this->assertIsArray($configuration);
        $this->assertArrayHasKey('ArticleCreatedEvent', $configuration);
        $this->assertIsCallable($configuration['ArticleCreatedEvent']);
    }

    #[Test]
    public function configurationCallbackShouldReturnNull(): void
    {
        // Arrange
        $configuration = ArticleCreationPublicationWorkflowSaga::configuration();
        $eventMock = Mockery::mock(ArticleCreatedEvent::class);

        // Act
        $result = $configuration['ArticleCreatedEvent']($eventMock);

        // Assert
        $this->assertNull($result);
    }

    #[Test]
    #[DataProviderExternal(ArticleCreationPublicationWorkflowSagaDataProvider::class, 'provideSagaIdentification')]
    public function handleArticleCreatedEventShouldUpdateStateAndSetDone(
        array $eventData,
        string $expectedSagaId,
    ): void {
        // Arrange
        $processUuid = $eventData['processUuid'];
        $uuid = $eventData['articleUuid'];

        $processUuidVo = Mockery::mock(ProcessUuid::class);
        $processUuidVo->shouldReceive('__toString')
            ->andReturn($processUuid);

        $uuidVo = Mockery::mock(Uuid::class);
        $uuidVo->shouldReceive('__toString')
            ->andReturn($uuid);

        $eventMock = Mockery::mock(ArticleCreatedEvent::class);
        $eventMock->shouldReceive('getProcessUuid')
            ->andReturn($processUuidVo);
        $eventMock->shouldReceive('getUuid')
            ->andReturn($uuidVo);

        $stateMock = Mockery::mock(State::class);
        $stateMock->shouldReceive('set')
            ->once()
            ->with('processId', $processUuid)
            ->andReturnSelf();
        $stateMock->shouldReceive('set')
            ->once()
            ->with('id', $uuid)
            ->andReturnSelf();
        $stateMock->shouldReceive('setDone')
            ->once();

        // Act
        $result = $this->saga->handleArticleCreatedEvent($stateMock, $eventMock);

        // Assert
        $this->assertSame($stateMock, $result);
    }

    #[Test]
    public function sagaShouldImplementStaticallyConfiguredSagaInterface(): void
    {
        // Assert
        $this->assertInstanceOf(StaticallyConfiguredSagaInterface::class, $this->saga);
    }

    #[Test]
    public function constructorShouldCreateInstance(): void
    {
        // Arrange & Act
        $saga = new ArticleCreationPublicationWorkflowSaga();

        // Assert
        $this->assertInstanceOf(ArticleCreationPublicationWorkflowSaga::class, $saga);
    }
}
