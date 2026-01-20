<?php

declare(strict_types=1);

namespace Micro\Article\Application\CommandHandler;

use Micro\Article\Application\Command\ArticleDeleteCommand;
use Micro\Article\Domain\Repository\EntityStore\ArticleRepositoryInterface;
use MicroModule\Base\Application\Command\CommandInterface;
use MicroModule\Base\Application\CommandHandler\CommandHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @class ArticleDeleteHandler
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
    'command' => ArticleDeleteCommand::class,
    'bus' => 'command.article',
])]
class ArticleDeleteHandler implements CommandHandlerInterface
{
    public function __construct(
        protected ArticleRepositoryInterface $articleRepository,
    ) {
    }

    /**
     * Handle ArticleDeleteCommand command.
     */
    public function handle(CommandInterface $articleDeleteCommand): string
    {
        $articleEntity = $this->articleRepository->get($articleDeleteCommand->getUuid());
        $articleEntity->articleDelete($articleDeleteCommand->getProcessUuid());

        $this->articleRepository->store($articleEntity);

        return $articleEntity->getUuid()
            ->toString();
    }
}
