<?php

declare(strict_types=1);

namespace Micro\Article\Application\Factory;

use Micro\Article\Application\Dto\ArticleDto;
use Micro\Article\Domain\ReadModel\ArticleReadModelInterface;
use MicroModule\Base\Application\Factory\DtoFactoryInterface as BaseDtoFactoryInterface;

/**
 * Factory for creating Article DTOs.
 *
 * Supports multiple creation patterns:
 * - From explicit parameters (makeArticleDto)
 * - From array data (makeArticleDtoFromData)
 * - From ReadModel using ObjectMapper (makeArticleDtoFromReadModel)
 *
 * @SuppressWarnings(PHPMD.ExcessiveParameterList)
 */
interface DtoFactoryInterface extends BaseDtoFactoryInterface
{
    public const ARTICLE_DTO = 'ArticleDto';

    /**
     * Create ArticleDto from explicit parameters.
     */
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
    ): ArticleDto;

    /**
     * Create ArticleDto from array data.
     */
    public function makeArticleDtoFromData(array $data): ArticleDto;

    /**
     * Create ArticleDto from ReadModel using ObjectMapper.
     *
     * Uses Symfony ObjectMapper with configured transforms
     * for automatic property mapping and type conversion.
     */
    public function makeArticleDtoFromReadModel(ArticleReadModelInterface $readModel): ArticleDto;

    /**
     * Create multiple DTOs from a collection of ReadModels.
     *
     * @param iterable<ArticleReadModelInterface> $readModels
     *
     * @return array<ArticleDto>
     */
    public function makeArticleDtosFromReadModels(iterable $readModels): array;
}
