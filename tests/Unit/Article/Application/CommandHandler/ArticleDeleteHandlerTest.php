<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\CommandHandler;

use Micro\Article\Application\Command\ArticleDeleteCommand;
use Micro\Article\Application\CommandHandler\ArticleDeleteHandler;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Application\CommandHandler\ArticleDeleteHandlerDataProvider;
use Tests\Unit\Mock\Article\Domain\Repository\ArticleRepositoryMockTrait;

/**
 * Unit tests for ArticleDeleteHandler.
 */
#[CoversClass(ArticleDeleteHandler::class)]
final class ArticleDeleteHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use ArticleRepositoryMockTrait;

    private ArticleDeleteHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createArticleRepositoryMock();
        $this->handler = new ArticleDeleteHandler($this->articleRepositoryMock);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    #[Test]
    #[DataProviderExternal(ArticleDeleteHandlerDataProvider::class, 'provideSuccessScenarios')]
    public function handleShouldDeleteArticleEntity(array $mockArgs, array $mockTimes, string $expectedUuid): void
    {
        // Arrange
        $processUuid = ProcessUuid::fromNative($mockArgs['processUuid']);
        $entityUuid = Uuid::fromNative($mockArgs['entityUuid']);
        $command = new ArticleDeleteCommand($processUuid, $entityUuid);

        // Create mock entity using trait helper
        $articleEntityMock = $this->createArticleEntityMock($expectedUuid);
        $articleEntityMock->shouldReceive('articleDelete')
            ->with(\Mockery::type(ProcessUuid::class))
            ->times($mockTimes['articleDelete']);

        // Configure repository mock using trait methods
        $this->expectArticleRepositoryGet($articleEntityMock, $mockTimes['get']);
        $this->expectArticleRepositoryStore($mockTimes['store']);

        // Act
        $result = $this->handler->handle($command);

        // Assert
        $this->assertSame($expectedUuid, $result);
    }

    #[Test]
    #[DataProviderExternal(ArticleDeleteHandlerDataProvider::class, 'provideFailureScenarios')]
    public function handleShouldThrowExceptionOnFailure(
        array $mockArgs,
        array $mockTimes,
        string $expectedException,
        string $expectedMessage,
    ): void {
        // Arrange
        $processUuid = ProcessUuid::fromNative($mockArgs['processUuid']);
        $entityUuid = Uuid::fromNative($mockArgs['entityUuid']);
        $command = new ArticleDeleteCommand($processUuid, $entityUuid);

        if (isset($mockArgs['getException'])) {
            // Repository get throws exception (entity not found) - use trait method
            $this->expectArticleRepositoryGetThrowsException(
                new $mockArgs['getException']($mockArgs['getExceptionMessage']),
                $mockTimes['get']
            );
        } else {
            // Create mock entity for store failure scenario
            $articleEntityMock = $this->createArticleEntityMock($mockArgs['entityUuid']);
            $articleEntityMock->shouldReceive('articleDelete')
                ->times($mockTimes['articleDelete']);

            $this->expectArticleRepositoryGet($articleEntityMock, $mockTimes['get']);

            // Repository throws exception on store - use trait method
            $this->expectArticleRepositoryStoreThrowsException(
                new $mockArgs['storeException']($mockArgs['storeExceptionMessage']),
                $mockTimes['store']
            );
        }

        // Assert
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);

        // Act
        $this->handler->handle($command);
    }

    #[Test]
    public function handlerShouldImplementCommandHandlerInterface(): void
    {
        // Assert
        $this->assertInstanceOf(
            \MicroModule\Base\Application\CommandHandler\CommandHandlerInterface::class,
            $this->handler
        );
    }

    #[Test]
    public function handleShouldReturnUuidString(): void
    {
        // Arrange
        $expectedUuid = '550e8400-e29b-41d4-a716-446655440000';
        $processUuid = ProcessUuid::fromNative('f47ac10b-58cc-4372-a567-0e02b2c3d479');
        $entityUuid = Uuid::fromNative($expectedUuid);
        $command = new ArticleDeleteCommand($processUuid, $entityUuid);

        // Create mock entity using trait helper
        $articleEntityMock = $this->createArticleEntityMock($expectedUuid);
        $articleEntityMock->shouldReceive('articleDelete')->once();

        // Configure mocks using trait methods
        $this->expectArticleRepositoryGet($articleEntityMock, 1);
        $this->expectArticleRepositoryStore(1);

        // Act
        $result = $this->handler->handle($command);

        // Assert
        $this->assertIsString($result);
        $this->assertSame($expectedUuid, $result);
    }
}
