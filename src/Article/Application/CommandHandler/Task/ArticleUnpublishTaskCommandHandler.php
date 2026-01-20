<?php

declare(strict_types=1);

namespace Micro\Article\Application\CommandHandler\Task;

use Micro\Article\Application\Command\Task\ArticleUnpublishTaskCommand;
use Micro\Article\Domain\Repository\TaskRepositoryInterface;
use MicroModule\Base\Application\Command\CommandInterface;
use MicroModule\Base\Application\CommandHandler\CommandHandlerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @class ArticleUnpublishTaskCommandHandler
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
    'command' => ArticleUnpublishTaskCommand::class,
    'bus' => 'command.article',
])]
class ArticleUnpublishTaskCommandHandler implements CommandHandlerInterface
{
    public function __construct(
        protected TaskRepositoryInterface $taskRepository,
    ) {
    }

    /**
     * Handle ArticleUnpublishTaskCommand command.
     */
    public function handle(CommandInterface $articleUnpublishTaskCommand): bool
    {
        $this->taskRepository->addArticleUnpublishTask(
            $articleUnpublishTaskCommand->getProcessUuid(),
            $articleUnpublishTaskCommand->getUuid()
        );

        return true;
    }
}
