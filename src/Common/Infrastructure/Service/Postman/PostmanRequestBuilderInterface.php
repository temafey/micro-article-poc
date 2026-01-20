<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Service\Postman;

/**
 * Postman Request Builder Interface.
 *
 * Builds individual Postman request structures from OpenAPI operations.
 * Follows Single Responsibility Principle - only builds requests.
 */
interface PostmanRequestBuilderInterface
{
    /**
     * Builds a Postman request from an OpenAPI operation.
     *
     * Converts OpenAPI operation definition into Postman Collection v2.1.0 request format.
     *
     * @param array<string,mixed> $operation   Operation definition from OpenAPI
     * @param array<string,mixed> $openApiSpec Full OpenAPI specification for schema resolution
     *
     * @return array<string,mixed> Postman request structure
     */
    public function buildRequest(array $operation, array $openApiSpec): array;
}
