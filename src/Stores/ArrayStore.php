<?php

namespace Rudnev\Settings\Stores;

use Illuminate\Support\Arr;
use Rudnev\Settings\Contracts\StoreContract;

class ArrayStore implements StoreContract
{
    /**
     * The settings store name
     *
     * @var string
     */
    protected $name;

    /**
     * The array of stored values.
     *
     * @var array
     */
    protected $storage = [];

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @inheritDoc
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @inheritDoc
     */
    public function has($key)
    {
        return Arr::has($this->storage, $key);
    }

    /**
     * @inheritDoc
     */
    public function get($key)
    {
        return Arr::get($this->storage, $key);
    }

    /**
     * @inheritDoc
     */
    public function getMultiple(iterable $keys)
    {
        $return = [];

        foreach ($keys as $key) {
            $return[$key] = $this->get($key);
        }

        return $return;
    }

    /**
     * @inheritDoc
     */
    public function all()
    {
        return $this->storage;
    }

    /**
     * @inheritDoc
     */
    public function set($key, $value)
    {
        Arr::set($this->storage, $key, $value);
    }

    /**
     * @inheritDoc
     */
    public function setMultiple(iterable $values)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * @inheritDoc
     */
    public function forget($key)
    {
        Arr::forget($this->storage, $key);

        return true;
    }

    /**
     * @inheritDoc
     */
    public function forgetMultiple(iterable $keys)
    {
        foreach ($keys as $key) {
            $this->forget($key);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function flush()
    {
        $this->storage = [];

        return true;
    }
}