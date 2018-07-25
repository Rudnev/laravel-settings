<?php

namespace Rudnev\Settings\Contracts;

interface StoreContract
{
    /**
     * Get the settings store name.
     *
     * @return string
     */
    public function getName();

    /**
     * Set the settings store name.
     *
     * @param $name
     * @return mixed
     */
    public function setName($name);

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
     * @return mixed
     */
    public function get($key);

    /**
     * Retrieve multiple items from the settings store by key.
     *
     * Items not found in the settings store will have a null value.
     *
     * @param  iterable $keys
     * @return array
     */
    public function getMultiple(iterable $keys);

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
     * Store multiple items in the settings store.
     *
     * @param  iterable $values
     * @return void
     */
    public function setMultiple(iterable $values);

    /**
     * Remove an item from the settings store.
     *
     * @param  string $key
     * @return bool
     */
    public function forget($key);

    /**
     * Remove multiple items from the settings store.
     *
     * @param  iterable $keys
     * @return bool
     */
    public function forgetMultiple(iterable $keys);

    /**
     * Remove all items from the settings store.
     *
     * @return bool
     */
    public function flush();

    /**
     * Set the scope.
     *
     * @param mixed $scope
     * @return \Rudnev\Settings\Contracts\StoreContract
     */
    public function scope($scope): StoreContract;
}