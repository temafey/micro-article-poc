<?php

declare(strict_types=1);

namespace Micro\Article\Application\CommandHandler;

use Micro\Article\Application\Command\ArticlePublishCommand;
use Micro\Article\Domain\Repository\EntityStore\ArticleRepositoryInterface;
use MicroModule\Base\Application\Command\CommandInterface;
use MicroModule\Base\Application\CommandHandler\CommandHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @class ArticlePublishHandler
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
    'command' => ArticlePublishCommand::class,
    'bus' => 'command.article',
])]
class ArticlePublishHandler implements CommandHandlerInterface
{
    public function __construct(
        protected ArticleRepositoryInterface $articleRepository,
    ) {
    }

    /**
     * Handle ArticlePublishCommand command.
     */
    public function handle(CommandInterface $articlePublishCommand): string
    {
        $articleEntity = $this->articleRepository->get($articlePublishCommand->getUuid());
        $articleEntity->articlePublish($articlePublishCommand->getProcessUuid());

        $this->articleRepository->store($articleEntity);

        return $articleEntity->getUuid()
            ->toString();
    }
}
