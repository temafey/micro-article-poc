<?php

declare(strict_types=1);

namespace Micro\Article\Application\Dto;

use MicroModule\Base\Application\Dto\DtoInterface;

/**
 * @interface ArticleDtoInterface
 */
interface ArticleDtoInterface extends DtoInterface
{
    public const UUID = 'uuid';

    public const TITLE = 'title';

    public const SHORT_DESCRIPTION = 'short_description';

    public const DESCRIPTION = 'description';

    public const SLUG = 'slug';

    public const EVENT_ID = 'event_id';

    public const STATUS = 'status';

    public const PUBLISHED_AT = 'published_at';

    public const ARCHIVED_AT = 'archived_at';

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';
}
