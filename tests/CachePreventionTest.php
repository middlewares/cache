<?php
declare(strict_types = 1);

namespace Middlewares\Tests;

use Middlewares\CachePrevention;
use Middlewares\Utils\Dispatcher;
use PHPUnit\Framework\TestCase;

class CachePreventionTest extends TestCase
{
    public function testCachePrevention()
    {
        $response = Dispatcher::run([
            new CachePrevention(),
        ]);

        $this->assertEquals('no-cache, no-store, must-revalidate', $response->getHeaderLine('Cache-Control'));
    }
}
