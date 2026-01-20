<?php

declare(strict_types=1);

namespace Micro\Article\Application\CommandHandler\Task;

use Micro\Article\Application\Command\Task\ArticlePublishTaskCommand;
use Micro\Article\Domain\Repository\TaskRepositoryInterface;
use MicroModule\Base\Application\Command\CommandInterface;
use MicroModule\Base\Application\CommandHandler\CommandHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @class ArticlePublishTaskCommandHandler
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
    'command' => ArticlePublishTaskCommand::class,
    'bus' => 'command.article',
])]
class ArticlePublishTaskCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        protected TaskRepositoryInterface $taskRepository,
    ) {
    }

    /**
     * Handle ArticlePublishTaskCommand command.
     */
    public function handle(CommandInterface $articlePublishTaskCommand): bool
    {
        $this->taskRepository->addArticlePublishTask(
            $articlePublishTaskCommand->getProcessUuid(),
            $articlePublishTaskCommand->getUuid()
        );

        return true;
    }
}
