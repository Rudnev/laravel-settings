<?php

namespace Rudnev\Settings\Contracts;

interface RepositoryContract
{
    /**
     * Determine if an item exists in the settings store.
     *
     * @param  string $key
     * @return bool
     */
    public function has($key);

    /**
     * Retrieve an item from the settings store by key.
     *
     * @param  string $key
     * @param  mixed $default
     * @return mixed
     */
    public function get($key, $default = null);

    /**
     * Get all of the settings items.
     *
     * @return array
     */
    public function all();

    /**
     * Store an item in the settings store.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function set($key, $value);

    /**
     * Remove an item from the settings store.
     *
     * @param  string $key
     * @return bool
     */
    public function forget($key);

    /**
     * Remove all items from the settings store.
     *
     * @return bool
     */
    public function flush();
}