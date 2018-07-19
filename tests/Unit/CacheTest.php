<?php

namespace Rudnev\Settings\Tests\Unit;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Rudnev\Settings\Cache;

class CacheTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testItemCanBeSetAndRetrieved()
    {
        $repo = $this->getRepo();
        $cache = $this->getCache($repo);

        $repo->shouldReceive('get')->once()->with('_pfx:cached-items')->andReturnNull();
        $repo->shouldReceive('put')->once()->with('pfx:foo', 'bar', 1);
        $repo->shouldReceive('put')->once()->with('_pfx:cached-items', ['pfx:foo' => null], 1);
        $cache->put('foo', 'bar');

        $repo->shouldReceive('get')->once()->with('_pfx:cached-items')->andReturnNull();
        $repo->shouldReceive('setMultiple')->once()->with([
            'pfx:foo' => 'bar',
            'pfx:bar' => 'foo',
            'pfx:baz' => null,
        ], 1);
        $repo->shouldReceive('put')->once()->with('_pfx:cached-items', [
            'pfx:foo' => null,
            'pfx:bar' => null,
            'pfx:baz' => null,
        ], 1);
        $cache->putMultiple(['foo' => 'bar', 'bar' => 'foo', 'baz' => null]);

        $repo->shouldReceive('get')->once()->with('_pfx:cached-items')->andReturnNull();
        $repo->shouldReceive('get')->once()->with('pfx:foo')->andReturnNull();
        $repo->shouldReceive('put')->once()->with('pfx:foo', 'bar', 1);
        $repo->shouldReceive('put')->once()->with('_pfx:cached-items', ['pfx:foo' => null], 1);
        $this->assertEquals('bar', $cache->remember('foo', function () {
            return 'bar';
        }));

        $repo->shouldReceive('get')->once()->with('_pfx:cached-items')->andReturn(['pfx:qux' => null]);
        $repo->shouldReceive('get')->once()->with('pfx:qux')->andReturn('baz');
        $this->assertEquals('baz', $cache->remember('qux', function () {
            return 'baz';
        }));

        $repo->shouldReceive('get')->once()->with('_pfx:cached-items')->andReturn(['pfx:foo' => null]);
        $repo->shouldReceive('get')->once()->with('pfx:foo')->andReturnNull();
        $repo->shouldReceive('put')->once()->with('pfx:foo', 'bar', 1);
        $this->assertEquals('bar', $cache->remember('foo', function () {
            return 'bar';
        }));

        $repo->shouldReceive('get')->once()->with('_pfx:cached-items')->andReturnNull();
        $repo->shouldReceive('get')->once()->with('pfx:foo')->andReturn('fizz');
        $repo->shouldReceive('put')->once()->with('pfx:foo', 'bar', 1);
        $repo->shouldReceive('put')->once()->with('_pfx:cached-items', ['pfx:foo' => null], 1);
        $this->assertEquals('bar', $cache->remember('foo', function () {
            return 'bar';
        }));

        $repo->shouldReceive('get')->once()->with('_pfx:cached-items')->andReturnNull();
        $repo->shouldReceive('getMultiple')->once()->with(['pfx:foo', 'pfx:bar', 'pfx:baz'])->andReturn([
            'pfx:foo' => 1,
            'pfx:bar' => null,
            'pfx:baz' => null,
        ]);
        $repo->shouldReceive('has')->once()->with('pfx:bar')->andReturnTrue();
        $repo->shouldReceive('has')->once()->with('pfx:baz')->andReturnFalse();
        $repo->shouldReceive('forget')->once()->with('pfx:foo');
        $repo->shouldReceive('forget')->once()->with('pfx:bar');
        $this->assertEquals([], $cache->getMultiple(['foo', 'bar', 'baz']));

        $repo->shouldReceive('get')->once()->with('_pfx:cached-items')->andReturn(['pfx:foo' => null]);
        $repo->shouldReceive('getMultiple')->once()->with(['pfx:foo', 'pfx:bar', 'pfx:baz'])->andReturn([
            'pfx:foo' => 1,
            'pfx:bar' => null,
            'pfx:baz' => null,
            'pfx:qux' => 2,
        ]);
        $repo->shouldReceive('has')->once()->with('pfx:bar')->andReturnTrue();
        $repo->shouldReceive('has')->once()->with('pfx:baz')->andReturnFalse();
        $repo->shouldReceive('forget')->once()->with('pfx:bar');
        $repo->shouldReceive('forget')->once()->with('pfx:qux');
        $this->assertEquals(['foo' => 1], $cache->getMultiple(['foo', 'bar', 'baz']));
    }

    public function testItemCanBeRemoved()
    {
        $repo = $this->getRepo();
        $cache = $this->getCache($repo);

        $repo->shouldReceive('get')->once()->with('_pfx:cached-items')->andReturnNull();
        $repo->shouldReceive('forget')->once()->with('pfx:foo');
        $repo->shouldReceive('put')->once()->with('_pfx:cached-items', [], 1)->andReturnNull();
        $cache->forget('foo');

        $repo->shouldReceive('get')->once()->with('_pfx:cached-items')->andReturn([
            'pfx:foo' => null,
            'pfx:bar' => null,
            'pfx:baz' => null,
        ]);
        $repo->shouldReceive('forget')->once()->with('pfx:bar');
        $repo->shouldReceive('put')->once()->with('_pfx:cached-items', [
            'pfx:foo' => null,
            'pfx:baz' => null,
        ], 1)->andReturnNull();
        $cache->forget('bar');

        $repo->shouldReceive('get')->once()->with('_pfx:cached-items')->andReturnNull();
        $repo->shouldReceive('forget')->once()->with('pfx:foo.bar.baz');
        $repo->shouldReceive('forget')->once()->with('pfx:foo.bar');
        $repo->shouldReceive('forget')->once()->with('pfx:foo');
        $repo->shouldReceive('put')->once()->with('_pfx:cached-items', [], 1)->andReturnNull();
        $cache->forget('foo.bar.baz');

        $repo->shouldReceive('get')->once()->with('_pfx:cached-items')->andReturnNull();
        $repo->shouldReceive('forget')->once()->with('pfx:foo.bar');
        $repo->shouldReceive('forget')->once()->with('pfx:foo');
        $repo->shouldReceive('forget')->once()->with('pfx:baz');
        $repo->shouldReceive('put')->once()->with('_pfx:cached-items', [], 1)->andReturnNull();
        $cache->forgetMultiple(['foo.bar', 'baz']);
    }

    public function testFlush()
    {
        $repo = $this->getRepo();
        $cache = $this->getCache($repo);

        $repo->shouldReceive('get')->once()->with('_pfx:cached-items')->andReturnNull();
        $repo->shouldReceive('deleteMultiple')->once()->with([]);
        $repo->shouldReceive('forget')->once()->with('_pfx:cached-items');
        $cache->flush();

        $repo->shouldReceive('get')->once()->with('_pfx:cached-items')->andReturn([
            'pfx:foo' => null,
            'pfx:bar' => null,
            'pfx:baz' => null,
        ]);
        $repo->shouldReceive('deleteMultiple')->once()->with(['pfx:foo', 'pfx:bar', 'pfx:baz']);
        $repo->shouldReceive('forget')->once()->with('_pfx:cached-items');
        $cache->flush();
    }

    protected function getRepo()
    {
        return m::mock('Illuminate\Contracts\Cache\Repository');
    }

    protected function getCache($repo)
    {
        $cache = new Cache(1, 'pfx:');
        $cache->setCacheRepository($repo);

        return $cache;
    }
}