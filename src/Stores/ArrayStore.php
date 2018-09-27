<?php

namespace Rudnev\Settings\Stores;

use Illuminate\Support\Arr;
use Rudnev\Settings\Scopes\Scope;
use Rudnev\Settings\Contracts\StoreContract;

class ArrayStore implements StoreContract
{
    /**
     * The settings store name.
     *
     * @var string
     */
    protected $name;

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
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
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
     * @param mixed $scope
     * @return void
     */
    public function setScope(Scope $scope): void
    {
        $this->scope = $scope;
    }

    /**
     * {@inheritdoc}
     */
    public function has($key)
    {
        return ! is_null(Arr::get($this->storage, $key));
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        return Arr::get($this->storage, $key);
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     */
    public function all()
    {
        return $this->storage;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        Arr::set($this->storage, $key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function setMultiple(iterable $values)
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function forget($key)
    {
        Arr::forget($this->storage, $key);

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function forgetMultiple(iterable $keys)
    {
        foreach ($keys as $key) {
            $this->forget($key);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $this->storage = [];

        return true;
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

        $store->flush();

        return $store;
    }
}
