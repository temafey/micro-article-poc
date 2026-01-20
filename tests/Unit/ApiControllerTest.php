<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

/**
 * Stub test file - original tested App\Controller\ApiController which does not exist.
 *
 * @todo Remove or implement proper tests when controller is implemented
 */
#[Group('skip')]
final class ApiControllerTest extends TestCase
{
    public function testPlaceholder(): void
    {
        $this->markTestSkipped('App\Controller\ApiController does not exist in the codebase');
    }
}
