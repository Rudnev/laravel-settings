<?php

namespace Rudnev\Settings\Traits;

use ArrayObject;

trait HasSettings
{
    /**
     * The settings repository instance.
     *
     * @var \Rudnev\Settings\Contracts\RepositoryContract
     */
    protected $settingsRepo;

    /**
     * The original state of the settings.
     *
     * @var array
     */
    protected $settingsOriginal;

    /**
     * The state of the settings.
     *
     * @var array
     */
    protected $settingsAttribute;

    /**
     * The "booting" method of the trait.
     *
     * @return void
     */
    public static function bootHasSettings()
    {
        static::saved(function ($model) {
            // return if settings are not affected
            if (is_null($model->settingsOriginal) && is_null($model->settingsAttribute)) {
                return;
            }

            $old = array_dot((array) $model->settingsOriginal);
            $new = array_dot((array) $model->settingsAttribute);

            // removing settings
            if (! empty($old)) {
                $forget = array_keys(array_diff_key($old, $new));
                $model->settings()->forget($forget);
            }

            // saving settings
            if (! empty($new)) {
                $changes = array_diff_assoc($new, $old);

                $model->settings()->set($changes);
            }

            $model->settingsOriginal = (array) $model->settingsAttribute;
        });

        static::deleting(function ($model) {
            if (method_exists($model, 'isForceDeleting') && ! $model->isForceDeleting()) {
                return;
            }

            $model->settings()->flush();
        });
    }

    /**
     * Get the settings attribute.
     *
     * @return array
     */
    public function getSettingsAttribute()
    {
        if ($this->exists && is_null($this->settingsOriginal)) {
            $this->settingsOriginal = $this->settings()->all();

            if (is_null($this->settingsAttribute)) {
                $this->settings = $this->settingsOriginal;
            }
        }

        if (is_null($this->settingsAttribute)) {
            $this->settings = [];
        }

        return $this->settingsAttribute;
    }

    /**
     * Set the settings attribute.
     *
     * @param $value
     */
    public function setSettingsAttribute($value)
    {
        if ($this->exists && is_null($this->settingsOriginal)) {
            $this->settingsOriginal = $this->settings()->all();
        }

        if (is_null($value)) {
            $this->settingsAttribute = $value;
        } else {
            $this->settingsAttribute = new class($value) extends ArrayObject {
                protected $default = [];

                public function offsetGet($key)
                {
                    return $this->offsetExists($key) ? parent::offsetGet($key) : value(array_get($this->default, $key));
                }

                public function setDefault($value)
                {
                    $this->default = $value;
                }
            };

            $this->settingsAttribute->setDefault($this->settingsConfig['default'] ?? []);
        }
    }

    /**
     * @param null $key
     * @param null $default
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
