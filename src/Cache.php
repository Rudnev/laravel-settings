<?php

namespace Rudnev\Settings;

use Closure;
use Illuminate\Contracts\Cache\Repository as CacheContract;
use Psr\SimpleCache\InvalidArgumentException;

class Cache
{
    /**
     * The cache repository implementation.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * The time to life of the cache items.
     *
     * @var integer
     */
    protected $ttl;

    /**
     * A string that should be prepended to cache keys.
     *
     * @var string
     */
    protected $prefix;

    /**
     * The key for retrieving list of cached items
     *
     * @var string
     */
    protected $indexKey;

    /**
     * Cache constructor.
     *
     * @param integer $ttl
     * @param string $prefix
     *
     * @return void
     */
    public function __construct($ttl = 120, $prefix = 'lsi:')
    {
        $this->ttl = $ttl;
        $this->prefix = $prefix;
        $this->indexKey = '_'.$prefix.'cached-items';
    }

    /**
     * Get the cache repository instance.
     *
     * @return \Illuminate\Contracts\Cache\Repository|null
     */
    public function getCacheRepository()
    {
        return $this->cache;
    }

    /**
     * Set the cache repository instance.
     *
     * @param \Illuminate\Contracts\Cache\Repository $cache
     *
     * @return void
     */
    public function setCacheRepository(CacheContract $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Retrieve list of cached items
     *
     * @return iterable
     */
    protected function getIndex()
    {
        return $this->cache->get($this->indexKey) ?? [];
    }

    /**
     * Store list of cached items
     *
     * @param iterable $index
     */
    protected function putIndex($index)
    {
        $this->cache->put($this->indexKey, $index, $this->ttl);
    }

    /**
     * Get an item from the cache, or store the default value.
     *
     * @param string $key
     * @param Closure $callback
     *
     * @return mixed
     */
    public function remember($key, Closure $callback)
    {
        if (isset($this->cache)) {
            $index = $this->getIndex();

            $value = $this->cache->get($key = $this->cacheKey($key));

            // If the item exists in the cache and present in the index, we return it,
            // otherwise we write a new value from the callback function.
            if (! is_null($value) && $inIndex = array_key_exists($key, $index)) {
                return $value;
            }

            $this->cache->put($key, $value = $callback(), $this->ttl);

            if (isset($inIndex) && ! $inIndex || ! array_key_exists($key, $index)) {
                $index[$key] = null;
                $this->putIndex($index);
            }

            return $value;
        }

        return $callback();
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * @param iterable $keys
     *
     * @return array
     */
    public function getMultiple($keys)
    {
        if (! isset($this->cache)) {
            return [];
        }

        $index = $this->getIndex();

        try {
            $values = $this->cache->getMultiple(array_map([$this, 'cacheKey'], $keys));
        } catch (InvalidArgumentException $ex) {
            $values = [];
        }

        $return = [];

        foreach ($values as $key => $value) {
            // Skipping non-existing
            if (is_null($value) && ! $this->cache->has($key)) {
                continue;
            }

            // Consistency check
            if (! array_key_exists($key, $index)) {
                $this->cache->forget($key);
                continue;
            }

            $return[substr($key, strlen($this->prefix))] = $value;

            unset($values[$key]);
        }

        return $return;
    }

    /**
     * Store an item in the cache.
     *
     * @param $key
     * @param $value
     *
     * @return void
     */
    public function put($key, $value)
    {
        if (! isset($this->cache)) {
            return;
        }

        $this->cache->put($key = $this->cacheKey($key), $value, $this->ttl);

        $index = $this->getIndex();

        if (! array_key_exists($key, $index)) {
            $index[$key] = null;
            $this->putIndex($index);
        }
    }

    /**
     * Store multiple items in the cache.
     *
     * @param iterable $values
     *
     * @return bool
     */
    public function putMultiple($values)
    {
        if (! isset($this->cache)) {
            return false;
        }

        $index = $this->getIndex();

        foreach ($values as $key => $value) {
            unset($values[$key]);

            $key = $this->cacheKey($key);

            $values[$key] = $value;

            $index[$key] = null;
        }

        try {
            $this->cache->setMultiple($values, $this->ttl);
        } catch (InvalidArgumentException $ex) {
            return false;
        }

        $this->putIndex($index);

        return true;
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     *
     * @return void
     */
    public function forget($key)
    {
        if (isset($this->cache)) {
            $index = $this->getIndex();

            $keys = explode('.', $key);

            do {
                $key = $this->cacheKey(implode('.', $keys));
                $this->cache->forget($key);
                unset($index[$key]);
            } while (array_pop($keys) && $keys);

            $this->putIndex($index);
        }
    }

    /**
     * Remove multiple items from the cache.
     *
     * @param iterable $keys
     *
     * @return void
     */
    public function forgetMultiple($keys)
    {
        if (! isset($this->cache)) {
            return;
        }

        $index = $this->getIndex();

        foreach ($keys as $key) {
            $keys = explode('.', $key);

            do {
                $key = $this->cacheKey(implode('.', $keys));
                $this->cache->forget($key);
                unset($index[$key]);
            } while (array_pop($keys) && $keys);
        }

        $this->putIndex($index);
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool
     */
    public function flush()
    {
        if (isset($this->cache)) {
            try {
                $this->cache->deleteMultiple(array_keys($this->getIndex()));
                $this->cache->forget($this->indexKey);
            } catch (InvalidArgumentException $ex) {
                return false;
            }
        }

        return true;
    }

    /**
     * Format the key for a cache item.
     *
     * @param $key
     *
     * @return string
     */
    protected function cacheKey($key)
    {
        return $this->prefix.$key;
    }
}