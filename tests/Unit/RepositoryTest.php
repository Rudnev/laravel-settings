<?php

namespace Rudnev\Settings\Tests\Unit;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Rudnev\Settings\Cache;

class RepositoryTest extends TestCase
{
    protected function tearDown()
    {
        m::close();
    }

    public function testGetReturnsValue()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->once()->with('foo')->andReturn('bar');
        $this->assertEquals('bar', $repo->get('foo'));
    }

    public function testDefaultValueIsReturned()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->times(2)->andReturn(null);
        $this->assertEquals('bar', $repo->get('foo', 'bar'));
        $this->assertEquals('baz', $repo->get('boom', function () {
            return 'baz';
        }));
    }

    public function testGetReturnsMultipleValuesWhenGivenAnArray()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('getMultiple')->once()->with(['foo', 'bar', 'fuz'])->andReturn(['foo' => 'bar', 'bar' => 'baz', 'fuz' => null]);
        $this->assertEquals(['foo' => 'bar', 'bar' => 'baz', 'fuz' => null], $repo->get(['foo', 'bar', 'fuz']));
    }

    public function testGetReturnsMultipleValuesWhenGivenAnArrayWithDefaultValues()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('getMultiple')->once()->with(['foo', 'bar'])->andReturn(['foo' => null, 'bar' => 'baz']);
        $this->assertEquals(['foo' => 'default', 'bar' => 'baz'], $repo->get(['foo' => 'default', 'bar']));
    }

    public function testHasMethod()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('get')->once()->with('foo')->andReturn(null);
        $repo->getStore()->shouldReceive('get')->once()->with('bar')->andReturn('bar');

        $this->assertTrue($repo->has('bar'));
        $this->assertFalse($repo->has('foo'));
    }

    public function testRegisterMacroWithNonStaticCall()
    {
        $repo = $this->getRepository();
        $repo::macro(__CLASS__, function () {
            return 'Taylor';
        });
        $this->assertEquals($repo->{__CLASS__}(), 'Taylor');
    }

    public function testForgettingKey()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('forget')->once()->with('a-key')->andReturn(true);
        $repo->forget('a-key');
    }

    public function testForgettingMultipleKey()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('forgetMultiple')->once()->with(['foo', 'bar'])->andReturn(true);

        $repo->forgetMultiple(['foo', 'bar']);

        $repo->getStore()->shouldReceive('forgetMultiple')->once()->with(['baz', 'qux'])->andReturn(true);

        $repo->forget(['baz', 'qux']);
    }

    public function testSettingValue()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('set')->with($key = 'foo', $value = 'bar');
        $repo->set($key, $value);
    }

    public function testSettingMultipleItems()
    {
        $repo = $this->getRepository();
        $repo->getStore()->shouldReceive('setMultiple')->once()->with(['foo' => 'bar', 'bar' => 'baz']);
        $repo->setMultiple(['foo' => 'bar', 'bar' => 'baz']);
    }

    protected function getRepository()
    {
        $dispatcher = new \Illuminate\Events\Dispatcher(m::mock('Illuminate\Container\Container'));
        $store = m::mock('Rudnev\Settings\Contracts\StoreContract');
        $store->allows(['getName' => 'a-store']);
        $repository = new \Rudnev\Settings\Repository($store, new Cache());
        $repository->setEventDispatcher($dispatcher);

        return $repository;
    }
}