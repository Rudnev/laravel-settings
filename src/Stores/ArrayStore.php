<?php

declare(strict_types=1);

namespace Rudnev\Settings\Stores;

use Illuminate\Support\Arr;
use Rudnev\Settings\Contracts\StoreContract;
use Rudnev\Settings\Scopes\Scope;

class ArrayStore implements StoreContract
{
    /**
     * The settings store name.
     *
     * @var string
     */
    protected $name = 'array';

    /**
     * The scope.
     *
     * @var \Rudnev\Settings\Scopes\Scope
     */
    protected $scope;

    /**
     * The array of stored values.
     *
     * @var array
     */
    protected $storage = [];

    /**
     * ArrayStore constructor.
     *
     * @return void
     */
    public function __construct()
    {
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
     * @param  string  $name
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
     * @param  \Rudnev\Settings\Scopes\Scope  $scope
     * @return void
     */
    public function setScope(Scope $scope): void
    {
        $this->scope = $scope;
    }

    /**
     * Determine if an item exists in the settings store.
     *
     * @param  string  $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return ! is_null(Arr::get($this->storage, $key));
    }

    /**
     * Retrieve an item from the settings store by key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get(string $key)
    {
        return Arr::get($this->storage, $key);
    }

    /**
     * Retrieve multiple items from the settings store by key.
     *
     * Items not found in the settings store will have a null value.
     *
     * @param  iterable  $keys
     * @return array
     */
    public function getMultiple(iterable $keys): array
    {
        $return = [];

        foreach ($keys as $key) {
            $return[$key] = $this->get($key);
        }

        return $return;
    }

    /**
     * Get all the settings items.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->storage;
    }

    /**
     * Store an item in the settings store.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function set(string $key, $value): void
    {
        Arr::set($this->storage, $key, $value);
    }

    /**
     * Store multiple items in the settings store.
     *
     * @param  iterable  $values
     * @return void
     */
    public function setMultiple(iterable $values): void
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * Remove an item from the settings store.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        Arr::forget($this->storage, $key);

        return true;
    }

    /**
     * Remove multiple items from the settings store.
     *
     * @param  iterable  $keys
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
        $this->storage = [];

        return true;
    }

    /**
     * Set the scope.
     *
     * @param  \Rudnev\Settings\Scopes\Scope  $scope
     * @return \Rudnev\Settings\Contracts\StoreContract
     */
    public function scope(Scope $scope): StoreContract
    {
        $store = clone $this;

        $store->setScope($scope);

        $store->flush();

        return $store;
    }
}
