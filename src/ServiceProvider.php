<?php

namespace Rudnev\Settings;

use Illuminate\Support\ServiceProvider as BaseProvider;
use Rudnev\Settings\Commands\ClearCache;
use Rudnev\Settings\Contracts\FactoryContract;
use Rudnev\Settings\Contracts\RepositoryContract;
use Rudnev\Settings\Listeners\OctaneEventSubscriber;
use Rudnev\Settings\Listeners\QueueEventSubscriber;

class ServiceProvider extends BaseProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        $this->registerPublishables();

        $this->registerListeners();

        if (isset($_SERVER['LARAVEL_OCTANE'])) {
            $this->bootOctane();
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/settings.php', 'settings');

        $this->app->singleton('settings', function ($app) {
            return new SettingsManager($app);
        });

        $this->app->singleton('settings.store', function ($app) {
            return $app['settings']->store();
        });

        $this->addAliases();

        $this->registerCommands();
    }

    /**
     * Bootstrap for Laravel Octane.
     *
     * @return void
     */
    public function bootOctane(): void
    {
        $this->app['config']->push('octane.warm', 'settings');

        $this->app['events']->subscribe(OctaneEventSubscriber::class);
    }

    /**
     * Register publishables.
     *
     * @return void
     */
    public function registerPublishables(): void
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
     * Register commands.
     *
     * @return void
     */
    public function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ClearCache::class,
            ]);
        }
    }

    /**
     * Register event listeners.
     *
     * @return void
     */
    public function registerListeners(): void
    {
        if ($this->app->runningInConsole()) {
            $this->app['events']->subscribe(QueueEventSubscriber::class);
        }
    }

    /**
     * Add aliases.
     *
     * @return void
     */
    public function addAliases(): void
    {
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
    public function provides(): array
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
