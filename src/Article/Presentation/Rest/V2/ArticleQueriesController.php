<?php

declare(strict_types=1);

namespace Micro\Article\Presentation\Rest\V2;

use League\Tactician\CommandBus;
use Micro\Component\Common\Infrastructure\Api\ApiVersion;
use Micro\Article\Application\Dto\ArticleDto;
use Micro\Article\Application\Factory\QueryFactoryInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @class ArticleQueriesController
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
#[ApiVersion('v2')]
class ArticleQueriesController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'tactician.commandbus.query.article')]
        protected CommandBus $queryBus,
        protected QueryFactoryInterface $queryFactory,
    ) {
    }

    /**
     * Request of "ArticleDto" to process "findByCriteriaArticle" query.
     */
    #[Route('', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Request of "ArticleDto" to process "findByCriteriaArticle" query',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: ArticleDto::class)))
    )]
    #[OA\Tag(name: 'article-queries')]
    #[OA\Parameter(
        name: 'uuid',
        description: "The field 'uuid' of 'ArticleDto'",
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'title',
        description: "The field 'title' of 'ArticleDto'",
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'short_description',
        description: "The field 'short_description' of 'ArticleDto'",
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'description',
        description: "The field 'description' of 'ArticleDto'",
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'slug',
        description: "The field 'slug' of 'ArticleDto'",
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'event_id',
        description: "The field 'event_id' of 'ArticleDto'",
        in: 'query',
        schema: new OA\Schema(type: 'int')
    )]
    #[OA\Parameter(
        name: 'status',
        description: "The field 'status' of 'ArticleDto'",
        in: 'query',
        schema: new OA\Schema(type: 'string')
    )]
    #[OA\Parameter(
        name: 'published_at',
        description: "The field 'published_at' of 'ArticleDto'",
        in: 'query',
        schema: new OA\Schema(type: '\DateTimeInterface')
    )]
    #[OA\Parameter(
        name: 'archived_at',
        description: "The field 'archived_at' of 'ArticleDto'",
        in: 'query',
        schema: new OA\Schema(type: '\DateTimeInterface')
    )]
    #[OA\Parameter(
        name: 'created_at',
        description: "The field 'created_at' of 'ArticleDto'",
        in: 'query',
        schema: new OA\Schema(type: '\DateTimeInterface')
    )]
    #[OA\Parameter(
        name: 'updated_at',
        description: "The field 'updated_at' of 'ArticleDto'",
        in: 'query',
        schema: new OA\Schema(type: '\DateTimeInterface')
    )]
    public function getAll(#[MapQueryString] ArticleDto $articleDto): JsonResponse
    {
        $result = $this->queryBus->handle(
            $this->queryFactory->makeQueryInstanceByTypeFromDto(
                QueryFactoryInterface::FIND_BY_CRITERIA_ARTICLE_QUERY,
                $articleDto
            )
        );

        return new JsonResponse($result);
    }

    /**
     * Process "fetchOneArticle" query using route parameters.
     */
    #[Route('/{uuid}', requirements: [
        'uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
    ], methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Process "fetchOneArticle" query using route parameters',
        content: new OA\JsonContent(type: 'object')
    )]
    #[OA\Tag(name: 'article-queries')]
    public function getOne(string $uuid): JsonResponse
    {
        $result = $this->queryBus->handle(
            $this->queryFactory->makeQueryInstanceByType(
                QueryFactoryInterface::FETCH_ONE_ARTICLE_QUERY,
                [
                    'uuid' => $uuid,
                ]
            )
        );

        return new JsonResponse($result);
    }

    /**
     * Process "findByCriteriaArticle" query using route parameters.
     */
    #[Route('/slug/{slug}', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Process "findByCriteriaArticle" query using route parameters',
        content: new OA\JsonContent(type: 'object')
    )]
    #[OA\Tag(name: 'article-queries')]
    public function getBySlug(string $slug): JsonResponse
    {
        $result = $this->queryBus->handle(
            $this->queryFactory->makeQueryInstanceByType(
                QueryFactoryInterface::FIND_BY_CRITERIA_ARTICLE_QUERY,
                [
                    'slug' => $slug,
                ]
            )
        );

        return new JsonResponse($result);
    }

    /**
     * Process "findByCriteriaArticle" query using route parameters.
     */
    #[Route('/event/{event_id}', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Process "findByCriteriaArticle" query using route parameters',
        content: new OA\JsonContent(type: 'object')
    )]
    #[OA\Tag(name: 'article-queries')]
    public function getByEvent(string $event_id): JsonResponse
    {
        $result = $this->queryBus->handle(
            $this->queryFactory->makeQueryInstanceByType(
                QueryFactoryInterface::FIND_BY_CRITERIA_ARTICLE_QUERY,
                [
                    'event_id' => $event_id,
                ]
            )
        );

        return new JsonResponse($result);
    }

    /**
     * Process "findByCriteriaArticle" query.
     */
    #[Route('/published', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Process "findByCriteriaArticle" query',
        content: new OA\JsonContent(type: 'object')
    )]
    #[OA\Tag(name: 'article-queries')]
    public function getPublished(): JsonResponse
    {
        $result = $this->queryBus->handle(
            $this->queryFactory->makeQueryInstanceByType(
                QueryFactoryInterface::FIND_BY_CRITERIA_ARTICLE_QUERY,
                [
                    'status' => 'published',
                ]
            )
        );

        return new JsonResponse($result);
    }

    /**
     * Process "findByCriteriaArticle" query.
     */
    #[Route('/archived', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Process "findByCriteriaArticle" query',
        content: new OA\JsonContent(type: 'object')
    )]
    #[OA\Tag(name: 'article-queries')]
    public function getArchived(): JsonResponse
    {
        $result = $this->queryBus->handle(
            $this->queryFactory->makeQueryInstanceByType(
                QueryFactoryInterface::FIND_BY_CRITERIA_ARTICLE_QUERY,
                [
                    'status' => 'archived',
                ]
            )
        );

        return new JsonResponse($result);
    }
}
