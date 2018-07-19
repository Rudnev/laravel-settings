<?php

namespace Rudnev\Settings\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool has(string $key)
 * @method static mixed get(string $key, mixed $default = null)
 * @method static mixed all()
 * @method static void set(string $key, mixed $value)
 * @method static bool forget(string $key)
 * @method static bool flush()
 * @method static \Rudnev\Settings\Contracts\StoreContract getStore()
 * @method static \Rudnev\Settings\Contracts\RepositoryContract store()
 * @method static \Rudnev\Settings\Repository repository(\Rudnev\Settings\Contracts\StoreContract $store, array $config)
 * @method static \Rudnev\Settings\SettingsManager extend(string $driver, \Closure $callback)
 * @see \Rudnev\Settings\SettingsManager
 * @see \Rudnev\Settings\Repository
 */
class SettingsFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'settings';
    }
}