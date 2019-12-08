<?php
declare(strict_types = 1);

namespace Middlewares\Tests;

use InvalidArgumentException;
use Middlewares\ClearSiteData;
use Middlewares\Utils\Dispatcher;
use PHPUnit\Framework\TestCase;

class ClearSiteDataTest extends TestCase
{
    public function testDefaultValue()
    {
        $response = Dispatcher::run([
            new ClearSiteData(),
        ]);

        $this->assertEquals('"*"', $response->getHeaderLine('Clear-Site-Data'));
    }

    public function testCustomValue()
    {
        $response = Dispatcher::run([
            new ClearSiteData('cache', 'storage'),
        ]);

        $this->assertEquals('"cache" "storage"', $response->getHeaderLine('Clear-Site-Data'));
    }

    public function testException()
    {
        $this->expectException(InvalidArgumentException::class);

        $response = Dispatcher::run([
            new ClearSiteData('foo'),
        ]);
    }
}
