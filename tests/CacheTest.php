<?php

namespace Middlewares\Tests;

use Middlewares\Cache;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use MatthiasMullie\Scrapbook\Adapters\MemoryStore;
use MatthiasMullie\Scrapbook\Psr6\Pool;

class CacheTest extends \PHPUnit_Framework_TestCase
{
    public function testCache()
    {
        $used = 0;

        $dispatcher = new Dispatcher([
            new Cache(new Pool(new MemoryStore())),
            function () use (&$used) {
                ++$used;

                echo 'Hello';
            },
        ]);

        $request1 = Factory::createServerRequest();
        $request2 = $request1->withHeader('If-Modified-Since', date('D, d M Y H:i:s'));

        $response1 = $dispatcher->dispatch($request1);
        $response2 = $dispatcher->dispatch($request2);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response1);
        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response2);

        $this->assertEquals('Hello', (string) $response1->getBody());
        $this->assertEquals('', (string) $response2->getBody());

        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertEquals(304, $response2->getStatusCode());

        $this->assertSame(1, $used);
    }
}
