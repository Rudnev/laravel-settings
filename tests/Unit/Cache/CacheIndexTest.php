<?php

namespace Rudnev\Settings\Tests\Unit\Cache;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Rudnev\Settings\Cache\CacheIndex;

class CacheIndexTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testItemCanBeAdded()
    {
        $index = $this->getIndex();
        $index->add('foo');
        $this->assertFalse($index->has('bar'));
        $this->assertFalse($index->has('foo.bar'));
        $this->assertTrue($index->has('foo'));

        $index = $this->getIndex();
        $index->add('foo.bar.baz');
        $this->assertFalse($index->has('foo'));
        $this->assertFalse($index->has('foo.bar'));
        $this->assertTrue($index->has('foo.bar.baz'));

        $index = $this->getIndex();
        $index->add('foo');
        $index->add('foo.bar.baz');
        $this->assertFalse($index->has('foo.bar'));
        $this->assertTrue($index->has('foo.bar.baz'));
        $this->assertTrue($index->has('foo'));
    }

    public function testItemCanBeRemoved()
    {
        $index = $this->getIndex();
        $index->add('foo');
        $index->remove('foo');
        $this->assertFalse($index->has('foo'));

        $index = $this->getIndex();
        $index->add('foo');
        $index->remove('foo.bar.baz');
        $this->assertTrue($index->has('foo'));

        $index = $this->getIndex();
        $index->add('foo.bar');
        $index->remove('foo');
        $this->assertFalse($index->has('foo.bar'));

        $index = $this->getIndex();
        $index->add('foo.bar.baz');
        $index->remove('foo.bar.baz');
        $this->assertFalse($index->has('foo.bar.baz'));
        $this->assertFalse($index->has('foo.bar'));
        $this->assertFalse($index->has('foo'));
    }

    public function testKeysCanBeRetrieved()
    {
        $index = $this->getIndex();
        $index->add('foo');
        $index->add('foo.bar.baz');
        $index->add('buz');
        $this->assertEquals(['foo', 'foo.bar.baz', 'buz'], $index->keys());

        $index = $this->getIndex();
        $index->add('foo.qux');
        $index->add('foo.bar.baz.fizz');
        $index->add('buz');
        $this->assertEquals(['foo.bar.baz.fizz'], $index->childKeys('foo.bar'));
        $this->assertEquals(['foo.qux', 'foo.bar.baz.fizz'], $index->childKeys('foo'));
        $this->assertEquals([], $index->childKeys('buz'));
        $this->assertEquals([], $index->childKeys('fish'));
    }

    public function testIndexCanBeCleared()
    {
        $index = $this->getIndex();
        $index->add('foo');
        $index->add('foo.bar.baz');
        $index->add('buz');
        $index->clear();
        $this->assertFalse($index->has('foo'));
        $this->assertFalse($index->has('foo.bar.baz'));
        $this->assertFalse($index->has('buz'));
    }

    public function testInvalidArguments()
    {
        $index = $this->getIndex();
        $this->assertFalse($index->has(null));
        $this->assertNull($index->add(null));
        $this->assertEquals([], $index->keys());
        $this->assertFalse($index->remove(null));
        $this->assertEquals([], $index->childKeys(null));
    }


    protected function getIndex()
    {
        return new CacheIndex();
    }
}