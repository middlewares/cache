<?php

namespace Middlewares\Tests;

use Middlewares\CachePrevention;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\CallableMiddleware;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response;

class CachePreventionTest extends \PHPUnit_Framework_TestCase
{
    public function testCachePrevention()
    {
        $response = (new Dispatcher([
            new CachePrevention(),
            new CallableMiddleware(function () {
                return new Response();
            }),
        ]))->dispatch(new ServerRequest());

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals('no-cache, no-store, must-revalidate', $response->getHeaderLine('Cache-Control'));
    }
}
