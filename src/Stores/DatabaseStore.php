<?php

declare(strict_types=1);

namespace Rudnev\Settings\Stores;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Rudnev\Settings\Contracts\StoreContract;
use Rudnev\Settings\Scopes\EntityScope;
use Rudnev\Settings\Scopes\Scope;

class DatabaseStore implements StoreContract
{
    /**
     * The settings store name.
     *
     * @var string
     */
    protected $name = 'database';

    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\ConnectionInterface
     */
    protected $connection;

    /**
     * The name of the settings table.
     *
     * @var string
     */
    protected $table;

    /**
     * The name of the "key" column.
     *
     * @var string
     */
    protected $keyColumn;

    /**
     * The name of the "value" column.
     *
     * @var string
     */
    protected $valueColumn;

    /**
     * The name of the scope column.
     *
     * @var string
     */
    protected $scopeColumn;

    /***
     * The name of the table for storing model settings.
     *
     * @var string
     */
    protected $morphTable;

    /***
     * The entity id column.
     *
     * @var string
     */
    protected $morphId;

    /***
     * The entity type column.
     *
     * @var string
     */
    protected $morphType;

    /***
     * The name of the "key" column.
     *
     * @var string
     */
    protected $morphKey;

    /***
     * The name of the "value" column.
     *
     * @var string
     */
    protected $morphValue;

    /**
     * The scope.
     *
     * @var \Rudnev\Settings\Scopes\Scope
     */
    protected $scope;

    /**
     * Create a new database store.
     *
     * @param  \Illuminate\Database\ConnectionInterface $connection
     * @param  array $names
     * @return void
     */
    public function __construct(ConnectionInterface $connection, array $names)
    {
        $this->connection = $connection;

        $this->table = $names['settings']['table'];
        $this->keyColumn = $names['settings']['key'];
        $this->valueColumn = $names['settings']['value'];
        $this->scopeColumn = $names['settings']['scope'];
        $this->morphTable = $names['settings_models']['table'];
        $this->morphId = $names['settings_models']['entity'].'_id';
        $this->morphType = $names['settings_models']['entity'].'_type';
        $this->morphKey = $names['settings_models']['key'];
        $this->morphValue = $names['settings_models']['value'];

        $this->scope = new Scope();
    }

    /**
     * Get the settings store name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the settings store name.
     *
     * @param string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the scope.
     *
     * @return \Rudnev\Settings\Scopes\Scope
     */
    public function getScope(): Scope
    {
        return $this->scope;
    }

    /**
     * Set the scope.
     *
     * @param \Rudnev\Settings\Scopes\Scope $scope
     * @return void
     */
    public function setScope(Scope $scope): void
    {
        if ($scope instanceof EntityScope) {
            $this->table = $this->morphTable;
            $this->keyColumn = $this->morphKey;
            $this->valueColumn = $this->morphValue;
        }

        $this->scope = $scope;
    }

    /**
     * Determine if an item exists in the settings store.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        $keys = explode('.', $key);

        if (count($keys) > 1) {
            return ! is_null($this->get($key));
        }

        return $this->table()->where($this->keyColumn, '=', $key)->whereNotNull($this->valueColumn)->exists();
    }

    /**
     * Retrieve an item from the settings store by key.
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        $keys = explode('.', $key);

        if (count($keys) > 1) {
            $root = $keys[0];

            $data = [$root => $this->get($root)];

            return Arr::get($data, $key);
        }

        $item = $this->table()->where($this->keyColumn, '=', $key)->first();

        if (is_null($item)) {
            return;
        }

        $item = is_array($item) ? (object) $item : $item;

        return $this->unpack($item->{$this->valueColumn});
    }

    /**
     * Retrieve multiple items from the settings store by key.
     *
     * Items not found in the settings store will have a null value.
     *
     * @param iterable $keys
     * @return array
     */
    public function getMultiple(iterable $keys): array
    {
        $return = [];
        $data = [];

        foreach ($keys as $i => $key) {
            $subkeys = explode('.', $key);

            if (count($subkeys) > 1) {
                $root = $subkeys[0];

                if (! isset($data[$root])) {
                    $data[$root] = $this->get($root);
                }

                $return[$key] = Arr::get([$root => $data[$root]], $key);

                unset($keys[$i]);
            }
        }

        if (count($keys) === 0) {
            return $return;
        }

        $result = $this->table()->whereIn($this->keyColumn, $keys)->get();

        while ($item = $result->shift()) {
            $return[$item->{$this->keyColumn}] = $this->unpack($item->{$this->valueColumn});
        }

        foreach ($keys as $key) {
            if (! isset($return[$key])) {
                $return[$key] = null;
            }
        }

        return $return;
    }

