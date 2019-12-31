<?php

namespace Rudnev\Settings;

use Closure;
use Illuminate\Contracts\Cache\Factory as CacheFactoryContract;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Rudnev\Settings\Cache\CacheDecorator;
use Rudnev\Settings\Cache\L1\FirstLevelCache;
use Rudnev\Settings\Cache\L2\SecondLevelCache;
use Rudnev\Settings\Contracts\FactoryContract;
use Rudnev\Settings\Contracts\StoreContract;
use Rudnev\Settings\Scopes\Scope;
use Rudnev\Settings\Stores\ArrayStore;
use Rudnev\Settings\Stores\DatabaseStore;

/**
 * @mixin \Rudnev\Settings\Contracts\RepositoryContract
 */
class SettingsManager implements FactoryContract
{
    /**
     * The application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * The array of created stores.
     *
     * @var array
     */
    protected $stores = [];

    /**
     * Create a new manager instance.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Get a repository instance.
     *
     * @param string $name
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function store(string $name = null)
    {
        $name = $name ?: $this->getDefaultStore();

        if (is_null($name)) {
            throw new InvalidArgumentException(sprintf('Unable to resolve NULL store for [%s].', static::class));
        }

        if (! isset($this->stores[$name])) {
            $this->stores[$name] = $this->createStore($name);
        }

        return $this->stores[$name];
    }

    /**
     * Get the default store name.
     *
     * @return string
     */
    public function getDefaultStore()
    {
        return $this->getConfig('default');
    }

    /**
     * Create a new store instance.
     *
     * @param  string $name
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    protected function createStore($name)
    {
        $config = $this->getConfig("stores.$name");

        if (is_null($config)) {
            throw new InvalidArgumentException("Store [{$name}] is not defined.");
        }

        $driver = $config['driver'] ?? null;

        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($driver, $name, $config);
        } else {
            $method = 'create'.Str::studly($driver).'Store';

            if (method_exists($this, $method)) {
                return $this->$method($name, $config);
            }
        }

        throw new InvalidArgumentException("Driver [$driver] not supported.");
    }

    /**
     * Call a custom driver creator.
     *
     * @param  string $driver
     * @param  string $storeName
     * @param  array $config
     * @return mixed
     */
    protected function callCustomCreator($driver, $storeName, $config)
    {
        return $this->customCreators[$driver]($this->app, $storeName, $config);
    }

    /**
     * Create an instance of the array store.
     *
     * @param string $name
     * @return \Rudnev\Settings\Repository
     */
    protected function createArrayStore($name)
    {
        $store = new ArrayStore();

        $store->setName($name);

        return $this->repository($store);
    }

    /**
     * Create an instance of the database store.
     *
     * @param string $name
     * @param  array $config
     * @return \Rudnev\Settings\Repository
     */
    protected function createDatabaseStore($name, array $config)
    {
        $connection = $this->app['db']->connection($config['connection'] ?? null);

        $store = new DatabaseStore($connection, $config['names']);

        $store->setName($name);

        $store = $this->makeCacheWrapper($store, $config['cache'] ?? null);

        $this->preloadScopes($config['scopes']['preload'] ?? [], $store);

        $repository = $this->repository($store);

        $repository->setScope($config['scopes']['default'] ?? 'default');

        return $repository;
    }

    /**
     * Preload scopes.
     *
     * @param array $scopes
     * @param \Rudnev\Settings\Contracts\StoreContract $store
     * @return void
     */
    public function preloadScopes(array $scopes, StoreContract $store): void
    {
        if ($store instanceof CacheDecorator) {
            foreach ($scopes as $scope) {
                $store->scope(new Scope($scope))->all();
            }
        }
    }

    /**
     * Create a new settings repository with the given implementation.
     *
     * @param  \Rudnev\Settings\Contracts\StoreContract $store
     * @return \Rudnev\Settings\Repository
     */
    public function repository(StoreContract $store)
    {
        $repository = new Repository($store);

        $repository->setEventDispatcher($this->getEventDispatcher());

        return $repository;
    }

    /**
     * Get the event dispatcher instance.
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    protected function getEventDispatcher(): DispatcherContract
    {
        if ($this->getConfig('events')) {
            return $this->app[DispatcherContract::class];
        } else {
            return new Dispatcher();
        }
    }

    /**
     * Wrap the store in the cache decorator.
     *
     * @param \Rudnev\Settings\Contracts\StoreContract $store
     * @param array $config
     * @return \Rudnev\Settings\Cache\CacheDecorator|\Rudnev\Settings\Contracts\StoreContract
     */
    protected function makeCacheWrapper(StoreContract $store, array $config = [])
    {
        if (empty($config['enabled'])) {
            return $store;
        }

        return new CacheDecorator($store, $this->getFirstLevelCache(), $this->getSecondLevelCache($store, $config));
    }

    /**
     * Get the first level cache instance.
     *
     * @return \Rudnev\Settings\Cache\L1\FirstLevelCache
     */
    public function getFirstLevelCache(): FirstLevelCache
    {
        return new FirstLevelCache();
    }

    /**
     * Get the second level cache instance.
     *
     * @param \Rudnev\Settings\Contracts\StoreContract|string $store
     * @param array|null $config
     * @return \Rudnev\Settings\Cache\L2\SecondLevelCache
     */
    public function getSecondLevelCache($store, array $config = null): SecondLevelCache
    {
        $repository = $this->app[CacheFactoryContract::class]->store($config['store'] ?? null);

        $secondLevelCache = new SecondLevelCache($repository);

        $storeName = $store instanceof StoreContract ? $store->getName() : $store;

        $secondLevelCache->setPrefix(sprintf('%s.%s', $config['prefix'] ?? 'ls', $storeName));

        if (isset($config['ttl'])) {
            if ($this->app->version() >= 5.8) {
                $secondLevelCache->setDefaultLifetime($config['ttl'] * 60);
            } else {
                $secondLevelCache->setDefaultLifetime((int) $config['ttl']);
            }
        }

        return $secondLevelCache;
    }

    /**
     * Clear the cache.
     *
     * @return void
     */
    public function clearCache(): void
    {
        foreach ($this->stores as $repo) {
            $store = $repo->getStore();

            if ($store instanceof CacheDecorator) {
                $store->getFirstLevelCache()->flush();
            }
        }

        foreach ($this->getConfig('stores') as $name => $config) {
            if (! empty($config['cache'])) {
                $this->getSecondLevelCache($name, $config['cache'])->flush();
            }
        }
    }

    /**
     * Get the settings configuration.
     *
     * @param  string $key
     * @return mixed
     */
    protected function getConfig($key)
    {
        return $this->app['config']["settings.$key"];
    }

    /**
     * Register a custom driver creator Closure.
     *
     * @param  string $driver
     * @param  \Closure $callback
     * @return $this
     */
    public function extend($driver, Closure $callback)
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Dynamically call the default store instance.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->store()->$method(...$parameters);
    }
}
