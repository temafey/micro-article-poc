<?php

declare(strict_types=1);

namespace Micro\Article\Application\Saga;

use Broadway\Saga\Metadata\StaticallyConfiguredSagaInterface;
use Broadway\Saga\State;
use Micro\Component\Common\Infrastructure\Observability\Traits\TracedSagaTrait;
use Micro\Article\Domain\Event\ArticleCreatedEvent;
use MicroModule\Saga\AbstractSaga;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @class ArticleCreationPublicationWorkflowSaga
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
#[AutoconfigureTag(name: 'broadway.saga', attributes: [
    'type' => 'article-creation-publication-workflow',
])]
class ArticleCreationPublicationWorkflowSaga extends AbstractSaga implements StaticallyConfiguredSagaInterface
{
    use TracedSagaTrait;

    protected const STATE_CRITERIA_KEY = 'processId';

    protected const STATE_ID_KEY = 'id';

    /**
     * Saga configuration method, return map of events and state search criteria.
     */
    public static function configuration()
    {
        return [
            'ArticleCreatedEvent' => static function (ArticleCreatedEvent $event): null {
                return null; // no criteria, start of a new saga
            },
        ];
    }

    /**
     * Handle ArticleCreatedEvent event with OpenTelemetry tracing.
     */
    public function handleArticleCreatedEvent(State $state, ArticleCreatedEvent $event): State
    {
        return $this->traceEventHandler($event, function () use ($state, $event): State {
            $state->set(self::STATE_CRITERIA_KEY, (string) $event->getProcessUuid());
            $state->set(self::STATE_ID_KEY, (string) $event->getUuid());
            $state->setDone();

            return $state;
        });
    }
}
