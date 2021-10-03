<?php

declare(strict_types=1);

namespace Rudnev\Settings\Cache\L1;

use Closure;
use Illuminate\Support\Arr;

class FirstLevelRegion
{
    /**
     * The name of this region.
     *
     * @var string
     */
    protected $name;

    /**
     * The array of cached items.
     *
     * @var array
     */
    protected $data = [];

    /**
     * All the items is cached or not.
     *
     * @var bool
     */
    protected $incomplete = true;

    /**
     * Region constructor.
     *
     * @param  string  $name
     * @return void
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Get the name of this region.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set the name of this region.
     *
     * @param  string  $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Determine if an item exists.
     *
     * @param  string  $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return Arr::has($this->data, $key);
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param  string  $key
     * @param  \Closure|null  $callback
     * @return mixed
     */
    public function get(string $key, Closure $callback = null)
    {
        if ($this->has($key)) {
            return Arr::get($this->data, $key);
        }

        if ($callback && ! is_null($value = $callback($key))) {
            $this->put($key, $value);
        }

        return $value ?? null;
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @param  iterable  $keys
     * @param  \Closure|null  $callback
     * @return array
     */
    public function getMultiple(iterable $keys, Closure $callback = null): array
    {
        $result = [];
        $notFound = [];

        foreach ($keys as $key) {
            if ($this->has($key)) {
                $result[$key] = Arr::get($this->data, $key);
            } else {
                $result[$key] = null;
                $notFound[] = $key;
            }
        }

        if ($callback && ! empty($notFound)) {
            $notFound = array_filter($callback($notFound), function ($value) {
                return ! is_null($value);
            });

            $this->putMultiple($notFound);

            $result = array_merge($result, $notFound);
        }

        return $result;
    }

    /**
     * Retrieve all items from the cache.
     *
     * @param  \Closure|null  $callback
     * @return array
     */
    public function all(Closure $callback = null): array
    {
        if ($this->incomplete && $callback) {
            $values = $callback();

            if (is_array($values)) {
                $this->data = $values;
            }

            $this->incomplete = false;
        }

        return $this->data;
    }

    /**
     * Store an item in the cache.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function put(string $key, $value): void
    {
        Arr::set($this->data, $key, $value);
    }

    /**
     * Store multiple items in the cache.
     *
     * @param  iterable  $values
     * @return void
     */
    public function putMultiple(iterable $values): void
    {
        foreach ($values as $key => $value) {
            $this->put($key, $value);
        }
    }

    /**
     * Remove an item from the cache.
     *
     * @param  string  $key
     * @return void
     */
    public function forget(string $key): void
    {
        Arr::forget($this->data, $key);
    }

    /**
     * Remove multiple items from the cache.
     *
     * @param  iterable  $keys
     * @return void
     */
    public function forgetMultiple(iterable $keys): void
    {
        foreach ($keys as $key) {
            $this->forget($key);
        }
    }

    /**
     * Remove all items from the cache.
     *
     * @return void
     */
    public function flush(): void
    {
        $this->data = [];
    }
}
