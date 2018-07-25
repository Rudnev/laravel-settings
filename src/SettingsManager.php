<?php

namespace Rudnev\Settings;

use Closure;
use Illuminate\Contracts\Cache\Factory as CacheFactoryContract;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Rudnev\Settings\Cache\Cache;
use Rudnev\Settings\Cache\EventSubscriber;
use Rudnev\Settings\Contracts\FactoryContract;
use Rudnev\Settings\Contracts\StoreContract;
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
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Get a repository instance.
     *
     * @param  string $name
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function store($name = null)
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
     * @param  array $config
     * @return \Rudnev\Settings\Repository
     */
    protected function createArrayStore($name, array $config)
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

        $cache = $this->makeCache($config['cache'] ?? null);

        return $this->repository($store, $cache);
    }

    /**
     * Create a new settings repository with the given implementation.
     *
     * @param  \Rudnev\Settings\Contracts\StoreContract $store
     * @param  \Rudnev\Settings\Cache\Cache
     * @return \Rudnev\Settings\Repository
     */
    public function repository(StoreContract $store, Cache $cache = null)
    {
        $repository = new Repository($store);

        $this->addEventDispatcher($repository);

        $this->addCache($repository, $cache);

        return $repository;
    }

    /**
     * Set the event dispatcher instance to the settings repository.
     *
     * @param \Rudnev\Settings\Repository $repository
     * @return void
     */
    protected function addEventDispatcher(Repository $repository)
    {
        if ($this->getConfig('events')) {
            $repository->setEventDispatcher($this->app[DispatcherContract::class]);
        } else {
            $repository->setEventDispatcher(new Dispatcher());
        }
    }

    /**
     * Set the cache instance to the settings repository.
     *
     * @param \Rudnev\Settings\Repository $repository
     * @param  \Rudnev\Settings\Cache\Cache $cache
     * @return void
     */
    protected function addCache(Repository $repository, Cache $cache = null)
    {
        if ($cache) {
            $repository->setCache($cache);

            $repository->getEventDispatcher()->subscribe(new EventSubscriber($cache));
        }
    }

    /**
     * Make a cache instance.
     *
     * @param array $config
     * @return \Rudnev\Settings\Cache\Cache|null
     */
    protected function makeCache(array $config = [])
    {
        if (! empty($config['enabled'])) {
            $repo = $this->app[CacheFactoryContract::class]->store($config['store'] ?? null);

            return new Cache($repo, $config['ttl'] ?? null);
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