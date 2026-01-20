<?php

declare(strict_types=1);

namespace Micro\Article\Infrastructure\Repository\Storage;

use MicroModule\Base\Infrastructure\Repository\AbstractDBALReadModelStore;

/**
 * @class DBALReadModelStore
 */
class DBALReadModelStore extends AbstractDBALReadModelStore
{
    /**
     * Get default query for fetching article data.
     *
     * @return string Default SELECT query
     */
    protected function getDefaultQuery(): string
    {
        return sprintf('SELECT * FROM %s', $this->tableName);
    }
}
