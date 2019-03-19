<?php

namespace Rudnev\Settings\Tests\Unit\Cache\L1;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Rudnev\Settings\Cache\L1\FirstLevelRegion;

class FirstLevelRegionTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testNameCanBeSetAndRetrieved()
    {
        $region = new FirstLevelRegion('foo');
        $this->assertEquals('foo', $region->getName());

        $region->setName('bar');
        $this->assertEquals('bar', $region->getName());
    }

    public function testExistenceChecking()
    {
        $region = new FirstLevelRegion('foo');
        $this->assertFalse($region->has('foo'));

        $region->put('foo', false);
        $this->assertTrue($region->has('foo'));
    }

    public function testItemCanBePutAndRetrieved()
    {
        $region = new FirstLevelRegion('foo');
        $this->assertNull($region->get('foo'));

        $region->put('bar', 'baz');
        $this->assertEquals('baz', $region->get('bar'));

        $this->assertEquals(123, $region->get('baz', function () {
            return 123;
        }));
        $this->assertEquals(123, $region->get('baz'));
    }

    public function testMultipleItemsCanBePutAndRetrieved()
    {
        $region = new FirstLevelRegion('foo');

        $region->putMultiple(['foo' => 123, 'bar' => 321]);
        $this->assertEquals(123, $region->get('foo'));
        $this->assertEquals(321, $region->get('bar'));
        $this->assertEquals(['foo' => 123, 'bar' => 321, 'baz' => null], $region->getMultiple(['foo', 'bar', 'baz']));
        $this->assertEquals(['foo' => 123, 'bar' => 321, 'baz' => 132], $region->getMultiple([
            'foo',
            'bar',
            'baz',
        ], function ($keys) {
            $data = [
                'baz' => 132,
            ];

            return array_only($data, $keys);
        }));
        $this->assertEquals(['foo' => 123, 'bar' => 321, 'baz' => 132], $region->getMultiple(['foo', 'bar', 'baz']));
    }

    public function testAllItemsCanBeRetrieved()
    {
        $region = new FirstLevelRegion('foo');
        $region->putMultiple(['foo' => 123, 'bar' => 321]);
        $this->assertEquals(['foo' => 123, 'bar' => 321], $region->all());
        $this->assertEquals(['foo' => 123, 'bar' => 321, 'baz' => 132], $region->all(function () {
            return [
                'foo' => 123,
                'bar' => 321,
                'baz' => 132,
            ];
        }));

        $region = new FirstLevelRegion('foo');
        $this->assertEquals(['not-in-the-cache' => 123], $region->all(function () {
            return [
                'not-in-the-cache' => 123,
            ];
        }));
        $this->assertEquals(['not-in-the-cache' => 123], $region->all());
    }

    public function testItemCanBeRemoved()
    {
        $region = new FirstLevelRegion('foo');

        $region->put('bar', 'baz');
        $region->forget('bar');
        $this->assertNull($region->get('bar'));
    }

    public function testMultipleItemsCanBeRemoved()
    {
        $region = new FirstLevelRegion('foo');
        $region->putMultiple(['foo' => 123, 'bar' => 321, 'baz' => 132]);

        $region->forgetMultiple(['bar', 'baz']);
        $this->assertEquals(['foo' => 123], $region->all());
    }

    public function testAllItemsCanBeRemoved()
    {
        $region = new FirstLevelRegion('foo');

        $region->put('bar', 'baz');
        $region->put('baz', 'qux');
        $region->flush();
        $this->assertNull($region->get('bar'));
        $this->assertNull($region->get('baz'));
    }
}
