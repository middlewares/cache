<?php
declare(strict_types = 1);

namespace Middlewares\Tests;

use MatthiasMullie\Scrapbook\Adapters\MemoryStore;
use MatthiasMullie\Scrapbook\Psr6\Pool;
use Middlewares\Cache;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    public function testInitialState(): Cache
    {
        $cache = new Cache(new Pool(new MemoryStore()));

        $response = Dispatcher::run([
            $cache,
            function () {
                echo 'Hello';
            },
        ]);

        $this->assertEquals('Hello', (string) $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());

        return $cache;
    }

    /**
     * @depends testInitialState
     */
    public function testModifiedSince(Cache $cache): Cache
    {
        $response = Dispatcher::run(
            [
                $cache,
                function () {
                    echo 'Hello';
                },
            ],
            Factory::createServerRequest()->withHeader('If-Modified-Since', date('D, d M Y H:i:s'))
        );

        $this->assertEquals('', (string) $response->getBody());
        $this->assertEquals(304, $response->getStatusCode());

        return $cache;
    }

    /**
     * @depends testModifiedSince
     */
    public function testModifiedSincePost(Cache $cache)
    {
        $response = Dispatcher::run(
            [
                $cache,
                function () {
                    echo 'Hello';
                },
            ],
            Factory::createServerRequest()
                ->withMethod('POST')
                ->withHeader('If-Modified-Since', date('D, d M Y H:i:s'))
        );

        $this->assertEquals('Hello', (string) $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testModifiedSince
     */
    public function testModified(Cache $cache)
    {
        $response = Dispatcher::run(
            [
                $cache,
                function () {
                    echo 'Hello';
                },
            ],
            Factory::createServerRequest()
                ->withHeader('If-Modified-Since', date('D, d M Y H:i:s', time() - 100))
        );

        $this->assertEquals('Hello', (string) $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
    }
}
