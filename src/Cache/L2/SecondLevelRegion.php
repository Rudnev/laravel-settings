<?php

declare(strict_types=1);

namespace Rudnev\Settings\Cache\L2;

use Closure;
use Illuminate\Contracts\Cache\Repository;

class SecondLevelRegion
{
    /**
     * The name of this region.
     *
     * @var string
     */
    protected $name;

    /**
     * The cache store instance.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $store;

    /**
     * The time to life of the cache entry.
     *
     * @var int
     */
    protected $lifetime = 0;

    /**
     * The lock operations availability flag.
     *
     * @var bool
     */
    protected $lockAvailable;

    /**
     * The version list.
     *
     * @var int[]
     */
    protected static $versions = [];

    /**
     * Region constructor.
     *
     * @param string $name
     * @param \Illuminate\Contracts\Cache\Repository $store
     * @param int $lifetime
     * @return void
     */
    public function __construct(string $name, Repository $store, int $lifetime = 0)
    {
        $this->name = $name;
        $this->store = $store;
        $this->lifetime = $lifetime;
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
     * @param string $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Get the cache store instance.
     *
     * @return \Illuminate\Contracts\Cache\Repository
     */
    public function getStore(): Repository
    {
        return $this->store;
    }

    /**
     * Set the cache store instance.
     *
     * @param \Illuminate\Contracts\Cache\Repository $store
     * @return void
     */
    public function setStore(Repository $store): void
    {
        $this->store = $store;
    }

    /**
     * Get the time to life of the cache entry.
     *
     * @return int
     */
    public function getLifetime(): int
    {
        return $this->lifetime;
    }

    /**
     * Set the time to life of the cache entry.
     *
     * @param int $lifetime
     * @return void
     */
    public function setLifetime(int $lifetime): void
    {
        $this->lifetime = $lifetime;
    }

    /**
     * Determine if an item exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return $this->store->has($this->getCacheEntryKey($key));
    }

    /**
     * Retrieve an item from the cache by key.
     *
     * @param string $key
     * @param \Closure|null $callback
     * @return mixed
     */
    public function get(string $key, Closure $callback = null)
    {
        if ($this->has($key)) {
            return $this->store->get($this->getCacheEntryKey($key));
        } elseif ($callback) {
            $value = $callback($key);

            if (! is_null($value)) {
                $this->put($key, $value);
            }
        }

        return $value ?? null;
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * Items not found in the cache will have a null value.
     *
     * @param iterable $keys
     * @param \Closure|null $callback
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getMultiple(iterable $keys, Closure $callback = null): array
    {
        $return = [];
        $map = [];

        foreach ($keys as $key) {
            $map[$key] = $this->getCacheEntryKey($key);
        }

        $result = $this->store->getMultiple(array_values($map));

        foreach ($map as $k => $v) {
            $return[$k] = null;

            foreach ($result as $key => $value) {
                if ($key === $v) {
                    $return[$k] = $value;
                    break;
                }
            }
        }

        if ($callback && $notFound = array_keys($return, null, true)) {
            $values = array_filter($callback($notFound), function ($value) {
                return ! is_null($value);
            });

            $this->putMultiple($values);

            $return = array_merge($return, $values);
        }

        return $return;
    }

    /**
     * Retrieve all items from the cache.
     *
     * @param \Closure|null $callback
     * @return array
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function all(Closure $callback = null): array
    {
        $keys = $this->get('[keys]');

        if ($keys) {
            $result = $this->getMultiple($keys);

            if (array_search(null, $result, true) === false) {
                return $result;
            }
        }

        if ($callback) {
            $this->putMultiple($values = $callback());
            $this->put('[keys]', array_keys($values));
        }

        return $values ?? [];
    }

    /**
     * Store an item in the cache.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function put(string $key, $value): void
    {
        if (! $this->has($key)) {
            $this->store->forget($this->getCacheEntryKey('[keys]'));
        }

        $this->store->put($this->getCacheEntryKey($key), $value, $this->lifetime);
    }

    /**
     * Store multiple items in the cache.
     *
     * @param iterable $values
     * @return void
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function putMultiple(iterable $values): void
    {
        $this->store->forget($this->getCacheEntryKey('[keys]'));

        $items = [];

        foreach ($values as $key => $value) {
            $items[$this->getCacheEntryKey($key)] = $value;
        }

        $this->store->setMultiple($items, $this->lifetime);
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     * @return void
     */
    public function forget(string $key): void
    {
        $this->store->forget($this->getCacheEntryKey('[keys]'));
        $this->store->forget($this->getCacheEntryKey($key));
    }

    /**
     * Remove multiple items from the cache.
     *
     * @param iterable $keys
     * @return void
     */
    public function forgetMultiple(iterable $keys): void
    {
        $this->store->forget($this->getCacheEntryKey('[keys]'));

        foreach ($keys as $key) {
            $this->store->forget($this->getCacheEntryKey($key));
        }
    }

    /**
     * Remove all items from the cache.
     *
     * @return void
     */
    public function flush(): void
    {
        $this->incrementVersion();
    }

    /**
     * Attempt to acquire the lock.
     *
     * @param string $key
     * @param Closure $callback
     * @param int $lifetime
     * @param int $timeout
     * @return void
     */
    public function lock(string $key, Closure $callback, int $lifetime = 60, int $timeout = 60): void
    {
        if ($this->isLockAvailable()) {
            $this->store->lock($this->getLockName($key), $lifetime)->block($timeout, $callback);
        } else {
            $callback();
        }
    }

    /**
     * Determine if atomic locking operations are available.
     *
     * @return bool
     */
    public function isLockAvailable(): bool
    {
        return $this->lockAvailable ?? $this->lockAvailable = method_exists($this->store->getStore(), 'lock');
    }

    /**
     * Get the name of lock.
     *
     * @param string $key
     * @return string
     */
    protected function getLockName(string $key): string
    {
        return '[locks].'.$this->getCacheEntryKey($key);
    }

    /**
     * Returns the cache version.
     *
     * @return int
     */
    protected function getVersion(): int
    {
        if (array_key_exists($this->name, static::$versions)) {
            return static::$versions[$this->name];
        }

        static::$versions[$this->name] = (int) $this->store->get($this->getCacheVersionKey());

        return static::$versions[$this->name];
    }

    /**
     * Increment the cache version.
     *
     * @return void
     */
    protected function incrementVersion(): void
    {
        $version = $this->getVersion() + 1;

        $this->store->put($this->getCacheVersionKey(), $version, $this->lifetime);

        static::$versions[$this->name] = $version;
    }

    /**
     * Get the cache version key.
     *
     * @return string
     */
    protected function getCacheVersionKey(): string
    {
        return $this->name.'.version';
    }

    /**
     * Get the cache entry key.
     *
     * @param string $key
     * @return string
     */
    protected function getCacheEntryKey(string $key): string
    {
        return sprintf('%s[%s].%s', $this->name, $this->getVersion(), $key);
    }

    /**
     * Reset static properties.
     *
     * @return void
     */
    public static function reset(): void
    {
        static::$versions = [];
    }
}
