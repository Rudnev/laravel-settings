<?php

namespace Rudnev\Settings\Traits;

use Rudnev\Settings\Structures\Container;

trait HasSettings
{
    /**
     * The settings repository instance.
     *
     * @var \Rudnev\Settings\Contracts\RepositoryContract|null
     */
    protected $settingsRepo;

    /**
     * The state of the settings.
     *
     * @var \Rudnev\Settings\Structures\Container|null
     */
    protected $settingsAttribute;

    /**
     * The "booting" method of the trait.
     *
     * @return void
     */
    public static function bootHasSettings()
    {
        static::saved(function (self $model) {
            // return if settings are not affected
            if (is_null($model->settingsAttribute)) {
                return;
            }

            // removing settings
            if (! empty($deleted = $model->settingsAttribute->getDeleted())) {
                $model->settings()->forget(array_keys($deleted));
            }

            // saving settings
            if (! empty($updated = $model->settingsAttribute->getUpdated())) {
                $model->settings()->set($updated);
            }

            $model->settingsAttribute->sync();
        });

        static::deleting(function (self $model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->settings()->flush();

            if (! is_null($model->settingsAttribute)) {
                $model->settingsAttribute->setOriginal([]);
            }
        });
    }

    /**
     * Get the settings attribute.
     *
     * @return \Rudnev\Settings\Structures\Container
     */
    public function getSettingsAttribute()
    {
        if (is_null($this->settingsAttribute)) {
            $this->settingsAttribute = new Container($this->exists ? $this->settings()->all() : []);

            $this->settingsAttribute->setDefault($this->settingsConfig['default'] ?? []);
        }

        return $this->settingsAttribute;
    }

    /**
     * Set the settings attribute.
     *
     * @param array|null $value
     */
    public function setSettingsAttribute($value)
    {
        if (is_null($value)) {
            $this->settingsAttribute = null;
        } else {
            $this->getSettingsAttribute()->substitute($value);
        }
    }

    /**
     * Get / set the specified value of the settings.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param string|iterable|null $key
     * @param mixed $default
     * @return mixed|\Rudnev\Settings\Contracts\RepositoryContract
     */
    public function settings($key = null, $default = null)
    {
        if (is_null($this->settingsRepo)) {
            $store = $this->settingsConfig['store'] ?? null;

            $this->settingsRepo = settings()->store($store)->scope($this, $this->settingsConfig ?? null);
        }

        if (is_null($key)) {
            return $this->settingsRepo;
        }

        if (is_iterable($key)) {
            return $this->settingsRepo->set($key);
        }

        return $this->settingsRepo->get($key, $default);
    }
}
