<?php

namespace Rudnev\Settings;

use ArrayAccess;
use Rudnev\Settings\Cache\Cache;
use Rudnev\Settings\Events\StoreEvent;
use Illuminate\Support\Traits\Macroable;
use Rudnev\Settings\Events\PropertyMissed;
use Rudnev\Settings\Events\PropertyRemoved;
use Rudnev\Settings\Events\PropertyWritten;
use Rudnev\Settings\Contracts\StoreContract;
use Rudnev\Settings\Events\PropertyReceived;
use Rudnev\Settings\Events\AllSettingsRemoved;
use Rudnev\Settings\Events\AllSettingsReceived;
use Rudnev\Settings\Contracts\RepositoryContract;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;

/**
 * @mixin \Rudnev\Settings\Contracts\StoreContract
 */
class Repository implements ArrayAccess, RepositoryContract
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The settings store instance.
     *
     * @var \Rudnev\Settings\Contracts\StoreContract
     */
    protected $store;

    /**
     * The cache instance.
     *
     * @var \Rudnev\Settings\Cache\Cache
     */
    protected $cache;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The scope.
     *
     * @var mixed
     */
    protected $scope = '';

    /**
     * Default settings.
     *
     * @var array
     */
    protected $default = [];

    /**
     * Create a new settings repository instance.
     *
     * @param  \Rudnev\Settings\Contracts\StoreContract $store
     * @return void
     */
    public function __construct(StoreContract $store)
    {
        $this->store = $store;
    }

    /**
     * Get the settings store instance.
     *
     * @return \Rudnev\Settings\Contracts\StoreContract
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Set the settings store implementation.
     *
     * @param \Rudnev\Settings\Contracts\StoreContract $store
     * @return void
     */
    public function setStore(StoreContract $store)
    {
        $this->store = $store->scope($this->scope);
    }

    /**
     * Get the cache instance.
     *
     * @return \Rudnev\Settings\Cache\Cache
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * Set the cache instance.
     *
     * @param \Rudnev\Settings\Cache\Cache $cache
     * @return void
     */
    public function setCache(Cache $cache)
    {
        $cache->load(function () {
            return $this->store->all();
        });

        $this->cache = $cache;
    }

    /**
     * Get the event dispatcher instance.
     *
     * @return  \Illuminate\Contracts\Events\Dispatcher $dispatcher
     */
    public function getEventDispatcher()
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher implementation.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher $dispatcher
     * @return void
     */
    public function setEventDispatcher(DispatcherContract $dispatcher)
    {
        $this->events = $dispatcher;
    }

    /**
     * Get the scope.
     *
     * @return mixed
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Set the scope.
     *
     * @param mixed
     * @return void
     */
    public function setScope($scope)
    {
        if (! is_object($scope)) {
            $scope = (string) $scope;
        }

        $this->scope = $scope;

        if (isset($this->store)) {
            $this->store = $this->store->scope($scope);
        }

        $this->cache = null;
    }

    /**
     * Get the default value.
     *
     * @param string $key
     * @return array
     */
    public function getDefault($key = null)
    {
        if (isset($key)) {
            return value($this->default[$key] ?? null);
        }

        return $this->default;
    }

    /**
     * Set the default value.
     *
     * @param array|string $key
     * @param mixed $value
     * @return void
     */
    public function setDefault($key, $value = null)
    {
        if (is_array($key)) {
            $this->default = array_merge($this->default, $key);

            return;
        }

        $this->default[$key] = $value;
    }

    /**
     * Remove the default value.
     *
     * @param array|string $key
     * @return void
     */
    public function forgetDefault($key = null)
    {
        if (is_null($key)) {
            $this->default = [];

            return;
        }

        if (is_array($key)) {
            foreach ($key as $item) {
                unset($this->default[$item]);
            }

            return;
        }

        unset($this->default[$key]);
    }

    /**
     * Determine if an item exists in the settings store.
     *
     * @param  string $key
     * @return bool
     */
    public function has($key)
    {
        return $this->data()->has($key);
    }

    /**
     * Retrieve an item from the settings store by key.
     *
     * @param  string|iterable $key
     * @param  mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (is_iterable($key)) {
            return $this->getMultiple($key);
        }

        $value = $this->data()->get($key);

        // If we could not find the settings value, we will fire the missed event and get
        // the default value for this settings value. This default could be a callback
        // so we will execute the value function which will resolve it if needed.
        if (is_null($value)) {
            $this->event(new PropertyMissed($key));

            $value = isset($default) ? value($default) : $this->getDefault($key);
        } else {
            $this->event(new PropertyReceived($key, $value));
        }

        return $value;
    }

    /**
     * Retrieve multiple items from the settings store by key.
     *
     * Items not found in the settings store will have a null value.
     *
     * @param  iterable $keys
     * @return array
     */
    protected function getMultiple(iterable $keys)
    {
        $keyList = collect($keys)->map(function ($value, $key) {
            return is_string($key) ? $key : $value;
        })->values()->all();

        $values = $this->data()->getMultiple($keyList);

        return collect($values)->map(function ($value, $key) use ($keys) {
            if (is_null($value)) {
                $this->event(new PropertyMissed($key));

                return isset($keys[$key]) ? value($keys[$key]) : $this->getDefault($key);
            }

            $this->event(new PropertyReceived($key, $value));

            return $value;
        })->all();
    }

    /**
     * Get all of the settings items.
     *
     * @return array
     */
    public function all()
    {
        $data = $this->data()->all();

        $this->event(new AllSettingsReceived());

        return $data;
    }

    /**
     * Store an item in the settings store.
     *
     * @param  string|iterable $key
     * @param  mixed|null $value
     * @return $this
     */
    public function set($key, $value = null)
    {
        if (is_iterable($key)) {
            return $this->setMultiple($key);
        }

        $this->store->set($key, $value);

        $this->event(new PropertyWritten($key, $value));

        return $this;
    }

    /**
     * Store multiple items in the settings store.
     *
     * @param  iterable $values
     * @return $this
     */
    protected function setMultiple(iterable $values)
    {
        $this->store->setMultiple($values);

        foreach ($values as $key => $value) {
            $this->event(new PropertyWritten($key, $value));
        }

        return $this;
    }

    /**
     * Remove an item from the settings store.
     *
     * @param  string|iterable $key
     * @return bool
     */
    public function forget($key)
    {
        if (is_iterable($key)) {
            return $this->forgetMultiple($key);
        }

        $success = $this->store->forget($key);

        if ($success) {
            $this->event(new PropertyRemoved($key));
        }

        return $success;
    }

    /**
     * Remove multiple items from the settings store.
     *
     * @param  iterable $keys
     * @return bool
     */
    protected function forgetMultiple(iterable $keys)
    {
        $success = $this->store->forgetMultiple($keys);

        if ($success) {
            foreach ($keys as $key) {
                $this->event(new PropertyRemoved($key));
            }
        }

        return $success;
    }

    /**
     * Remove all items from the settings store.
     *
     * @return bool
     */
    public function flush()
    {
        $success = $this->store->flush();

        if ($success) {
            $this->event(new AllSettingsRemoved());
        }

        return $success;
    }

    /**
     * Set the scope.
     *
     * @param mixed $scope
     * @param array $options
     * @return \Rudnev\Settings\Contracts\RepositoryContract
     */
    public function scope($scope, $options = null): RepositoryContract
    {
        $repo = clone $this;

        $repo->setScope($scope);

        $repo->forgetDefault();

        $repo->setDefault($options['default'] ?? []);

        return $repo;
    }

    /**
     * Get the cache if it's enabled, otherwise the settings store.
     *
     * @return \Rudnev\Settings\Contracts\StoreContract
     */
    protected function data()
    {
        return $this->cache ?? $this->store;
    }

    /**
     * Fire an event for this settings instance.
     *
     * @param  object $event
     * @return void
     */
    protected function event($event)
    {
        if (! isset($this->events)) {
            return;
        }

        if ($event instanceof StoreEvent) {
            $event->setStoreName($this->store->getName());
            $event->setScope($this->scope);
        }

        $this->events->dispatch($event);
    }

    /**
     * Determine if a value exists.
     *
     * @param  string $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return $this->has($key);
    }

    /**
     * Retrieve an item from the settings store by key.
     *
     * @param  string $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Store an item in the settings store.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->set($key, $value);
    }

    /**
     * Remove an item from the settings store.
     *
     * @param  string $key
     * @return void
     */
    public function offsetUnset($key)
    {
        $this->forget($key);
    }

    /**
     * Handle dynamic calls into macros or pass missing methods to the store.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->store->$method(...$parameters);
    }

    /**
     * Clone settings repository instance.
     *
     * @return void
     */
    public function __clone()
    {
        $this->store = clone $this->store;

        if (is_object($this->cache)) {
            $this->cache = clone $this->cache;
        }
    }
}
