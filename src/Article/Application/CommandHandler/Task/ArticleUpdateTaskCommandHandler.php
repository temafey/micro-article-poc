<?php

declare(strict_types=1);

namespace Micro\Article\Application\CommandHandler\Task;

use Micro\Article\Application\Command\Task\ArticleUpdateTaskCommand;
use Micro\Article\Domain\Repository\TaskRepositoryInterface;
use MicroModule\Base\Application\Command\CommandInterface;
use MicroModule\Base\Application\CommandHandler\CommandHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @class ArticleUpdateTaskCommandHandler
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
    'command' => ArticleUpdateTaskCommand::class,
    'bus' => 'command.article',
])]
class ArticleUpdateTaskCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        protected TaskRepositoryInterface $taskRepository,
    ) {
    }

    /**
     * Handle ArticleUpdateTaskCommand command.
     */
    public function handle(CommandInterface $articleUpdateTaskCommand): bool
    {
        $this->taskRepository->addArticleUpdateTask(
            $articleUpdateTaskCommand->getProcessUuid(),
            $articleUpdateTaskCommand->getUuid(),
            $articleUpdateTaskCommand->getArticle()
        );

        return true;
    }
}
