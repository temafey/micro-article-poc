<?php

declare(strict_types=1);

namespace Micro\Component\Common\Infrastructure\Service\DataMapper;

use MicroModule\Base\Infrastructure\Service\DataMapper\AbstractDataMapper;
use MicroModule\Base\Infrastructure\Service\DataMapper\Types\JsonType;

class DbalDataMapper extends AbstractDataMapper
{
    protected const FIELD_TYPES = [
        'roles' => JsonType::class,
    ];

    protected function getFieldTypes(): array
    {
        return self::FIELD_TYPES;
    }
}
