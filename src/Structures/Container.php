<?php

declare(strict_types=1);

namespace Rudnev\Settings\Structures;

use Countable;
use TypeError;
use ArrayAccess;
use ArrayIterator;
use JsonSerializable;
use IteratorAggregate;
use Illuminate\Support\Arr;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Contracts\Support\Arrayable;

class Container implements ArrayAccess, Arrayable, Countable, IteratorAggregate, Jsonable, JsonSerializable
{
    /**
     * The original state.
     *
     * @var array
     */
    protected $original = [];

    /**
     * Default values.
     *
     * @var array
     */
    protected $default = [];

    /**
     * The items contained in the container.
     *
     * @var array
     */
    protected $items = [];

    /**
     * Create a new container instance.
     *
     * @param array $input
     */
    public function __construct(array $input = [])
    {
        $this->original = Arr::dot($input);

        $this->fill($input);
    }

    /**
     * Fill the container with an array of items.
     *
     * @param array $items
     */
    public function fill(array $items)
    {
        foreach ($items as $key => $value) {
            $this->offsetSet($key, $value);
        }
    }

    /**
     * Substitute the array of items.
     *
     * @param array $items
     */
    public function substitute(array $items)
    {
        $this->items = [];

        $this->fill($items);
    }

    /**
     * Get the original state.
     *
     * @return array
     */
    public function getOriginal(): array
    {
        return $this->original;
    }

    /**
     * Set the original state.
     *
     * @param array $original
     */
    public function setOriginal(array $original)
    {
        $this->original = $original;
    }

    /**
     * Get the default value.
     *
     * @param string $key
     * @return mixed
     */
    public function getDefault(string $key = null)
    {
        return isset($key)
            ? value(Arr::get($this->default, $key))
            : $this->default;
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
     * Determine if an item exists at an offset.
     *
     * @param mixed $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return array_key_exists($key, $this->items);
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

        return Arr::get($this->items, $key, $this->getDefault($key));
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

        Arr::set($this->items, $key, $value);
    }

    /**
     * Unset the item at a given offset.
     *
     * @param string $key
     * @return void
     */
    public function offsetUnset($key)
    {
        unset($this->items[$key]);
    }

    /**
     * Count the number of items in the container.
     *
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * Get updated items.
     *
     * @return array
     */
    public function getUpdated()
    {
        return array_diff_assoc(
            Arr::dot($this->items),
            $this->original
        );
    }

    /**
     * Get deleted items.
     *
     * @return array
     */
    public function getDeleted()
    {
        return array_diff_key(
            $this->original,
            Arr::dot($this->items)
        );
    }

    /**
     * Sync the original state with the current.
     *
     * @return void
     */
    public function sync()
    {
        $this->original = Arr::dot($this->items);
    }

    /**
     * Get an iterator for the items.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Get the items as a plain array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->items;
    }

    /**
     * Get the items as JSON.
     *
     * @param int $options
     * @return string
     */
    public function toJson($options = 0)
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the object into something JSON serializable.
     *
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->items;
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
