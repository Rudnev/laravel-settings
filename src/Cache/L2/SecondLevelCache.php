<?php

declare(strict_types=1);

namespace Rudnev\Settings\Cache\L2;

use Illuminate\Contracts\Cache\Repository;

class SecondLevelCache
{
    /**
     * The cache store instance.
     *
     * @var \Illuminate\Contracts\Cache\Repository
     */
    protected $store;

    /**
     * The cache key prefix.
     *
     * @var string
     */
    protected $prefix = 'laravel_settings';

    /**
     * The default lifetime in minutes.
     *
     * @var int
     */
    protected $defaultLifetime = 120;

    /**
     * The array of created regions.
     *
     * @var array
     */
    protected static $regions = [];

    /**
     * The version list.
     *
     * @var int[]
     */
    protected static $versions = [];

    /**
     * SecondLevelCache constructor.
     *
     * @param \Illuminate\Contracts\Cache\Repository $store
     * @return void
     */
    public function __construct(Repository $store)
    {
        $this->store = $store;
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
     */
    public function setStore(Repository $store): void
    {
        $this->store = $store;
    }

    /**
     * Get the cache key prefix.
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Set the cache key prefix.
     *
     * @param string $prefix
     */
    public function setPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * Get the default lifetime in minutes.
     *
     * @return int
     */
    public function getDefaultLifetime(): int
    {
        return $this->defaultLifetime;
    }

    /**
     * Set the default lifetime in minutes.
     *
     * @param int $minutes
     */
    public function setDefaultLifetime(int $minutes): void
    {
        $this->defaultLifetime = $minutes;
    }

    /**
     * Get the cache region by name.
     *
     * @param string $name
     * @return \Rudnev\Settings\Cache\L2\SecondLevelRegion
     */
    public function region(string $name): SecondLevelRegion
    {
        if (empty(static::$regions[$this->prefix][$name])) {
            $fullName = sprintf('%s[%s].%s', $this->prefix, $this->getVersion(), $name);
            static::$regions[$this->prefix][$name] = new SecondLevelRegion($fullName, $this->store, $this->defaultLifetime);
        }

        return static::$regions[$this->prefix][$name];
    }

    /**
     * Remove all items from the cache.
     *
     * @return void
     */
    public function flush(): void
    {
        $this->incrementVersion();

        static::$regions[$this->prefix] = [];
    }

    /**
     * Returns the cache version.
     *
     * @return int
     */
    protected function getVersion(): int
    {
        if (array_key_exists($this->prefix, static::$versions)) {
            return static::$versions[$this->prefix];
        }

        static::$versions[$this->prefix] = (int) $this->store->get($this->getCacheVersionKey());

        return static::$versions[$this->prefix];
    }

    /**
     * Increment the cache version.
     *
     * @return void
     */
    protected function incrementVersion(): void
    {
        $version = $this->getVersion() + 1;

        $this->store->forever($this->getCacheVersionKey(), $version);

        static::$versions[$this->prefix] = $version;
    }

    /**
     * Get the cache version key.
     *
     * @return string
     */
    protected function getCacheVersionKey(): string
    {
        return $this->prefix.'.version';
    }

    /**
     * Reset static properties.
     *
     * @return void
     */
    public static function reset(): void
    {
        static::$regions = [];
        static::$versions = [];
    }
}
