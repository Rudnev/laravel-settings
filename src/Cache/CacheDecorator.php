<?php

declare(strict_types=1);

namespace Rudnev\Settings\Cache;

use Illuminate\Support\Arr;
use Rudnev\Settings\Cache\L1\FirstLevelCache;
use Rudnev\Settings\Cache\L1\FirstLevelRegion;
use Rudnev\Settings\Cache\L2\SecondLevelCache;
use Rudnev\Settings\Cache\L2\SecondLevelRegion;
use Rudnev\Settings\Contracts\StoreContract;
use Rudnev\Settings\Scopes\Scope;

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
     * @param  \Rudnev\Settings\Contracts\StoreContract  $store
     * @param  \Rudnev\Settings\Cache\L1\FirstLevelCache  $firstLevelCache
     * @param  \Rudnev\Settings\Cache\L2\SecondLevelCache  $secondLevelCache
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
     * @param  \Rudnev\Settings\Contracts\StoreContract  $store
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
     * @param  \Rudnev\Settings\Cache\L1\FirstLevelCache  $cache
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
     * @param  \Rudnev\Settings\Cache\L2\SecondLevelCache  $secondLevelCache
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
     * @param  string  $name
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
     * @param  \Rudnev\Settings\Scopes\Scope  $scope
     * @return void
     */
    public function setScope(Scope $scope): void
    {
        $this->store->setScope($scope);
    }

    /**
     * Get the first level region instance.
     *
     * @return \Rudnev\Settings\Cache\L1\FirstLevelRegion
     */
    public function getFirstLevelRegion(): FirstLevelRegion
    {
        return $this->firstLevelCache->region($this->getScope()->hash);
    }

    /**
     * Get the second level region instance.
     *
     * @return \Rudnev\Settings\Cache\L2\SecondLevelRegion
     */
    public function getSecondLevelRegion(): SecondLevelRegion
    {
        return $this->secondLevelCache->region($this->getScope()->hash);
    }

    /**
     * Determine if an item exists in the settings store.
     *
     * @param  string  $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return ! is_null($this->get($key));
    }

    /**
     * Retrieve an item from the settings store by key.
     *
     * @param  string  $key
     * @return mixed
     */
    public function get(string $key)
    {
        $firstLevel = $this->getFirstLevelRegion();

        return $firstLevel->get($key, function ($key) use ($firstLevel) {
            $root = $this->getKeyRoot($key);

            $result = $this->getSecondLevelRegion()->get($root, function ($key) {
                return $this->store->get($key);
            });

            if (! is_null($result) && ! $firstLevel->has($root)) {
                $firstLevel->put($root, $result);
            }

            return Arr::get([$root => $result], $key);
        });
    }

    /**
     * Retrieve multiple items from the settings store by key.
     *
     * Items not found in the settings store will have a null value.
     *
     * @param  iterable  $keys
     * @return array
     */
    public function getMultiple(iterable $keys): array
    {
        $firstLevel = $this->getFirstLevelRegion();

        return $firstLevel->getMultiple($keys, function ($keys) use ($firstLevel) {
            $return = [];

            $roots = $this->getKeyRoot($keys);

            $data = $this->getSecondLevelRegion()->getMultiple($roots);

            foreach ($data as $root => $value) {
                if (! is_null($value) && ! $firstLevel->has($root)) {
                    $firstLevel->put($root, $value);
                }
            }

            foreach ($keys as $key) {
                $return[$key] = Arr::get($data, $key);
            }

            if ($notFound = array_keys($return, null, true)) {
                $return = array_merge($return, $this->store->getMultiple($notFound));
            }

            return $return;
        });
    }

    /**
     * Get all the settings items.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->getFirstLevelRegion()->all(function () {
            return $this->getSecondLevelRegion()->all(function () {
                return $this->store->all();
            });
        });
    }

    /**
     * Store an item in the settings store.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function set(string $key, $value): void
    {
        $this->store->set($key, $value);

        $this->getFirstLevelRegion()->put($key, $value);

        $this->getSecondLevelRegion()->forget($this->getKeyRoot($key));
    }

    /**
     * Store multiple items in the settings store.
     *
     * @param  iterable  $values
     * @return void
     */
    public function setMultiple(iterable $values): void
    {
        $this->store->setMultiple($values);

        $this->getFirstLevelRegion()->putMultiple($values);

        $keys = [];

        foreach ($values as $k => $v) {
            $keys[] = $k;
        }

        $this->getSecondLevelRegion()->forgetMultiple($this->getKeyRoot($keys));
    }

    /**
     * Remove an item from the settings store.
     *
     * @param  string  $key
     * @return bool
     */
    public function forget(string $key): bool
    {
        $result = $this->store->forget($key);

        if ($result) {
            $this->getFirstLevelRegion()->forget($key);
            $this->getSecondLevelRegion()->forget($this->getKeyRoot($key));
        }

        return $result;
    }

    /**
     * Remove multiple items from the settings store.
     *
     * @param  iterable  $keys
     * @return bool
     */
    public function forgetMultiple(iterable $keys): bool
    {
        $result = $this->store->forgetMultiple($keys);

        if ($result) {
            $this->getFirstLevelRegion()->forgetMultiple($keys);
            $this->getSecondLevelRegion()->forgetMultiple($this->getKeyRoot($keys));
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
            $this->getFirstLevelRegion()->flush();
            $this->getSecondLevelRegion()->flush();
        }

        return $result;
    }

    /**
     * Set the scope.
     *
     * @param  \Rudnev\Settings\Scopes\Scope  $scope
     * @return \Rudnev\Settings\Cache\CacheDecorator
     */
    public function scope(Scope $scope): StoreContract
    {
        return new static($this->store->scope($scope), $this->firstLevelCache, $this->secondLevelCache);
    }

    /**
     * Get the root of key.
     *
     * @param string|iterable
     * @return mixed
     */
    protected function getKeyRoot($key)
    {
        if (is_iterable($key)) {
            $roots = [];

            foreach ($key as $k) {
                $roots[] = explode('.', $k)[0];
            }

            return array_unique($roots);
        }

        return explode('.', $key)[0];
    }
}