    /**
     * Get all of the settings items.
     *
     * @return array
     */
    public function all(): array
    {
        $return = [];

        $result = $this->table()->get();

        while ($item = $result->shift()) {
            $return[$item->{$this->keyColumn}] = $this->unpack($item->{$this->valueColumn});
        }

        return $return;
    }

    /**
     * Store an item in the settings store.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function set(string $key, $value): void
    {
        [$key, $value] = $this->prepareIfNested($key, $value);

        $value = $this->pack($value);

        $values = [$this->valueColumn => $value];

        if ($this->scope instanceof EntityScope) {
            $values[$this->morphId] = $this->scope->entityId;
            $values[$this->morphType] = $this->scope->entityClass;
        } else {
            $values[$this->scopeColumn] = $this->scope->hash;
        }

        $this->table()->updateOrInsert([$this->keyColumn => $key], $values);
    }

    /**
     * Store multiple items in the settings store.
     *
     * @param  iterable $values
     * @return void
     */
    public function setMultiple(iterable $values): void
    {
        foreach ($values as $key => $value) {
            [$key, $value] = $this->prepareIfNested($key, $value);
            $this->set($key, $value);
        }
    }

    /**
     * Prepare the item for setting it to the store,
     * if the key is a chain like a foo.bar.baz.
     *
     * @param string $key
     * @param mixed $value
     * @return array
     */
    protected function prepareIfNested(string $key, $value): array
    {
        $keys = explode('.', $key);

        if (count($keys) > 1) {
            $root = $keys[0];

            $data = [$root => $this->get($root)];

            Arr::set($data, $key, $value);

            return [$root, $data[$root]];
        }

        return [$key, $value];
    }

    /**
     * Remove an item from the settings store.
     *
     * @param  string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        $keys = explode('.', $key);

        if (count($keys) > 1) {
            $root = $keys[0];

            $data = [$root => $this->get($root)];

            if (! isset($data[$root])) {
                return false;
            }

            Arr::forget($data, [$key]);

            $this->set($root, $data[$root]);

            return true;
        }

        $this->table()->where($this->keyColumn, '=', $key)->delete();

        return true;
    }

    /**
     * Remove multiple items from the settings store.
     *
     * @param  iterable $keys
     * @return bool
     */
    public function forgetMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->forget($key);
        }

        return true;
    }

    /**
     * Remove all items from the settings store.
     *
     * @return bool
     */
    public function flush(): bool
    {
        return (bool) $this->table()->delete();
    }

    /**
     * Set the scope.
     *
     * @param \Rudnev\Settings\Scopes\Scope $scope
     * @return \Rudnev\Settings\Contracts\StoreContract
     */
    public function scope(Scope $scope): StoreContract
    {
        $store = clone $this;

        $store->setScope($scope);

        return $store;
    }

    /**
     * Get a query builder for the settings table.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function table()
    {
        $query = $this->connection->table($this->table);

        if ($this->scope instanceof EntityScope) {
            $query->where($this->morphId, $this->scope->entityId)->where($this->morphType, $this->scope->entityClass);
        } else {
            $query->where($this->scopeColumn, $this->scope->hash);
        }

        return $query;
    }

    /**
     * Get the underlying database connection.
     *
     * @return \Illuminate\Database\ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Pack the value before write to the database.
     *
     * @param $value
     * @return string
     */
    protected function pack($value)
    {
        return json_encode($value);
    }

    /**
     * Unpack the value after retrieving then from the database.
     *
     * @param $value
     * @return string
     */
    protected function unpack($value)
    {
        return json_decode($value, true);
    }
}
