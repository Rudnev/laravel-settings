<?php

declare(strict_types=1);

namespace Rudnev\Settings\Structures;

use ArrayObject;
use TypeError;

class Container extends ArrayObject
{
    /**
     * Default values.
     *
     * @var array
     */
    protected $default = [];

    /**
     * Get the default value.
     *
     * @param string $key
     * @return mixed
     */
    public function getDefault(string $key = null)
    {
        if (isset($key)) {
            return value(array_get($this->default, $key));
        }

        return $this->default;
    }

    /**
     * Set the default value.
     *
     * @param array|string $key
     * @param mixed $value
     * @return void
     */
    public function setDefault($key, $value = null): void
    {
        if (is_array($key)) {
            $this->default = array_merge($this->default, $key);
        } else {
            $this->default[$key] = $value;
        }
    }

    /**
     * Remove the default value.
     *
     * @param array|string $key
     * @return void
     */
    public function forgetDefault($key = null): void
    {
        if (is_null($key)) {
            $this->default = [];

            return;
        }

        if (is_array($key)) {
            foreach ($key as $item) {
                unset($this->default[$item]);
            }

            return;
        }

        unset($this->default[$key]);
    }

    /**
     * Retrieve an item by key.
     *
     * @param mixed $key
     * @return mixed
     */
    public function offsetGet($key)
    {
        $this->checkKeyType($key);

        return $this->offsetExists($key) ? parent::offsetGet($key) : $this->getDefault($key);
    }

    /**
     * Store an item.
     *
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    public function offsetSet($key, $value)
    {
        $this->checkKeyType($key);

        parent::offsetSet($key, $value);
    }

    /**
     * Check the type of key.
     *
     * @param mixed $key
     * @return void
     */
    protected function checkKeyType(&$key)
    {
        if (! is_string($key)) {
            throw new TypeError('Key must be of the type string, '.gettype($key).' given.');
        }
    }
}
