<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Service;

use MicroModule\Base\Infrastructure\Service\DataMapper\AbstractDataMapper;
use MicroModule\Base\Infrastructure\Service\DataMapper\Types\JsonType;

class DataMapper extends AbstractDataMapper
{
    protected const FIELD_TYPES = [
        // JSON field type mappings for proper serialization
        'income' => JsonType::class,
    ];

    protected function getFieldTypes(): array
    {
        return self::FIELD_TYPES;
    }
}
