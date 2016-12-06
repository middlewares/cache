<?php

namespace Middlewares\Tests;

use Middlewares\CachePrevention;
use Middlewares\Utils\Dispatcher;

class CachePreventionTest extends \PHPUnit_Framework_TestCase
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
