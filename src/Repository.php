<?php

namespace Rudnev\Settings;

use ArrayAccess;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Support\Traits\Macroable;
use Rudnev\Settings\Contracts\RepositoryContract;
use Rudnev\Settings\Contracts\StoreContract;
use Rudnev\Settings\Events\AllSettingsReceived;
use Rudnev\Settings\Events\AllSettingsRemoved;
use Rudnev\Settings\Events\PropertyMissed;
use Rudnev\Settings\Events\PropertyReceived;
use Rudnev\Settings\Events\PropertyRemoved;
use Rudnev\Settings\Events\PropertyWritten;
use Rudnev\Settings\Events\StoreEvent;

/**
 * @mixin \Rudnev\Settings\Contracts\StoreContract
 */
class Repository implements ArrayAccess, RepositoryContract
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * The settings store implementation.
     *
     * @var \Rudnev\Settings\Contracts\StoreContract
     */
    protected $store;

    /**
     * The settings cache instance.
     *
     * @var \Rudnev\Settings\Cache
     */
    protected $cache;

    /**
     * The event dispatcher implementation.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * Create a new settings repository instance.
     *
     * @param  \Rudnev\Settings\Contracts\StoreContract $store
     * @param  \Rudnev\Settings\Cache $cache
     * @return void
     */
    public function __construct(StoreContract $store, Cache $cache)
    {
        $this->store = $store;
        $this->cache = $cache;
    }

    /**
     * Get the settings store implementation.
     *
     * @return \Rudnev\Settings\Contracts\StoreContract
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Determine if an item exists in the settings store.
     *
     * @param  string $key
     * @return bool
     */
    public function has($key)
    {
        return ! is_null($this->get($key));
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

        $get = function () use ($key) {
            return $this->store->get($this->itemKey($key));
        };

        $value = $this->cache->remember($this->itemKey($key), $get);

        // If we could not find the settings value, we will fire the missed event and get
        // the default value for this settings value. This default could be a callback
        // so we will execute the value function which will resolve it if needed.
        if (is_null($value)) {
            $this->event(new PropertyMissed($key));

            $value = value($default);
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

        $cachedValues = $this->cache->getMultiple($keyList);

        $values = $this->store->getMultiple(array_diff($keyList, array_keys($cachedValues)));

        $values = array_merge($cachedValues, $values);

        return collect($values)->map(function ($value, $key) use ($keys, $cachedValues) {
            if (! isset($cachedValues[$key])) {
                $this->cache->put($key, $value);
            }

            if (is_null($value)) {
                $this->event(new PropertyMissed($key));

                return isset($keys[$key]) ? value($keys[$key]) : null;
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
        $data = $this->store->all();

        $this->cache->putMultiple($data);

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

        $this->store->set($this->itemKey($key), $value);

        $this->cache->forget($this->itemKey($key));

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

        $this->cache->forgetMultiple(array_keys($values));

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

        $success = $this->store->forget($this->itemKey($key));

        $this->cache->forget($this->itemKey($key));

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

        $this->cache->forgetMultiple($keys);

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
        $this->cache->flush();

        $success = $this->store->flush();

        if ($success) {
            $this->event(new AllSettingsRemoved());
        }

        return $success;
    }

    /**
     * Format the key for a settings item.
     *
     * @param  string $key
     * @return string
     */
    protected function itemKey($key)
    {
        return $key;
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
        }

        $this->events->dispatch($event);
    }

    /**
     * Set the event dispatcher instance.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher $dispatcher
     * @return void
     */
    public function setEventDispatcher(DispatcherContract $dispatcher)
    {
        $this->events = $dispatcher;
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
    }
}