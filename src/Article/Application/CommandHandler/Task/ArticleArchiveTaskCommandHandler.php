<?php

declare(strict_types=1);

namespace Micro\Article\Application\CommandHandler\Task;

use Micro\Article\Application\Command\Task\ArticleArchiveTaskCommand;
use Micro\Article\Domain\Repository\TaskRepositoryInterface;
use MicroModule\Base\Application\Command\CommandInterface;
use MicroModule\Base\Application\CommandHandler\CommandHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @class ArticleArchiveTaskCommandHandler
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
    'command' => ArticleArchiveTaskCommand::class,
    'bus' => 'command.article',
])]
class ArticleArchiveTaskCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        protected TaskRepositoryInterface $taskRepository,
    ) {
    }

    /**
     * Handle ArticleArchiveTaskCommand command.
     */
    public function handle(CommandInterface $articleArchiveTaskCommand): bool
    {
        $this->taskRepository->addArticleArchiveTask(
            $articleArchiveTaskCommand->getProcessUuid(),
            $articleArchiveTaskCommand->getUuid()
        );

        return true;
    }
}
