<?php

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

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals('no-cache, no-store, must-revalidate', $response->getHeaderLine('Cache-Control'));
    }
}
