<?php

declare(strict_types=1);

namespace Rudnev\Settings;

use ArrayAccess;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
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
use Rudnev\Settings\Scopes\EloquentScope;
use Rudnev\Settings\Scopes\Scope;

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
     * The scope.
     *
     * @var \Rudnev\Settings\Scopes\Scope
     */
    protected $scope;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * Default settings.
     *
     * @var array
     */
    protected $default = [];

    /**
     * Create a new settings repository instance.
     *
     * @param  \Rudnev\Settings\Contracts\StoreContract  $store
     * @return void
     */
    public function __construct(StoreContract $store)
    {
        $this->store = $store;
        $this->scope = new Scope();
    }

    /**
     * Get the settings store instance.
     *
     * @return \Rudnev\Settings\Contracts\StoreContract
     */
    public function getStore(): StoreContract
    {
        return $this->store;
    }

    /**
     * Set the settings store implementation.
     *
     * @param  \Rudnev\Settings\Contracts\StoreContract  $store
     * @return void
     */
    public function setStore(StoreContract $store): void
    {
        $this->store = $store->scope($this->scope);
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
     * @param  mixed  $scope
     * @return void
     */
    public function setScope($scope): void
    {
        if ($scope instanceof Model) {
            $this->scope = new EloquentScope($scope);
        } else {
            $this->scope = new Scope((string) $scope);
        }

        $this->store = $this->store->scope($this->scope);
    }

    /**
     * Get the event dispatcher instance.
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public function getEventDispatcher(): DispatcherContract
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher implementation.
     *
     * @param  \Illuminate\Contracts\Events\Dispatcher  $dispatcher
     * @return void
     */
    public function setEventDispatcher(DispatcherContract $dispatcher): void
    {
        $this->events = $dispatcher;
    }

    /**
     * Get the default value.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getDefault(string $key = null)
    {
        if (isset($key)) {
            return value(Arr::get($this->default, $key));
        }

        return $this->default;
    }

    /**
     * Set the default value.
     *
     * @param  array|string  $key
     * @param  mixed  $value
     * @return void
     */
    public function setDefault($key, $value = null): void
    {
        if (is_array($key)) {
            $this->default = array_merge($this->default, $key);
        } else {
            $this->default[$key] = $value;
        }
    }

    /**
     * Remove the default value.
     *
     * @param  array|string  $key
     * @return void
     */
    public function forgetDefault($key = null): void
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
     * @param  string  $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->store->has($key);
    }

    /**
     * Retrieve an item from the settings store by key.
     *
     * @param  string|iterable  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (is_iterable($key)) {
            return $this->getMultiple($key);
        }

        $value = $this->store->get($key);

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
     * @param  iterable  $keys
     * @return array
     */
    protected function getMultiple(iterable $keys): array
    {
        $keyList = collect($keys)->map(function ($value, $key) {
            return is_string($key) ? $key : $value;
        })->values()->all();

        $values = $this->store->getMultiple($keyList);

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
    public function all(): array
    {
        $data = array_replace_recursive($this->default, $this->store->all());

        $this->event(new AllSettingsReceived());

        return $data;
    }

    /**
     * Store an item in the settings store.
     *
     * @param  string|iterable  $key
     * @param  mixed|null  $value
     * @return $this
     */
    public function set($key, $value = null): self
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
     * @param  iterable  $values
     * @return $this
     */
    protected function setMultiple(iterable $values): self
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
     * @param  string|iterable  $key
     * @return bool
     */
    public function forget($key): bool
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
     * @param  iterable  $keys
     * @return bool
     */
    protected function forgetMultiple(iterable $keys): bool
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
    public function flush(): bool
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
     * @param  mixed  $scope
     * @param  array  $options
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
     * Fire an event for this settings instance.
     *
     * @param  object  $event
     * @return void
     */
    protected function event($event): void
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
     * @param  string  $key
     * @return bool
     */
    public function offsetExists($key): bool
    {
        return $this->has($key);
    }

    /**
     * Retrieve an item from the settings store by key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        return $this->get($key);
    }

    /**
     * Store an item in the settings store.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function offsetSet($key, $value): void
    {
        $this->set($key, $value);
    }

    /**
     * Remove an item from the settings store.
     *
     * @param  string  $key
     * @return void
     */
    public function offsetUnset($key): void
    {
        $this->forget($key);
    }

    /**
     * Handle dynamic calls into macros or pass missing methods to the store.
     *
     * @param  string  $method
     * @param  array  $parameters
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
