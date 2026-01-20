<?php

declare(strict_types=1);

namespace Micro\Article\Application\CommandHandler;

use Micro\Article\Application\Command\ArticleUnpublishCommand;
use Micro\Article\Domain\Repository\EntityStore\ArticleRepositoryInterface;
use MicroModule\Base\Application\Command\CommandInterface;
use MicroModule\Base\Application\CommandHandler\CommandHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @class ArticleUnpublishHandler
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
    'command' => ArticleUnpublishCommand::class,
    'bus' => 'command.article',
])]
class ArticleUnpublishHandler implements CommandHandlerInterface
{
    public function __construct(
        protected ArticleRepositoryInterface $articleRepository,
    ) {
    }

    /**
     * Handle ArticleUnpublishCommand command.
     */
    public function handle(CommandInterface $articleUnpublishCommand): string
    {
        $articleEntity = $this->articleRepository->get($articleUnpublishCommand->getUuid());
        $articleEntity->articleUnpublish($articleUnpublishCommand->getProcessUuid());

        $this->articleRepository->store($articleEntity);

        return $articleEntity->getUuid()
            ->toString();
    }
}
