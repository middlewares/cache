<?php
declare(strict_types = 1);

namespace Middlewares\Tests;

use InvalidArgumentException;
use Middlewares\ClearSiteData;
use Middlewares\Utils\Dispatcher;
use PHPUnit\Framework\TestCase;

class ClearSiteDataTest extends TestCase
{
    public function testDefaultValue(): void
    {
        $response = Dispatcher::run([
            new ClearSiteData(),
        ]);

        $this->assertEquals('"*"', $response->getHeaderLine('Clear-Site-Data'));
    }

    public function testCustomValue(): void
    {
        $response = Dispatcher::run([
            new ClearSiteData('cache', 'storage'),
        ]);

        $this->assertEquals('"cache" "storage"', $response->getHeaderLine('Clear-Site-Data'));
    }

    public function testException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $response = Dispatcher::run([
            new ClearSiteData('foo'),
        ]);
    }
}
