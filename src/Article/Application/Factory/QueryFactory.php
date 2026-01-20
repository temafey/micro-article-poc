<?php

declare(strict_types=1);

namespace Micro\Article\Application\Factory;

use Micro\Article\Application\Query\FetchOneArticleQuery;
use Micro\Article\Application\Query\FindByCriteriaArticleQuery;
use Micro\Article\Application\Query\FindOneByArticleQuery;
use MicroModule\Base\Application\Dto\DtoInterface;
use MicroModule\Base\Application\Query\QueryInterface as BaseQueryInterface;
use MicroModule\Base\Domain\Exception\FactoryException;
use MicroModule\Base\Domain\ValueObject\FindCriteria;
use MicroModule\Base\Domain\ValueObject\ProcessUuid;
use MicroModule\Base\Domain\ValueObject\Uuid;
use Ramsey\Uuid\Uuid as RamseyUuid;

/**
 * @class QueryFactory
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class QueryFactory implements QueryFactoryInterface
{
    protected const ALLOWED_QUERIES = [
        self::FETCH_ONE_ARTICLE_QUERY,
        self::FIND_BY_CRITERIA_ARTICLE_QUERY,
        self::FIND_ONE_BY_ARTICLE_QUERY,
    ];

    public function isQueryAllowed(string $queryType): bool
    {
        return in_array($queryType, static::ALLOWED_QUERIES, true);
    }

    /**
     * Make query by query constant.
     */
    public function makeQueryInstanceByType(...$args): BaseQueryInterface
    {
        $type = (string) array_shift($args);
        $processUuid = RamseyUuid::uuid4()->toString();

        // Handle different argument patterns
        $firstArg = $args[0] ?? null;
        $secondArg = $args[1] ?? null;

        return match ($type) {
            self::FETCH_ONE_ARTICLE_QUERY => $this->makeFetchOneArticleQuery(
                is_array($firstArg) ? $processUuid : (is_string($firstArg) && is_string(
                    $secondArg
                ) ? $firstArg : $processUuid),
                is_array($firstArg) ? ($firstArg['uuid'] ?? '') : (is_string($secondArg) ? $secondArg : (is_string(
                    $firstArg
                ) ? $firstArg : ''))
            ),
            self::FIND_BY_CRITERIA_ARTICLE_QUERY => $this->makeFindByCriteriaArticleQuery(
                $processUuid,
                is_array($firstArg) ? $firstArg : []
            ),
            self::FIND_ONE_BY_ARTICLE_QUERY => $this->makeFindOneByArticleQuery(
                $processUuid,
                is_array($firstArg) ? $firstArg : []
            ),
            default => throw new FactoryException(sprintf('Query for type `%s` not found!', $type)),
        };
    }

    /**
     * Make query from DTO.
     */
    public function makeQueryInstanceByTypeFromDto(string $queryType, DtoInterface $dto): BaseQueryInterface
    {
        $data = $dto->normalize();
        $processUuid = RamseyUuid::uuid4()->toString();

        if (isset($data[DtoInterface::KEY_PROCESS_UUID])) {
            $processUuid = $data[DtoInterface::KEY_PROCESS_UUID];
            unset($data[DtoInterface::KEY_PROCESS_UUID]);
        }

        $uuid = null;
        if (isset($data[DtoInterface::KEY_UUID])) {
            $uuid = $data[DtoInterface::KEY_UUID];
            unset($data[DtoInterface::KEY_UUID]);
        }

        return match ($queryType) {
            self::FETCH_ONE_ARTICLE_QUERY => $this->makeFetchOneArticleQuery($processUuid, $uuid ?? ''),
            self::FIND_BY_CRITERIA_ARTICLE_QUERY => $this->makeFindByCriteriaArticleQuery($processUuid, $data),
            self::FIND_ONE_BY_ARTICLE_QUERY => $this->makeFindOneByArticleQuery($processUuid, $data),
            default => throw new FactoryException(sprintf('Query for type `%s` not found!', $queryType)),
        };
    }

    /**
     * Create FetchOneArticleQuery Query.
     */
    public function makeFetchOneArticleQuery(string $processUuid, string $uuid): FetchOneArticleQuery
    {
        return new FetchOneArticleQuery(ProcessUuid::fromNative($processUuid), Uuid::fromNative($uuid));
    }

    /**
     * Create FindByCriteriaArticleQuery Query.
     */
    public function makeFindByCriteriaArticleQuery(string $processUuid, array $findCriteria): FindByCriteriaArticleQuery
    {
        return new FindByCriteriaArticleQuery(
            ProcessUuid::fromNative($processUuid),
            FindCriteria::fromNative($findCriteria)
        );
    }

    /**
     * Create FindOneByArticleQuery Query.
     */
    public function makeFindOneByArticleQuery(string $processUuid, array $findCriteria): FindOneByArticleQuery
    {
        return new FindOneByArticleQuery(
            ProcessUuid::fromNative($processUuid),
            FindCriteria::fromNative($findCriteria)
        );
    }
}
