<?php

namespace Middlewares\Tests;

use Middlewares\CachePrevention;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;

class CachePreventionTest extends \PHPUnit_Framework_TestCase
{
    public function testCachePrevention()
    {
        $request = Factory::createServerRequest();

        $response = (new Dispatcher([
            new CachePrevention(),
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals('no-cache, no-store, must-revalidate', $response->getHeaderLine('Cache-Control'));
    }
}
