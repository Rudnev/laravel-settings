<?php

namespace Rudnev\Settings;

use Rudnev\Settings\Contracts\FactoryContract;
use Rudnev\Settings\Contracts\RepositoryContract;
use Illuminate\Support\ServiceProvider as BaseProvider;

class ServiceProvider extends BaseProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap the application events.
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/settings.php' => config_path('settings.php'),
        ], 'config');

        if (! class_exists('CreateSettingsTable')) {
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                __DIR__.'/../database/migrations/create_settings_table.stub' => $this->app['path.database']."/migrations/{$timestamp}_create_settings_table.php",
            ], 'migrations');
        }
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/settings.php', 'settings');

        $this->app->singleton('settings', function ($app) {
            return new SettingsManager($app);
        });

        $this->app->singleton('settings.store', function ($app) {
            return $app['settings']->store();
        });

        $this->app->alias('settings', FactoryContract::class);
        $this->app->alias('settings', SettingsManager::class);
        $this->app->alias('settings.store', RepositoryContract::class);
        $this->app->alias('settings.store', Repository::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'settings',
            'settings.store',
            FactoryContract::class,
            SettingsManager::class,
            RepositoryContract::class,
            Repository::class,
        ];
    }
}
