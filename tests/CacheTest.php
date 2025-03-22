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
            Factory::createServerRequest('GET', '/')->withHeader('If-Modified-Since', date('D, d M Y H:i:s'))
        );

        $this->assertEquals('', (string) $response->getBody());
        $this->assertEquals(304, $response->getStatusCode());

        return $cache;
    }

    /**
     * @depends testModifiedSince
     */
    public function testModifiedSincePost(Cache $cache): void
    {
        $response = Dispatcher::run(
            [
                $cache,
                function () {
                    echo 'Hello';
                },
            ],
            Factory::createServerRequest('POST', '/')
                ->withHeader('If-Modified-Since', date('D, d M Y H:i:s'))
        );

        $this->assertEquals('Hello', (string) $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @depends testModifiedSince
     */
    public function testModified(Cache $cache): void
    {
        $response = Dispatcher::run(
            [
                $cache,
                function () {
                    echo 'Hello';
                },
            ],
            Factory::createServerRequest('GET', '/')
                ->withHeader('If-Modified-Since', date('D, d M Y H:i:s', time() - 100))
        );

        $this->assertEquals('Hello', (string) $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
    }

    /**
     * @param  Cache                 $cache
     * @return array<Cache|callable>
     */
    public static function getETagMiddlewareStack($cache): array
    {
        return [
            $cache,
            function ($request, $next) {
                $response = $next->handle($request);

                return $response->withHeader('ETag', '"my-opaque-etag"');
            },
            function () {
                echo 'Hello';
            },
        ];
    }

    public function testInitialETagState(): Cache
    {
        $cache = new Cache(new Pool(new MemoryStore()));
        $stack = $this->getETagMiddlewareStack($cache);

        $response = Dispatcher::run($stack);

        $this->assertEquals('Hello', (string) $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('"my-opaque-etag"', $response->getHeaderLine('ETag'));

        return $cache;
    }

    /**
     * @depends testInitialETagState
     */
    public function testStrongETag(Cache $cache): void
    {
        $stack = $this->getETagMiddlewareStack($cache);

        $response = Dispatcher::run(
            $stack,
            Factory::createServerRequest('GET', '/')
                ->withHeader('If-None-Match', '"my-opaque-etag"')
        );

        $this->assertEquals(304, $response->getStatusCode());
        $this->assertEquals('', (string) $response->getBody());
        $this->assertEquals('"my-opaque-etag"', $response->getHeaderLine('ETag'));
    }

    /**
     * @depends testInitialETagState
     */
    public function testWeakETag(Cache $cache): void
    {
        $stack = $this->getETagMiddlewareStack($cache);

        $response = Dispatcher::run(
            $stack,
            Factory::createServerRequest('GET', '/')
                ->withHeader('If-None-Match', 'W/"my-opaque-etag"')
        );

        $this->assertEquals(304, $response->getStatusCode());
        $this->assertEquals('', (string) $response->getBody());
        $this->assertEquals('"my-opaque-etag"', $response->getHeaderLine('ETag'));
    }

    /**
     * @depends testInitialETagState
     */
    public function testWrongETag(Cache $cache): void
    {
        $stack = $this->getETagMiddlewareStack($cache);

        $response = Dispatcher::run(
            $stack,
            Factory::createServerRequest('GET', '/')
                ->withHeader('If-None-Match', '"other-opaque-etag"')
        );

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Hello', (string) $response->getBody());
        $this->assertEquals('"my-opaque-etag"', $response->getHeaderLine('ETag'));
    }
}
