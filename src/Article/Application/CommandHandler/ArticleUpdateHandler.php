<?php

declare(strict_types=1);

namespace Micro\Article\Application\CommandHandler;

use Micro\Article\Application\Command\ArticleUpdateCommand;
use Micro\Article\Domain\Repository\EntityStore\ArticleRepositoryInterface;
use MicroModule\Base\Application\Command\CommandInterface;
use MicroModule\Base\Application\CommandHandler\CommandHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @class ArticleUpdateHandler
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
    'command' => ArticleUpdateCommand::class,
    'bus' => 'command.article',
])]
class ArticleUpdateHandler implements CommandHandlerInterface
{
    public function __construct(
        protected ArticleRepositoryInterface $articleRepository,
    ) {
    }

    /**
     * Handle ArticleUpdateCommand command.
     */
    public function handle(CommandInterface $articleUpdateCommand): string
    {
        $articleEntity = $this->articleRepository->get($articleUpdateCommand->getUuid());
        $articleEntity->articleUpdate($articleUpdateCommand->getProcessUuid(), $articleUpdateCommand->getArticle());

        $this->articleRepository->store($articleEntity);

        return $articleEntity->getUuid()
            ->toString();
    }
}
