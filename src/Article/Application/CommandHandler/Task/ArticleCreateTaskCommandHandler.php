<?php

declare(strict_types=1);

namespace Micro\Article\Application\CommandHandler\Task;

use Micro\Article\Application\Command\Task\ArticleCreateTaskCommand;
use Micro\Article\Domain\Repository\TaskRepositoryInterface;
use MicroModule\Base\Application\Command\CommandInterface;
use MicroModule\Base\Application\CommandHandler\CommandHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @class ArticleCreateTaskCommandHandler
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
    'command' => ArticleCreateTaskCommand::class,
    'bus' => 'command.article',
])]
class ArticleCreateTaskCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        protected TaskRepositoryInterface $taskRepository,
    ) {
    }

    /**
     * Handle ArticleCreateTaskCommand command.
     */
    public function handle(CommandInterface $articleCreateTaskCommand): bool
    {
        $this->taskRepository->addArticleCreateTask(
            $articleCreateTaskCommand->getProcessUuid(),
            $articleCreateTaskCommand->getArticle()
        );

        return true;
    }
}
