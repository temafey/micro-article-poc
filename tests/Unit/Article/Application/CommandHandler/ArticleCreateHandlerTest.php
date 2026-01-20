<?php

declare(strict_types=1);

namespace Tests\Unit\Article\Application\CommandHandler;

use Micro\Article\Application\Command\ArticleCreateCommand;
use Micro\Article\Application\CommandHandler\ArticleCreateHandler;
use Micro\Article\Domain\ValueObject\Article;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Unit\DataProvider\Article\Application\CommandHandler\ArticleCreateHandlerDataProvider;
use Tests\Unit\Mock\Article\Domain\Factory\EntityFactoryMockTrait;
use Tests\Unit\Mock\Article\Domain\Repository\ArticleRepositoryMockTrait;

/**
 * Unit tests for ArticleCreateHandler.
 */
#[CoversClass(ArticleCreateHandler::class)]
final class ArticleCreateHandlerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use EntityFactoryMockTrait;
    use ArticleRepositoryMockTrait;

    private ArticleCreateHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createArticleRepositoryMock();
        $this->createEntityFactoryMock();

        $this->handler = new ArticleCreateHandler($this->articleRepositoryMock, $this->entityFactoryMock);
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    #[Test]
    #[DataProviderExternal(ArticleCreateHandlerDataProvider::class, 'provideSuccessScenarios')]
    public function handleShouldCreateAndStoreArticleEntity(
        array $articleData,
        array $mockArgs,
        array $mockTimes,
        string $expectedUuid,
    ): void {
        // Arrange
        $processUuid = ProcessUuid::fromNative($mockArgs['processUuid']);
        $article = Article::fromNative($articleData);
        $command = new ArticleCreateCommand($processUuid, $article);

        // Configure mocks using traits
        $this->expectEntityFactoryCreateArticleInstance($expectedUuid, $mockTimes['createEntity']);
        $this->expectArticleRepositoryStore($mockTimes['store']);

        // Act
        $result = $this->handler->handle($command);

        // Assert
        $this->assertSame($expectedUuid, $result);
    }

    #[Test]
    #[DataProviderExternal(ArticleCreateHandlerDataProvider::class, 'provideFailureScenarios')]
    public function handleShouldThrowExceptionOnFailure(
        array $articleData,
        array $mockArgs,
        array $mockTimes,
        string $expectedException,
        string $expectedMessage,
    ): void {
        // Arrange
        $processUuid = ProcessUuid::fromNative($mockArgs['processUuid']);
        $article = Article::fromNative($articleData);
        $command = new ArticleCreateCommand($processUuid, $article);

        if (isset($mockArgs['factoryException'])) {
            // Factory throws exception - use trait method
            $this->expectEntityFactoryCreateArticleInstanceThrowsException(
                new $mockArgs['factoryException']($mockArgs['factoryExceptionMessage']),
                $mockTimes['createEntity']
            );
        } else {
            // Create mock entity for repository failure scenario
            $this->expectEntityFactoryCreateArticleInstance($mockArgs['entityUuid'], $mockTimes['createEntity']);

            // Repository throws exception - use trait method
            $this->expectArticleRepositoryStoreThrowsException(
                new $mockArgs['repositoryException']($mockArgs['repositoryExceptionMessage']),
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
        $article = Article::fromNative([
            'title' => 'Test Title',
            'short_description' => 'Test short description.',
            'description' => 'This is a test description that meets the minimum fifty character length requirement.',
        ]);
        $command = new ArticleCreateCommand($processUuid, $article);

        // Configure mocks using traits
        $this->expectEntityFactoryCreateArticleInstance($expectedUuid, 1);
        $this->expectArticleRepositoryStore(1);

        // Act
        $result = $this->handler->handle($command);

        // Assert
        $this->assertIsString($result);
        $this->assertSame($expectedUuid, $result);
    }
}
