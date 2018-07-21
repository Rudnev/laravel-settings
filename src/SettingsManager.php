<?php

namespace Rudnev\Settings;

use Closure;
use InvalidArgumentException;
use Illuminate\Contracts\Cache\Factory as CacheFactoryContract;
use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Rudnev\Settings\Cache\Cache;
use Rudnev\Settings\Contracts\StoreContract;
use Rudnev\Settings\Contracts\FactoryContract;
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
     * The array of resolved settings stores.
     *
     * @var \Rudnev\Settings\Contracts\RepositoryContract[]
     */
    protected $stores = [];

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected $customCreators = [];

    /**
     * Create a new Settings manager instance.
     *
     * @param  \Illuminate\Foundation\Application $app
     * @return void
     */
    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Get a settings repository instance by name.
     *
     * @param  string|null $name
     * @return \Rudnev\Settings\Contracts\RepositoryContract
     */
    public function store($name = null)
    {
        $name = $name ?: $this->app['config']['settings.default'];

        if (! isset($this->stores[$name])) {
            $this->stores[$name] = $this->resolve($name);
            $this->stores[$name]->getStore()->setName($name);
        }

        return $this->stores[$name];
    }

    /**
     * Resolve the given store.
     *
     * @param  string $name
     * @return \Rudnev\Settings\Contracts\RepositoryContract
     *
     * @throws \InvalidArgumentException
     */
    protected function resolve($name)
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Settings store [{$name}] is not defined.");
        }

        if (isset($this->customCreators[$config['driver']])) {
            return $this->callCustomCreator($config);
        }

        $driverMethod = 'create'.ucfirst($config['driver']).'Driver';

        if (! method_exists($this, $driverMethod)) {
            throw new InvalidArgumentException("Driver [{$config['driver']}] is not supported.");
        }

        return $this->{$driverMethod}($config);
    }

    /**
     * Call a custom driver creator.
     *
     * @param  array $config
     * @return mixed
     */
    protected function callCustomCreator(array $config)
    {
        return $this->customCreators[$config['driver']]($this->app, $config);
    }

    /**
     * Create an instance of the array settings driver.
     *
     * @param  array $config
     * @return \Rudnev\Settings\Repository
     */
    protected function createArrayDriver(array $config)
    {
        return $this->repository(new ArrayStore, $config);
    }

    /**
     * Create an instance of the database settings driver.
     *
     * @param  array $config
     * @return \Rudnev\Settings\Repository
     */
    protected function createDatabaseDriver(array $config)
    {
        $connection = $this->app['db']->connection($config['connection'] ?? null);

        $store = new DatabaseStore($connection, $config['table'], $config['key_column'], $config['value_column']);

        return $this->repository($store, $config);
    }

    /**
     * Create a new settings repository with the given implementation.
     *
     * @param  \Rudnev\Settings\Contracts\StoreContract $store
     * @param  array $config
     * @return \Rudnev\Settings\Repository
     */
    public function repository(StoreContract $store, array $config)
    {
        $repository = new Repository($store, $this->makeCache($config));

        $this->addEventDispatcher($repository);

        return $repository;
    }

    /**
     * Make a Cache instance.
     *
     * @param $config
     * @return \Rudnev\Settings\Cache\Cache
     */
    protected function makeCache($config)
    {
        if (empty($config['cache']['enabled']) || ! $this->app->bound(CacheFactoryContract::class)) {
            return new Cache();
        }

        $cacheManager = $this->app[CacheFactoryContract::class];

        $cache = new Cache($config['cache']['ttl']);

        $cache->setCacheRepository($cacheManager->store($config['cache']['store'] ?? null));

        return $cache;
    }

    /**
     * Set the event dispatcher instance to the settings repository.
     *
     * @param \Rudnev\Settings\Repository $repository
     * @return void
     */
    protected function addEventDispatcher(Repository $repository)
    {
        if ($this->app['config']['settings.events'] && $this->app->bound(DispatcherContract::class)) {
            $repository->setEventDispatcher($this->app[DispatcherContract::class]);
        }
    }

    /**
     * Get the settings connection configuration.
     *
     * @param  string $name
     * @return array
     */
    protected function getConfig($name)
    {
        return $this->app['config']["settings.stores.{$name}"];
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
        $this->customCreators[$driver] = $callback->bindTo($this, $this);

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
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