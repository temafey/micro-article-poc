<?php

declare(strict_types=1);

namespace Micro\Article\Application\Factory;

use Micro\Component\Common\Infrastructure\Mapper\DtoMapperInterface;
use Micro\Article\Application\Dto\ArticleDto;
use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use MicroModule\Base\Application\Dto\DtoInterface;
use MicroModule\Base\Domain\Exception\FactoryException;

/**
 * Factory for creating Article DTOs.
 *
 * Supports multiple creation patterns including ObjectMapper
 * for automatic ReadModel→DTO transformations.
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
class DtoFactory implements DtoFactoryInterface
{
    public function __construct(
        private readonly DtoMapperInterface $dtoMapper,
    ) {
    }

    public function makeDtoByType(...$args): DtoInterface
    {
        $type = (string) array_shift($args);

        return match ($type) {
            self::ARTICLE_DTO => $this->makeArticleDto(...$args),
            default => throw new FactoryException(sprintf('Dto for type `%s` not found!', $type)),
        };
    }

    public function makeArticleDto(
        string $uuid,
        string $title,
        string $shortDescription,
        string $description,
        string $slug,
        int $eventId,
        string $status,
        \DateTimeInterface $publishedAt,
        \DateTimeInterface $archivedAt,
        \DateTimeInterface $createdAt,
        \DateTimeInterface $updatedAt,
    ): ArticleDto {
        return new ArticleDto(
            $uuid,
            $title,
            $shortDescription,
            $description,
            $slug,
            $eventId,
            $status,
            $publishedAt->format(\DateTimeInterface::ATOM),
            $archivedAt->format(\DateTimeInterface::ATOM),
            $createdAt->format(\DateTimeInterface::ATOM),
            $updatedAt->format(\DateTimeInterface::ATOM)
        );
    }

    public function makeArticleDtoFromData(array $data): ArticleDto
    {
        return ArticleDto::denormalize($data);
    }

    public function makeArticleDtoFromReadModel(ArticleReadModelInterface $readModel): ArticleDto
    {
        /** @var ArticleDto */
        return $this->dtoMapper->map($readModel, ArticleDto::class);
    }

    public function makeArticleDtosFromReadModels(iterable $readModels): array
    {
        /** @var array<ArticleDto> */
        return $this->dtoMapper->mapCollection($readModels, ArticleDto::class);
    }
}
