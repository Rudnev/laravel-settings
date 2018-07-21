<?php

namespace Rudnev\Settings\Tests\Unit\Cache;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Rudnev\Settings\Cache\Cache;

class CacheTest extends TestCase
{
    const PFX = 'pfx:';

    public function tearDown()
    {
        m::close();
    }

    public function testItemCanBeSet()
    {
        $pfx = self::PFX;

        $repo = $this->getRepo();
        $cache = $this->getCache($repo);
        $repo->shouldNotReceive('put')->with($pfx.'foo', m::any(), m::any());
        $cache->put('foo', null);
        $this->assertFalse($cache->has('foo'));

        $repo = $this->getRepo();
        $cache = $this->getCache($repo);
        $repo->shouldReceive('put')->once()->with($pfx.'foo', 'bar', m::any());
        $cache->put('foo', 'bar');
        $this->assertFalse($cache->has('bar'));
        $this->assertFalse($cache->has('foo.bar'));
        $this->assertTrue($cache->has('foo'));

        $repo = $this->getRepo();
        $cache = $this->getCache($repo);
        $repo->shouldReceive('put')->once()->with($pfx.'foo.bar.baz', 'fish', m::any());
        $cache->put('foo.bar.baz', 'fish');
        $this->assertFalse($cache->has('foo'));
        $this->assertFalse($cache->has('foo.bar'));
        $this->assertTrue($cache->has('foo.bar.baz'));

        $repo = $this->getRepo();
        $cache = $this->getCache($repo);
        $repo->shouldReceive('put')->once()->with($pfx.'foo', 'bar', m::any());
        $repo->shouldReceive('put')->once()->with($pfx.'foo.bar.baz', 'fish', m::any());
        $cache->put('foo', 'bar');
        $cache->put('foo.bar.baz', 'fish');
        $this->assertFalse($cache->has('foo.bar'));
        $this->assertTrue($cache->has('foo.bar.baz'));
        $this->assertTrue($cache->has('foo'));

        $repo = $this->getRepo();
        $cache = $this->getCache($repo);
        $repo->shouldNotReceive('put')->with($pfx.'foo', m::any(), m::any());
        $this->assertEquals(null, $cache->remember('foo', function () {
            return null;
        }));

        $repo = $this->getRepo();
        $cache = $this->getCache($repo);
        $repo->shouldReceive('put')->once()->with($pfx.'foo', 'bar', m::any());
        $this->assertEquals('bar', $cache->remember('foo', function () {
            return 'bar';
        }));
    }

    public function testMultipleItemsCanBeSet()
    {
        $pfx = self::PFX;

        $repo = $this->getRepo();
        $cache = $this->getCache($repo);
        $repo->shouldReceive('setMultiple')->once()->with([$pfx.'foo' => 1, $pfx.'foo.bar.baz' => 2], m::any());
        $cache->putMultiple([
            'foo' => 1,
            'foo.bar.baz' => 2,
            'qux' => null
        ]);
        $this->assertFalse($cache->has('foo.bar'));
        $this->assertTrue($cache->has('foo.bar.baz'));
        $this->assertTrue($cache->has('foo'));
        $this->assertFalse($cache->has('qux'));
    }

    public function testItemCanBeRetrieved()
    {
        $pfx = self::PFX;

        $repo = $this->getRepo();
        $cache = $this->getCache($repo);
        $cache->putMultiple([
            'foo' => 1,
            'foo.bar.baz' => 2,
        ]);

        $repo->shouldReceive('get')->once()->with($pfx.'foo')->andReturn(1);
        $this->assertEquals(1, $cache->get('foo'));

        $repo->shouldReceive('get')->once()->with($pfx.'foo.bar.baz')->andReturn(2);
        $this->assertEquals(2, $cache->get('foo.bar.baz'));
    }

    public function testMultipleItemsCanBeRetrieved()
    {
        $pfx = self::PFX;

        $repo = $this->getRepo();
        $cache = $this->getCache($repo);
        $cache->putMultiple([
            'foo' => 1,
            'foo.bar.baz' => 2,
        ]);

        $repo->shouldReceive('getMultiple')->once()->with([$pfx.'foo', $pfx.'foo.bar.baz', $pfx.'qux'])->andReturn([
            $pfx.'foo' => 1,
            $pfx.'foo.bar.baz' => 2,
            $pfx.'qux' => null,
        ]);
        $this->assertEquals(['foo' => 1, 'foo.bar.baz' => 2], $cache->getMultiple(['foo', 'foo.bar.baz', 'qux']));
    }

