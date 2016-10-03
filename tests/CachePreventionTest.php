<?php

namespace Middlewares\Tests;

use Middlewares\CachePrevention;
use Zend\Diactoros\Request;
use Zend\Diactoros\Response;
use mindplay\middleman\Dispatcher;

class CachePreventionTest extends \PHPUnit_Framework_TestCase
{
    public function testCachePrevention()
    {
        $response = (new Dispatcher([
            new CachePrevention(),
            function () {
                return new Response();
            },
        ]))->dispatch(new Request());

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals('no-cache, no-store, must-revalidate', $response->getHeaderLine('Cache-Control'));
    }
}
