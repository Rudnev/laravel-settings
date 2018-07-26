<?php

namespace Rudnev\Settings\Cache;

use Closure;
use Rudnev\Settings\Stores\ArrayStore;
use Illuminate\Contracts\Cache\Repository;
use Rudnev\Settings\Contracts\StoreContract;

class Cache extends ArrayStore implements StoreContract
{
    /**
     * The cache repository instance.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $repo;

    /**
     * The key for retrieving cached data.
     *
     * @var string
     */
    protected $name;

    /**
     * The time to life of the cache.
     *
     * @var int
     */
    protected $ttl;

    /**
     * Cache constructor.
     *
     * @param \Illuminate\Contracts\Cache\Repository $repo
     * @param int $ttl
     * @param string $name
     *
     * @return void
     */
    public function __construct(Repository $repo, $ttl = 120, $name = 'laravel-settings')
    {
        $this->repo = $repo;
        $this->name = $name;
        $this->ttl = $ttl;
    }

    /**
     * Get the cache repository instance.
     *
     * @return \Illuminate\Contracts\Cache\Repository|null
     */
    public function getRepository()
    {
        return $this->repo;
    }

    /**
     * Set the cache repository instance.
     *
     * @param \Illuminate\Contracts\Cache\Repository $cache
     *
     * @return void
     */
    public function setRepository(Repository $cache)
    {
        $this->repo = $cache;
    }

    /**
     * Load data from the cache, or store data from callback function.
     *
     * @param \Closure $callback
     * @return void
     */
    public function load(Closure $callback)
    {
        $values = $this->repo->remember($this->name, $this->ttl, $callback) ?? [];

        if (is_array($values)) {
            $this->storage = $values;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value)
    {
        $this->repo->forget($this->name);

        parent::set($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function forget($key)
    {
        $this->repo->forget($this->name);

        return parent::forget($key);
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        $this->repo->forget($this->name);

        return parent::flush();
    }
}
