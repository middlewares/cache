<?php
declare(strict_types = 1);

namespace Middlewares\Tests;

use Middlewares\Expires;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;
use PHPUnit\Framework\TestCase;

class ExpiresTest extends TestCase
{
    public function expiresProvider()
    {
        return [
            [
                'text/css',
                '',
                'max-age='.(strtotime('+1 year') - time()),
            ],
            [
                'text/css',
                'public',
                'public, max-age='.(strtotime('+1 year') - time()),
            ],
            [
                '',
                'public',
                'public, max-age='.(strtotime('+1 month') - time()),
            ],
            [
                '',
                'public, max-age=35',
                'public, max-age=35',
            ],
        ];
    }

    /**
     * @dataProvider expiresProvider
     */
    public function testExpires(string $contentType, string $cacheControl, string $result)
    {
        $response = Dispatcher::run([
            new Expires(),
            function () use ($contentType, $cacheControl) {
                return Factory::createResponse()
                    ->withHeader('Cache-Control', $cacheControl)
                    ->withHeader('Content-Type', $contentType);
            },
        ]);

        $this->assertEquals($result, $response->getHeaderLine('Cache-Control'));
        $this->assertTrue($response->hasHeader('Expires'));
    }

    public function testNoExpires()
    {
        $response = Dispatcher::run([
            new Expires(),
            function () {
                return Factory::createResponse()
                    ->withHeader('Cache-Control', 'no-store');
            },
        ]);

        $this->assertFalse($response->hasHeader('Expires'));

        $request = Factory::createServerRequest([], 'POST');

        $response = Dispatcher::run([
            new Expires(),
        ], $request);

        $this->assertFalse($response->hasHeader('Expires'));
    }

    public function testDefaultExpires()
    {
        $response = Dispatcher::run(
            [
                (new Expires())->defaultExpires('+1 year'),
            ],
            Factory::createServerRequest()->withHeader('Content-Type', 'foo/bar')
        );

        $this->assertTrue($response->hasHeader('Expires'));
        $this->assertEquals(
            'max-age='.(strtotime('+1 year') - time()),
            $response->getHeaderLine('Cache-Control')
        );
    }
}
