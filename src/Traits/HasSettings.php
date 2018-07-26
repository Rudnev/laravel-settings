<?php

namespace Rudnev\Settings\Traits;

trait HasSettings
{
    /**
     * The settings repository instance.
     *
     * @var \Rudnev\Settings\Contracts\RepositoryContract
     */
    protected $settingsRepo;

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
