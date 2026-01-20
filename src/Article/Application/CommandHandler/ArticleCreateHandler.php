<?php

declare(strict_types=1);

namespace Micro\Article\Application\CommandHandler;

use Micro\Article\Application\Command\ArticleCreateCommand;
use Micro\Article\Domain\Factory\EntityFactoryInterface;
use Micro\Article\Domain\Repository\EntityStore\ArticleRepositoryInterface;
use MicroModule\Base\Application\Command\CommandInterface;
use MicroModule\Base\Application\CommandHandler\CommandHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @class ArticleCreateHandler
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
#[AutoconfigureTag(name: 'tactician.handler', attributes: [
    'command' => ArticleCreateCommand::class,
    'bus' => 'command.article',
])]
class ArticleCreateHandler implements CommandHandlerInterface
{
    public function __construct(
        protected ArticleRepositoryInterface $articleRepository,
        protected EntityFactoryInterface $entityFactory,
    ) {
    }

    /**
     * Handle ArticleCreateCommand command.
     */
    public function handle(CommandInterface $articleCreateCommand): string
    {
        $articleEntity = $this->entityFactory->createArticleInstance(
            $articleCreateCommand->getProcessUuid(),
            $articleCreateCommand->getArticle(),
            $articleCreateCommand->getUuid() // Pass optional client-generated UUID
        );
        $this->articleRepository->store($articleEntity);

        return $articleEntity->getUuid()
            ->toString();
    }
}
