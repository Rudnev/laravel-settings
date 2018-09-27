<?php

declare(strict_types=1);

namespace Rudnev\Settings\Cache;

use Rudnev\Settings\Scopes\Scope;
use Rudnev\Settings\Contracts\StoreContract;
use Rudnev\Settings\Cache\L1\FirstLevelCache;
use Rudnev\Settings\Cache\L2\SecondLevelCache;

class CacheDecorator implements StoreContract
{
    /**
     * The settings store instance.
     *
     * @var \Rudnev\Settings\Contracts\StoreContract
     */
    protected $store;

    /**
     * The first level cache.
     *
     * @var \Rudnev\Settings\Cache\L1\FirstLevelCache
     */
    protected $firstLevelCache;

    /**
     * The second level cache.
     *
     * @var \Rudnev\Settings\Cache\L2\SecondLevelCache
     */
    protected $secondLevelCache;

    /**
     * CacheDecorator constructor.
     *
     * @param \Rudnev\Settings\Contracts\StoreContract $store
     * @param \Rudnev\Settings\Cache\L1\FirstLevelCache $firstLevelCache
     * @param \Rudnev\Settings\Cache\L2\SecondLevelCache $secondLevelCache
     * @return void
     */
    public function __construct(
        StoreContract $store,
        FirstLevelCache $firstLevelCache,
        SecondLevelCache $secondLevelCache
    ) {
        $this->store = $store;
        $this->firstLevelCache = $firstLevelCache;
        $this->secondLevelCache = $secondLevelCache;
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
     * @param \Rudnev\Settings\Contracts\StoreContract $store
     * @return void
     */
    public function setStore(StoreContract $store): void
    {
        $this->store = $store;
    }

    /**
     * Get the first level cache instance.
     *
     * @return \Rudnev\Settings\Cache\L1\FirstLevelCache
     */
    public function getFirstLevelCache(): FirstLevelCache
    {
        return $this->firstLevelCache;
    }

    /**
     * Set the first level cache instance.
     *
     * @param \Rudnev\Settings\Cache\L1\FirstLevelCache $cache
     * @return void
     */
    public function setFirstLevelCache(FirstLevelCache $cache): void
    {
        $this->firstLevelCache = $cache;
    }

    /**
     * Get the second level cache instance.
     *
     * @return \Rudnev\Settings\Cache\L2\SecondLevelCache
     */
    public function getSecondLevelCache(): SecondLevelCache
    {
        return $this->secondLevelCache;
    }

    /**
     * Set the second level cache instance.
     *
     * @param \Rudnev\Settings\Cache\L2\SecondLevelCache $secondLevelCache
     * @return void
     */
    public function setSecondLevelCache(SecondLevelCache $secondLevelCache): void
    {
        $this->secondLevelCache = $secondLevelCache;
    }

    /**
     * Get the settings store name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->store->getName();
    }

    /**
     * Set the settings store name.
     *
     * @param $name
     * @return void
     */
    public function setName(string $name): void
    {
        $this->store->setName($name);
    }

    /**
     * Get the scope.
     *
     * @return \Rudnev\Settings\Scopes\Scope
     */
    public function getScope(): Scope
    {
        return $this->store->getScope();
    }

    /**
     * Set the scope.
     *
     * @param \Rudnev\Settings\Scopes\Scope $scope
     * @return void
     */
    public function setScope(Scope $scope): void
    {
        $this->store->setScope($scope);
    }

    /**
     * Determine if an item exists in the settings store.
     *
     * @param  string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        $region = $this->getScope()->hash.'[has]';

        return $this->firstLevelCache->region($region)->get($key, function ($key) use ($region) {
            return $this->secondLevelCache->region($region)->get($key, function ($key) {
                return $this->store->has($key);
            });
        });
    }

    /**
     * Retrieve an item from the settings store by key.
     *
     * @param  string $key
     * @return mixed
     */
    public function get(string $key)
    {
        $region = $this->getScope()->hash;

        return $this->firstLevelCache->region($region)->get($key, function ($key) use ($region) {
            return $this->secondLevelCache->region($region)->get($key, function ($key) {
                return $this->store->get($key);
            });
        });
    }

    /**
     * Retrieve multiple items from the settings store by key.
     *
     * Items not found in the settings store will have a null value.
     *
     * @param  iterable $keys
     * @return array
     */
    public function getMultiple(iterable $keys): array
    {
        $region = $this->getScope()->hash;

        return $this->firstLevelCache->region($region)->getMultiple($keys, function ($keys) use ($region) {
            return $this->secondLevelCache->region($region)->getMultiple($keys, function ($keys) {
                return $this->store->getMultiple($keys);
            });
        });
    }

    /**
     * Get all of the settings items.
     *
     * @return array
     */
    public function all(): array
    {
        $region = $this->getScope()->hash;

        return $this->firstLevelCache->region($region)->all(function () use ($region) {
            return $this->secondLevelCache->region($region)->all(function () {
                return $this->store->all();
            });
        });
    }

    /**
     * Store an item in the settings store.
     *
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function set(string $key, $value): void
    {
        $this->store->set($key, $value);

        $region = $this->getScope()->hash;

        $this->firstLevelCache->region($region)->put($key, $value);
        $this->secondLevelCache->region($region)->put($key, $value);

        $this->firstLevelCache->region($region.'[has]')->forget($key);
        $this->secondLevelCache->region($region.'[has]')->forget($key);
    }

    /**
     * Store multiple items in the settings store.
     *
     * @param  iterable $values
     * @return void
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function setMultiple(iterable $values): void
    {
        $this->store->setMultiple($values);

        $region = $this->getScope()->hash;

        $this->firstLevelCache->region($region)->putMultiple($values);
        $this->secondLevelCache->region($region)->putMultiple($values);

        $this->firstLevelCache->region($region.'[has]')->forgetMultiple($keys = array_keys($values));
        $this->secondLevelCache->region($region.'[has]')->forgetMultiple($keys);
    }

    /**
     * Remove an item from the settings store.
     *
     * @param  string $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        $result = $this->store->forget($key);

        if ($result) {
            $this->firstLevelCache->region($this->getScope()->hash)->forget($key);
            $this->firstLevelCache->region($this->getScope()->hash.'[has]')->forget($key);

            $this->secondLevelCache->region($this->getScope()->hash)->flush();
            $this->secondLevelCache->region($this->getScope()->hash.'[has]')->flush();
        }

        return $result;
    }

    /**
     * Remove multiple items from the settings store.
     *
     * @param  iterable $keys
     * @return bool
     */
    public function forgetMultiple(iterable $keys): bool
    {
        $result = $this->store->forgetMultiple($keys);

        if ($result) {
            $this->firstLevelCache->region($this->getScope()->hash)->forgetMultiple($keys);
            $this->firstLevelCache->region($this->getScope()->hash.'[has]')->forgetMultiple($keys);

            $this->secondLevelCache->region($this->getScope()->hash)->flush();
            $this->secondLevelCache->region($this->getScope()->hash.'[has]')->flush();
        }

        return $result;
    }

    /**
     * Remove all items from the settings store.
     *
     * @return bool
     */
    public function flush(): bool
    {
        $result = $this->store->flush();

        if ($result) {
            $this->firstLevelCache->region($this->getScope()->hash)->flush();
            $this->firstLevelCache->region($this->getScope()->hash.'[has]')->flush();

            $this->secondLevelCache->region($this->getScope()->hash)->flush();
            $this->secondLevelCache->region($this->getScope()->hash.'[has]')->flush();
        }

        return $result;
    }

    /**
     * Set the scope.
     *
     * @param \Rudnev\Settings\Scopes\Scope $scope
     * @return \Rudnev\Settings\Cache\CacheDecorator
     */
    public function scope(Scope $scope): StoreContract
    {
        return new static($this->store->scope($scope), $this->firstLevelCache, $this->secondLevelCache);
    }
}