    public function testAllItemsCanBeRetrieved()
    {
        $pfx = self::PFX;

        $repo = $this->getRepo();
        $cache = $this->getCache($repo);
        $cache->putMultiple([
            'foo' => 1,
            'foo.bar.baz' => 2,
        ]);

        $repo->shouldReceive('getMultiple')->once()->with([$pfx.'foo', $pfx.'foo.bar.baz'])->andReturn([
            $pfx.'foo' => 1,
            $pfx.'foo.bar.baz' => 2,
        ]);
        $this->assertEquals(['foo' => 1, 'foo.bar.baz' => 2], $cache->all());
    }

    public function testItemCanBeRemoved()
    {
        $pfx = self::PFX;

        $repo = $this->getRepo();
        $cache = $this->getCache($repo);
        $cache->putMultiple([
            'foo' => 1,
            'foo.bar.baz' => 2,
            'products' => 3,
            'products.desk.price' => 4,
            'qux.pax' => 5,
            'qux.pax.fax' => 6,
        ]);

        $repo->shouldReceive('forget')->once()->with($pfx.'foo');
        $repo->shouldReceive('forget')->once()->with($pfx.'foo.bar.baz');
        $cache->forget('foo');
        $this->assertFalse($cache->has('foo'));
        $this->assertFalse($cache->has('foo.bar.baz'));
        $this->assertTrue($cache->has('products'));
        $this->assertTrue($cache->has('products.desk.price'));

        $repo->shouldReceive('forget')->once()->with($pfx.'products');
        $repo->shouldReceive('forget')->once()->with($pfx.'products.desk');
        $repo->shouldReceive('forget')->once()->with($pfx.'products.desk.price');
        $cache->forget('products.desk');
        $this->assertFalse($cache->has('products'));
        $this->assertFalse($cache->has('products.desk.price'));

        $repo->shouldReceive('forget')->once()->with($pfx.'qux');
        $repo->shouldReceive('forget')->once()->with($pfx.'qux.pax');
        $repo->shouldReceive('forget')->once()->with($pfx.'qux.pax.fax');
        $cache->forget('qux.pax.fax');
        $this->assertFalse($cache->has('qux.pax'));
        $this->assertFalse($cache->has('qux.pax.fax'));
    }

    public function testMultipleItemsCanBeRemoved()
    {
        $pfx = self::PFX;

        $repo = $this->getRepo();
        $cache = $this->getCache($repo);
        $cache->putMultiple([
            'foo' => 1,
            'foo.bar.baz' => 2,
            'products' => 3,
            'products.desk.price' => 4,
            'qux.pax' => 5,
            'qux.pax.fax' => 6,
            'bar' => 7,
        ]);

        $repo->shouldReceive('forget')->once()->with($pfx.'foo');
        $repo->shouldReceive('forget')->once()->with($pfx.'foo.bar.baz');
        $repo->shouldReceive('forget')->once()->with($pfx.'products');
        $repo->shouldReceive('forget')->once()->with($pfx.'products.desk.price');
        $repo->shouldReceive('forget')->once()->with($pfx.'qux.pax');
        $repo->shouldReceive('forget')->once()->with($pfx.'qux.pax.fax');
        $cache->forgetMultiple(['foo', 'products.desk', 'qux.pax.fax']);
        $this->assertFalse($cache->has('foo'));
        $this->assertFalse($cache->has('foo.bar.baz'));
        $this->assertFalse($cache->has('products'));
        $this->assertFalse($cache->has('products.desk.price'));
        $this->assertFalse($cache->has('qux'));
        $this->assertFalse($cache->has('qux.pax.fax'));
        $this->assertTrue($cache->has('bar'));
        $this->assertNull($cache->get('foo'));
        $this->assertNull($cache->get('qux.pax'));
    }

    public function testAllItemsCanBeRemoved()
    {
        $repo = $this->getRepo();
        $cache = $this->getCache($repo);
        $cache->putMultiple([
            'foo' => 1,
            'foo.bar.baz' => 2,
            'products' => 3,
            'products.desk.price' => 4,
            'qux.pax' => 5,
            'qux.pax.fax' => 6,
            'bar' => 7,
        ]);

        $cache->flush();
        $this->assertFalse($cache->has('foo'));
        $this->assertFalse($cache->has('foo.bar.baz'));
        $this->assertFalse($cache->has('products'));
        $this->assertFalse($cache->has('products.desk.price'));
        $this->assertFalse($cache->has('qux'));
        $this->assertFalse($cache->has('qux.pax.fax'));
        $this->assertFalse($cache->has('bar'));
        $this->assertNull($cache->get('foo'));
        $this->assertNull($cache->get('qux.pax'));
    }

    protected function getRepo()
    {
        return m::spy('Illuminate\Contracts\Cache\Repository');
    }

    protected function getCache($repo)
    {
        $cache = new Cache(1, self::PFX);
        $cache->setCacheRepository($repo);

        return $cache;
    }
}