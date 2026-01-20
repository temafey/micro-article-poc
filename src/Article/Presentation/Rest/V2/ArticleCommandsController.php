<?php

declare(strict_types=1);

namespace Micro\Article\Presentation\Rest\V2;

use League\Tactician\CommandBus;
use Micro\Component\Common\Infrastructure\Api\ApiVersion;
use Micro\Article\Application\Dto\ArticleDto;
use Micro\Article\Application\Factory\CommandFactoryInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @class ArticleCommandsController
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
class ArticleCommandsController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'tactician.commandbus.command.article')]
        protected CommandBus $commandBus,
        protected CommandFactoryInterface $commandFactory,
    ) {
    }

    /**
     * Request of "ArticleDto" to process "articleCreate" command.
     */
    #[Route('', methods: ['POST'])]
    #[OA\Response(
        response: 200,
        description: 'Request of "ArticleDto" to process "articleCreate" command',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: ArticleDto::class)))
    )]
    #[OA\Tag(name: 'article-commands')]
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
    public function create(#[MapRequestPayload] ArticleDto $articleDto): JsonResponse
    {
        $result = $this->commandBus->handle(
            $this->commandFactory->makeCommandInstanceByTypeFromDto(
                CommandFactoryInterface::ARTICLE_CREATE_COMMAND,
                $articleDto
            )
        );

        return new JsonResponse([
            'uuid' => $result,
        ]);
    }

    /**
     * Request of "ArticleDto" to process "articleUpdate" command.
     */
    #[Route('/{uuid}', requirements: [
        'uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
    ], methods: ['PUT'])]
    #[OA\Response(
        response: 200,
        description: 'Request of "ArticleDto" to process "articleUpdate" command',
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: ArticleDto::class)))
    )]
    #[OA\Tag(name: 'article-commands')]
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
    public function update(string $uuid, #[MapRequestPayload] ArticleDto $articleDto): JsonResponse
    {
        // Merge route UUID with request body data to create complete DTO
        $dtoData = $articleDto->normalize();
        $dtoData[ArticleDto::UUID] = $uuid;
        $articleDtoWithUuid = ArticleDto::denormalize($dtoData);

        $result = $this->commandBus->handle(
            $this->commandFactory->makeCommandInstanceByTypeFromDto(
                CommandFactoryInterface::ARTICLE_UPDATE_COMMAND,
                $articleDtoWithUuid
            )
        );

        return new JsonResponse([
            'uuid' => $result,
        ]);
    }

    /**
     * Process "articlePublish" command using route parameters.
     */
    #[Route('/{uuid}/publish', requirements: [
        'uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
    ], methods: ['PUT'])]
    #[OA\Response(
        response: 200,
        description: 'Process "articlePublish" command using route parameters',
        content: new OA\JsonContent(type: 'object')
    )]
    #[OA\Tag(name: 'article-commands')]
    public function publish(string $uuid): JsonResponse
    {
        $processUuid = Uuid::uuid6()->toString();

        $result = $this->commandBus->handle(
            $this->commandFactory->makeCommandInstanceByType(
                CommandFactoryInterface::ARTICLE_PUBLISH_COMMAND,
                $processUuid,
                $uuid
            )
        );

        return new JsonResponse([
            'uuid' => $result,
        ]);
    }

    /**
     * Process "articleUnpublish" command using route parameters.
     */
    #[Route('/{uuid}/unpublish', requirements: [
        'uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
    ], methods: ['PUT'])]
    #[OA\Response(
        response: 200,
        description: 'Process "articleUnpublish" command using route parameters',
        content: new OA\JsonContent(type: 'object')
    )]
    #[OA\Tag(name: 'article-commands')]
    public function unpublish(string $uuid): JsonResponse
    {
        $processUuid = Uuid::uuid6()->toString();

        $result = $this->commandBus->handle(
            $this->commandFactory->makeCommandInstanceByType(
                CommandFactoryInterface::ARTICLE_UNPUBLISH_COMMAND,
                $processUuid,
                $uuid
            )
        );

        return new JsonResponse([
            'uuid' => $result,
        ]);
    }

    /**
     * Process "articleArchive" command using route parameters.
     */
    #[Route('/{uuid}/archive', requirements: [
        'uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
    ], methods: ['PUT'])]
    #[OA\Response(
        response: 200,
        description: 'Process "articleArchive" command using route parameters',
        content: new OA\JsonContent(type: 'object')
    )]
    #[OA\Tag(name: 'article-commands')]
    public function archive(string $uuid): JsonResponse
    {
        $processUuid = Uuid::uuid6()->toString();

        $result = $this->commandBus->handle(
            $this->commandFactory->makeCommandInstanceByType(
                CommandFactoryInterface::ARTICLE_ARCHIVE_COMMAND,
                $processUuid,
                $uuid
            )
        );

        return new JsonResponse([
            'uuid' => $result,
        ]);
    }

    /**
     * Process "articleDelete" command using route parameters.
     */
    #[Route('/{uuid}', requirements: [
        'uuid' => '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}',
    ], methods: ['DELETE'])]
    #[OA\Response(
        response: 200,
        description: 'Process "articleDelete" command using route parameters',
        content: new OA\JsonContent(type: 'object')
    )]
    #[OA\Tag(name: 'article-commands')]
    public function delete(string $uuid): JsonResponse
    {
        $processUuid = Uuid::uuid6()->toString();

        $result = $this->commandBus->handle(
            $this->commandFactory->makeCommandInstanceByType(
                CommandFactoryInterface::ARTICLE_DELETE_COMMAND,
                $processUuid,
                $uuid
            )
        );

        return new JsonResponse([
            'uuid' => $result,
        ]);
    }
}
