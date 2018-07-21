<?php

namespace Rudnev\Settings\Cache;

use Closure;
use Illuminate\Contracts\Cache\Repository as CacheStore;
use Psr\SimpleCache\InvalidArgumentException;
use Rudnev\Settings\Contracts\CacheContract;

class Cache implements CacheContract
{
    /**
     * The cache repository implementation.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $cache;

    /**
     * The cache index instance.
     *
     * @var \Rudnev\Settings\Cache\CacheIndex
     */
    protected $index;

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
        $this->indexKey = '_'.$prefix.'index';
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
    public function setCacheRepository(CacheStore $cache)
    {
        $this->cache = $cache;

        $this->makeIndex();
    }

    /**
     * Make the cache index.
     *
     * @return void
     */
    protected function makeIndex()
    {
        $this->index = $this->cache->get($this->indexKey) ?? new CacheIndex();
    }

    /**
     * Store the cache index.
     *
     * return void
     */
    protected function saveIndex()
    {
        $this->cache->put($this->indexKey, $this->index, $this->ttl);
    }

    /**
     * Remove the cache index.
     *
     * return void
     */
    protected function forgetIndex()
    {
        $this->index->clear();
        $this->cache->forget($this->indexKey);
    }

    /**
     * Determine if an item exists in the cache store.
     *
     * @param  string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return $this->cache ? $this->index->has($this->cacheKey($key)) : false;
    }

    /**
     * Retrieve an item from the cache store by key.
     *
     * @param  string $key
     *
     * @return mixed
     */
    public function get($key)
    {
        if ($this->cache && $this->index->has($key = $this->cacheKey($key))) {
            return $this->cache->get($key);
        }

        return null;
    }

    /**
     * Retrieve multiple items from the cache by key.
     *
     * @param iterable $keys
     *
     * @return array
     */
    public function getMultiple(iterable $keys)
    {
        if (! $this->cache) {
            return [];
        }

        $keys = array_map([$this, 'cacheKey'], $keys);

        foreach ($keys as $key) {
            if (! $this->index->has($this->cacheKey($key))) {
                unset($keys[$key]);
            }
        }

        if (! $keys) {
            return [];
        }

        try {
            $values = $this->cache->getMultiple($keys);
        } catch (InvalidArgumentException $ex) {
            return [];
        }

        $return = [];

        foreach ($values as $key => $value) {
            if (is_null($value) && ! $this->cache->has($key)) {
                continue;
            }

            $return[substr($key, strlen($this->prefix))] = $value;

            unset($values[$key]);
        }

        return $return;
    }

    /**
     * Get all of the cache items.
     *
     * @return iterable
     */
    public function all()
    {
        $return = [];

        try {
            $values = $this->cache ? $this->cache->getMultiple($this->index->keys()) : [];
        } catch (InvalidArgumentException $ex) {
            return [];
        }

        foreach ($values as $key => $value) {
            $return[substr($key, strlen($this->prefix))] = $value;
            unset($values[$key]);
        }

        return $return;
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
        if (! $this->cache) {
            return $callback();
        }

        if ($this->index->has($key = $this->cacheKey($key))) {
            $value = $this->cache->get($key);

            if (! is_null($value)) {
                return $value;
            }
        }

        $value = $callback();

        if (! is_null($value)) {
            $this->cache->put($key, $value, $this->ttl);
            $this->index->add($key);
        }

        $this->saveIndex();

        return $value;
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
        if (! $this->cache) {
            return;
        }

        if (! is_null($value)) {
            $this->cache->put($key = $this->cacheKey($key), $value, $this->ttl);
            $this->index->add($key);
        }

        $this->saveIndex();
    }

    /**
     * Store multiple items in the cache.
     *
     * @param iterable $values
     *
     * @return bool
     */
    public function putMultiple(iterable $values)
    {
        if (! $this->cache) {
            return false;
        }

        foreach ($values as $key => $value) {
            unset($values[$key]);

            if (is_null($value)) {
                continue;
            }

            $key = $this->cacheKey($key);

            $values[$key] = $value;

            $this->index->add($key);
        }

        try {
            $this->cache->setMultiple($values, $this->ttl);
        } catch (InvalidArgumentException $ex) {
            return false;
        }

        $this->saveIndex();

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
        if ($this->cache) {
            $keys = explode('.', $key);

            $root = $this->cacheKey($keys[0]);

            $deleted = [];

            do {
                $cacheKey = $this->cacheKey(implode('.', $keys));
                $this->cache->forget($cacheKey);
                $deleted[] = $cacheKey;
            } while (array_pop($keys) && $keys);

            foreach ($this->index->childKeys($root) as $k) {
                if (! in_array($k, $deleted)) {
                    $this->cache->forget($k);
                }
            }

            $this->index->remove($root);

            $this->saveIndex();
        }
    }

    /**
     * Remove multiple items from the cache.
     *
     * @param iterable $keys
     *
     * @return void
     */
    public function forgetMultiple(iterable $keys)
    {
        if (! isset($this->cache)) {
            return;
        }

        foreach ($keys as $key) {
            $keys = explode('.', $key);

            $root = $this->cacheKey($keys[0]);

            $deleted = [];

            do {
                $cacheKey = $this->cacheKey(implode('.', $keys));
                $this->cache->forget($cacheKey);
                $deleted[] = $cacheKey;
            } while (array_pop($keys) && $keys);

            foreach ($this->index->childKeys($root) as $k) {
                if (! in_array($k, $deleted)) {
                    $this->cache->forget($k);
                }
            }

            $this->index->remove($root);
        }

        $this->saveIndex();
    }

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool
     */
    public function flush()
    {
        if (! $this->cache) {
            return false;
        }

        try {
            $this->cache->deleteMultiple($this->index->keys());
            $this->forgetIndex();
        } catch (InvalidArgumentException $ex) {
            return false;
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