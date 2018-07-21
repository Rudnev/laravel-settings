<?php

namespace Rudnev\Settings\Contracts;

interface CacheContract
{
    /**
     * Determine if an item exists in the cache store.
     *
     * @param  string $key
     * @return bool
     */
    public function has($key);

    /**
     * Retrieve an item from the cache store by key.
     *
     * @param  string $key
     * @return mixed
     */
    public function get($key);

    /**
     * Retrieve multiple items from the cache store by key.
     *
     * Items not found in the cache store will have a null value.
     *
     * @param  iterable $keys
     * @return array
     */
    public function getMultiple(iterable $keys);

    /**
     * Get all of the cache items.
     *
     * @return array
     */
    public function all();

    /**
     * Store an item in the cache store.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function put($key, $value);

    /**
     * Store multiple items in the cache store.
     *
     * @param  iterable $values
     * @return void
     */
    public function putMultiple(iterable $values);

    /**
     * Remove an item from the cache store.
     *
     * @param  string $key
     * @return bool
     */
    public function forget($key);

    /**
     * Remove multiple items from the cache store.
     *
     * @param  iterable $keys
     * @return bool
     */
    public function forgetMultiple(iterable $keys);

    /**
     * Remove all items from the cache store.
     *
     * @return bool
     */
    public function flush();
}