<?php

namespace Rudnev\Settings\Tests\Unit\Cache;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Rudnev\Settings\Cache\Cache;
use Illuminate\Cache\Repository as CacheRepo;
use Illuminate\Cache\ArrayStore as CacheRepoStore;

class CacheTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testRepositoryCanSetAndRetrieved()
    {
        $repo = m::spy('Illuminate\Contracts\Cache\Repository');
        $repo2 = m::spy('Illuminate\Cache\Repository');
        $cache = new Cache($repo);
        $this->assertEquals($repo, $cache->getRepository());
        $cache->setRepository($repo2);
        $this->assertEquals($repo2, $cache->getRepository());
        $this->assertNotEquals($repo, $repo2);
    }

    public function testDataCanBeLoaded()
    {
        $cache = new Cache(new CacheRepo(new CacheRepoStore()));
        $cache->load(function () {
            return ['foo' => 'bar', 'bar' => 'baz'];
        });
        $this->assertEquals(['foo' => 'bar', 'bar' => 'baz'], $cache->all());
    }

    public function testItemCanBeSet()
    {
        $repo = $this->getRepo();
        $cache = new Cache($repo);
        $repo->shouldReceive('forget');
        $this->assertNull($cache->get('foo'));
        $cache->set('foo', 'bar');
        $this->assertEquals('bar', $cache->get('foo'));
    }

    public function testItemCanBeRemoved()
    {
        $repo = $this->getRepo();
        $cache = new Cache($repo);
        $repo->shouldReceive('forget');
        $cache->set('foo', 'bar');
        $cache->forget('foo');
        $this->assertNull($cache->get('foo'));
    }

    public function testAllItemsCanBeRemoved()
    {
        $repo = $this->getRepo();
        $cache = new Cache($repo);
        $repo->shouldReceive('forget');
        $cache->set('foo', 'bar');
        $cache->set('baz', 'qux');
        $cache->flush();
        $this->assertNull($cache->get('foo'));
        $this->assertNull($cache->get('baz'));
    }

    protected function getRepo()
    {
        return m::spy('Illuminate\Contracts\Cache\Repository');
    }

    protected function getCache($repo)
    {
        $cache = new Cache($repo, 1, 'laravel-settings-test');

        return $cache;
    }
}
