<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Filter;

use Symfony\Component\JsonPath\JsonCrawler;

/**
 * JSONPath-based filter implementation using Symfony's JsonCrawler.
 *
 * Provides RFC 9535 JSONPath filtering capabilities for query results.
 *
 * Features:
 * - Filter arrays using JSONPath expressions
 * - Support for comparison operators (==, !=, <, >, <=, >=)
 * - Support for logical operators (&&, ||, !)
 * - Support for built-in functions (length, count, match, search)
 * - Recursive descent (..) for deep searching
 *
 * @example
 * ```php
 * $filter = new JsonPathFilter();
 *
 * // Filter items by status
 * $active = $filter->filter($items, '$[?(@.status == "active")]');
 *
 * // Get all titles from nested structure
 * $titles = $filter->filter($data, '$..title');
 *
 * // Filter by price range
 * $affordable = $filter->filter($products, '$[?(@.price < 100 && @.price > 10)]');
 *
 * // Use regex matching
 * $matching = $filter->filter($items, '$[?match(@.name, "^Article.*")]');
 * ```
 */
final readonly class JsonPathFilter implements JsonPathFilterInterface
{
    public function filter(array $data, string $path): array
    {
        if (empty($data)) {
            return [];
        }

        $jsonString = json_encode($data, JSON_THROW_ON_ERROR);
        $crawler = new JsonCrawler($jsonString);

        return $crawler->find($path);
    }

    public function findFirst(array $data, string $path): mixed
    {
        $results = $this->filter($data, $path);

        return $results[0] ?? null;
    }

    public function matches(array $data, string $path): bool
    {
        return $this->count($data, $path) > 0;
    }

    public function count(array $data, string $path): int
    {
        return count($this->filter($data, $path));
    }
}
