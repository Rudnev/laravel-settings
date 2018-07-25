<?php

if (! function_exists('settings')) {
    /**
     * Get / set the specified settings value.
     *
     * If an array is passed as the key, we will assume you want to set an array of values.
     *
     * @param  string|iterable $key
     * @param  mixed|null $default
     * @return mixed|\Rudnev\Settings\SettingsManager
     */
    function settings($key = null, $default = null)
    {
        if (is_null($key)) {
            return app('settings');
        }

        if (is_iterable($key)) {
            return app('settings')->set($key);
        }

        return app('settings')->get($key, $default);
    }
}