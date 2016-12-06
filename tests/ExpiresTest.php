<?php

namespace Middlewares\Tests;

use Middlewares\Expires;
use Middlewares\Utils\Dispatcher;
use Middlewares\Utils\Factory;

class ExpiresTest extends \PHPUnit_Framework_TestCase
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
    public function testExpires($contentType, $cacheControl, $result)
    {
        $request = Factory::createServerRequest();

        $response = (new Dispatcher([
            new Expires(),
            function () use ($contentType, $cacheControl) {
                return Factory::createResponse()
                    ->withHeader('Cache-Control', $cacheControl)
                    ->withHeader('Content-Type', $contentType);
            },
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertEquals($result, $response->getHeaderLine('Cache-Control'));
        $this->assertTrue($response->hasHeader('Expires'));
    }

    public function testNoExpires()
    {
        $request = Factory::createServerRequest();

        $response = (new Dispatcher([
            new Expires(),
            function () {
                return Factory::createResponse()
                    ->withHeader('Cache-Control', 'no-store');
            },
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertFalse($response->hasHeader('Expires'));

        $request = Factory::createServerRequest([], 'POST');

        $response = (new Dispatcher([
            new Expires(),
        ]))->dispatch($request);

        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $response);
        $this->assertFalse($response->hasHeader('Expires'));
    }
}
