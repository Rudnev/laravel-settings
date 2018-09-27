<?php

namespace Rudnev\Settings\Tests\Unit\Stores;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Rudnev\Settings\Scopes\Scope;
use Rudnev\Settings\Scopes\EntityScope;
use Rudnev\Settings\Stores\DatabaseStore;

class DatabaseStoreTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testNameCanBeSetAndRetrieved()
    {
        $store = $this->getStore();
        $store->setName('foo-bar');
        $this->assertEquals('foo-bar', $store->getName());
    }

    public function testScopeCanBeSetAndRetrieved()
    {
        $store = $this->getStore();
        $scope = new Scope('foo');
        $store->setScope($scope);
        $this->assertEquals(spl_object_id($scope), spl_object_id($store->getScope()));
    }

    public function testTrueIsReturnedWhenItemExists()
    {
        $store = $this->getStore();
        $table = m::mock('stdClass');

        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('scope', 'default')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'foo')->andReturn($table);
        $table->shouldReceive('whereNotNull')->once()->with('value')->andReturn($table);
        $table->shouldReceive('exists')->once()->andReturn(true);
        $this->assertTrue($store->has('foo'));

        // Dot syntax

        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('scope', 'default')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'products')->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn((object) [
            'key' => 'products',
            'value' => json_encode(['desk' => ['price' => 200]]),
        ]);
        $this->assertTrue($store->has('products.desk.price'));
    }

    public function testFalseIsReturnedWhenItemNotExists()
    {
        $store = $this->getStore();
        $table = m::mock('stdClass');

        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('scope', 'default')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'buz')->andReturn($table);
        $table->shouldReceive('whereNotNull')->andReturn($table);
        $table->shouldReceive('exists')->once()->andReturn(false);
        $this->assertFalse($store->has('buz'));

        // Dot syntax

        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('scope', 'default')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'products')->andReturn($table);
        $table->shouldReceive('first')->once()->andReturnNull();
        $this->assertFalse($store->has('products.desk.price'));
    }

    public function testNullIsReturnedWhenItemNotFound()
    {
        $store = $this->getStore();

        $table = m::mock('stdClass');
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('scope', 'default')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'foo')->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn(null);
        $this->assertNull($store->get('foo'));

        // Dot syntax

        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('scope', 'default')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'products')->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn((object) [
            'key' => 'products',
            'value' => json_encode(['desk' => ['price' => 200]]),
        ]);
        $this->assertNull($store->get('products.chair'));
    }

    public function testDecryptedValueIsReturnedWhenItemIsValid()
    {
        $store = $this->getStore();
        $table = m::mock('stdClass');

        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('scope', 'default')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'foo')->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn((object) ['value' => json_encode('bar')]);

        $this->assertEquals('bar', $store->get('foo'));

        // Dot syntax

        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('scope', 'default')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'products')->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn((object) [
            'key' => 'products',
            'value' => json_encode(['desk' => ['price' => 200]]),
        ]);
        $this->assertEquals(200, $store->get('products.desk.price'));
    }

    public function testMultipleItemsCanBeRetrieved()
    {
        $store = $this->getStore();
        $table = m::mock('stdClass');

        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('scope', 'default')->andReturn($table);
        $table->shouldReceive('whereIn')->once()->with('key', ['foo', 'fizz', 'quz', 'norf'])->andReturn($table);
        $table->shouldReceive('get')->once()->andReturn(collect([
            (object) ['key' => 'foo', 'value' => json_encode('bar')],
            (object) ['key' => 'fizz', 'value' => json_encode('buz')],
            (object) ['key' => 'quz', 'value' => json_encode('baz')],
        ]));
        $this->assertEquals([
            'foo' => 'bar',
            'fizz' => 'buz',
            'quz' => 'baz',
            'norf' => null,
        ], $store->getMultiple(['foo', 'fizz', 'quz', 'norf']));
        $this->assertEquals([], $store->getMultiple([]));

        // Dot syntax

        $store->getConnection()->shouldReceive('table')->times(3)->with('table')->andReturn($table);
        $table->shouldReceive('where')->times(3)->with('scope', 'default')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'foo')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'qux')->andReturn($table);
        $table->shouldReceive('whereIn')->once()->with('key', [2 => 'fizz', 3 => 'norf'])->andReturn($table);
        $table->shouldReceive('first')->twice()->andReturn((object) [
            'key' => 'foo',
            'value' => json_encode(['bar' => 'baz']),
        ]);
        $table->shouldReceive('get')->once()->andReturn(collect([
            (object) ['key' => 'fizz', 'value' => json_encode('buz')],
        ]));
        $this->assertEquals([
            'foo.bar' => 'baz',
            'qux.pax' => null,
            'fizz' => 'buz',
            'norf' => null,
        ], $store->getMultiple(['foo.bar', 'qux.pax', 'fizz', 'norf']));
    }

    public function testAllItemsCanBeRetrieved()
    {
        $store = $this->getStore();
        $table = m::mock('stdClass');
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('scope', 'default')->andReturn($table);
        $table->shouldReceive('get')->once()->andReturn(collect([
            (object) ['key' => 'foo', 'value' => json_encode('bar')],
            (object) ['key' => 'fizz', 'value' => json_encode('buz')],
            (object) ['key' => 'quz', 'value' => json_encode('baz')],
        ]));
        $this->assertEquals([
            'foo' => 'bar',
            'fizz' => 'buz',
            'quz' => 'baz',
        ], $store->all());
    }

    public function testValueIsInsertedOrUpdated()
    {
        $store = $this->getStore();
        $table = m::mock('stdClass');

        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('scope', 'default')->andReturn($table);
        $table->shouldReceive('updateOrInsert')->once()->with(['key' => 'foo'], [
            'scope' => 'default',
            'value' => json_encode('bar'),
        ]);
        $store->set('foo', 'bar');

        // Dot syntax:

        $store->getConnection()->shouldReceive('table')->twice()->with('table')->andReturn($table);
        $table->shouldReceive('where')->twice()->with('scope', 'default')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'products')->andReturn($table);
        $table->shouldReceive('first')->once()->andReturnNull();
        $table->shouldReceive('updateOrInsert')->once()->with(['key' => 'products'], [
            'value' => json_encode(['desk' => ['price' => 200]]),
            'scope' => 'default',
        ]);
        $store->set('products.desk.price', 200);

        $store->getConnection()->shouldReceive('table')->twice()->with('table')->andReturn($table);
        $table->shouldReceive('where')->twice()->with('scope', 'default')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'products')->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn((object) [
            'key' => 'products',
            'value' => json_encode(['desk' => ['price' => 200]]),
        ]);
        $table->shouldReceive('updateOrInsert')->once()->with(['key' => 'products'], [
            'scope' => 'default',
            'value' => json_encode([
                'desk' => [
                    'price' => 200,
                    'height' => 120,
                ],
            ]),
        ]);
        $store->set('products.desk.height', 120);

        $store->getConnection()->shouldReceive('table')->twice()->with('table')->andReturn($table);
        $table->shouldReceive('where')->twice()->with('scope', 'default')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'products')->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn((object) [
            'key' => 'products',
            'value' => json_encode(['desk' => ['price' => 200, 'height' => 120]]),
        ]);
        $table->shouldReceive('updateOrInsert')->once()->with(['key' => 'products'], [
            'value' => json_encode(['desk' => ['price' => 300]]),
            'scope' => 'default',
        ]);
        $store->set('products.desk', ['price' => 300]);
    }

    public function testMultipleValuesAreInsertedOrUpdated()
    {
        $store = $this->getStore();
        $table = m::mock('stdClass');
        $store->getConnection()->shouldReceive('table')->twice()->with('table')->andReturn($table);
        $table->shouldReceive('where')->twice()->with('scope', 'default')->andReturn($table);
        $table->shouldReceive('updateOrInsert')->once()->with(['key' => 'foo'], [
            'value' => json_encode('bar'),
            'scope' => 'default',
        ]);
        $table->shouldReceive('updateOrInsert')->once()->with(['key' => 'qux'], [
            'value' => json_encode('baz'),
            'scope' => 'default',
        ]);

        $store->setMultiple(['foo' => 'bar', 'qux' => 'baz']);
    }

    public function testItemsCanBeRemoved()
    {
        $store = $this->getStore();
        $table = m::mock('stdClass');
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('scope', 'default')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'foo')->andReturn($table);
        $table->shouldReceive('delete')->once();
        $store->forget('foo');

        $store = $this->getStore();
        $table = m::mock('stdClass');
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('scope', 'default')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'foo')->andReturn($table);
        $table->shouldReceive('first')->once()->andReturnNull();
        $table->shouldNotReceive('updateOrInsert');
        $table->shouldNotReceive('delete');
        $store->forget('foo.bar');

        $store = $this->getStore();
        $table = m::mock('stdClass');
        $store->getConnection()->shouldReceive('table')->times(4)->with('table')->andReturn($table);
        $table->shouldReceive('where')->times(4)->with('scope', 'default')->andReturn($table);
        $table->shouldReceive('where')->twice()->with('key', '=', 'foo')->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn((object) ['key' => 'foo', 'value' => json_encode('fish')]);
        $table->shouldReceive('updateOrInsert')->with(['key' => 'foo'], [
            'value' => json_encode('fish'),
            'scope' => 'default',
        ]);
        $table->shouldNotReceive('delete');
        $store->forget('foo.bar');
        $table->shouldReceive('first')->once()->andReturn((object) [
            'key' => 'foo',
            'value' => json_encode(['bar' => 1, 'baz' => 2]),
        ]);
        $table->shouldReceive('updateOrInsert')->with(['key' => 'foo'], [
            'value' => json_encode(['baz' => 2]),
            'scope' => 'default',
        ]);
        $table->shouldNotReceive('delete');
        $store->forget('foo.bar');
    }

    public function testMultipleItemsCanBeRemoved()
    {
        $store = $this->getStore();
        $table = m::mock('stdClass');
        $store->getConnection()->shouldReceive('table')->twice()->with('table')->andReturn($table);
        $table->shouldReceive('where')->twice()->with('scope', 'default')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'foo')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'qux')->andReturn($table);
        $table->shouldReceive('delete')->twice();

        $store->forgetMultiple(['foo', 'qux']);
    }

    //
    public function testItemsCanBeFlushed()
    {
        $store = $this->getStore();
        $table = m::mock('stdClass');
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('scope', 'default')->andReturn($table);
        $table->shouldReceive('delete')->once()->andReturn(2);

        $result = $store->flush();
        $this->assertTrue($result);
    }

    public function testScope()
    {
        $store = $this->getStore();
        $table = m::mock('stdClass');
        $this->assertNotEquals(spl_object_id($store), spl_object_id($store = $store->scope(new Scope('foo'))));
        $this->assertEquals('foo', $store->getScope()->hash);
        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('scope', 'foo')->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'bar')->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn((object) [
            'key' => 'bar',
            'value' => json_encode('baz'),
        ]);
        $this->assertEquals('baz', $store->get('bar'));

        $store->getConnection()->shouldReceive('table')->once()->with('table')->andReturn($table);
        $table->shouldReceive('where')->once()->with('scope', 'foo')->andReturn($table);
        $table->shouldReceive('updateOrInsert')->once()->with(['key' => 'qux'], [
            'scope' => 'foo',
            'value' => json_encode('pax'),
        ]);
        $store->set('qux', 'pax');

        $store = $this->getStore();
        $table = m::mock('stdClass');
        $model = m::mock('Illuminate\Database\Eloquent\Model');
        $model->shouldReceive('getKey')->andReturn(123);
        $scope = new EntityScope(get_class($model), $model->getKey());
        $this->assertNotEquals(spl_object_id($store), spl_object_id($store = $store->scope($scope)));
        $store->getConnection()->shouldReceive('table')->once()->with('settings_models')->andReturn($table);
        $table->shouldReceive('where')->once()->with('model_id', '123')->andReturn($table);
        $table->shouldReceive('where')->once()->with('model_type', get_class($model))->andReturn($table);
        $table->shouldReceive('where')->once()->with('key', '=', 'bar')->andReturn($table);
        $table->shouldReceive('first')->once()->andReturn((object) [
            'key' => 'bar',
            'value' => json_encode('baz'),
        ]);
        $this->assertEquals('baz', $store->get('bar'));

        $store->getConnection()->shouldReceive('table')->once()->with('settings_models')->andReturn($table);
        $table->shouldReceive('where')->once()->with('model_id', 123)->andReturn($table);
        $table->shouldReceive('where')->once()->with('model_type', get_class($model))->andReturn($table);
        $table->shouldReceive('updateOrInsert')->once()->with(['key' => 'qux'], [
            'model_id' => 123,
            'model_type' => get_class($model),
            'value' => json_encode('pax'),
        ]);
        $store->set('qux', 'pax');
    }

    protected function getStore()
    {
        return new DatabaseStore(m::mock('Illuminate\Database\Connection'), [
            'settings' => [
                'table' => 'table',
                'scope' => 'scope',
                'key' => 'key',
                'value' => 'value',
            ],
            'settings_models' => [
                'table' => 'settings_models',
                'entity' => 'model',
                'key' => 'key',
                'value' => 'value',
            ],
        ]);
    }
}
