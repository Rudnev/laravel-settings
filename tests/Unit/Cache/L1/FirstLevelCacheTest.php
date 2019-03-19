<?php

namespace Rudnev\Settings\Tests\Unit\Cache\L1;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Rudnev\Settings\Cache\L1\FirstLevelCache;
use Rudnev\Settings\Cache\L1\FirstLevelRegion;

class FirstLevelCacheTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testRegionCanBeCreatedAndRetrieved()
    {
        $cache = new FirstLevelCache();

        $region = $cache->region('foo');
        $this->assertInstanceOf(FirstLevelRegion::class, $region);
        $this->assertEquals('foo', $region->getName());
        $this->assertEquals(spl_object_id($region), spl_object_id($cache->region('foo')));
    }

    public function testCacheCanBeFlushed()
    {
        $cache = new FirstLevelCache();

        $cache->region('foo')->put('bar', 123);
        $this->assertEquals(123, $cache->region('foo')->get('bar'));

        $cache->flush();
        $this->assertNull($cache->region('foo')->get('bar'));
    }
}
