<?php

namespace Rudnev\Settings\Cache;

class CacheIndex
{
    /**
     * The tree of cache keys
     *
     * @var array
     */
    protected $tree = [
        // Child nodes
        0 => [],

        // Payload
        1 => null,
    ];

    /**
     * Determine if an item exists in the cache index.
     *
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        if (is_null($key)) {
            return false;
        }

        $keys = explode('.', $key);

        $node = &$this->tree;

        while ($key = array_shift($keys)) {
            if (! isset($node[0][$key])) {
                return false;
            }

            $node = &$node[0][$key];
        }

        return (bool) $node[1];
    }

    /**
     * Add an item in the cache index.
     *
     * @param string $key
     * @return void
     */
    public function add($key)
    {
        if (is_null($key)) {
            return;
        }

        $keys = explode('.', $key);

        $node = &$this->tree;

        while ($key = array_shift($keys)) {
            if (! isset($node[0][$key])) {
                $node[0][$key] = [0 => null, 1 => null];
            }

            $node = &$node[0][$key];
        }

        $node[1] = true;
    }

    /**
     * Remove an item from the cache index.
     *
     * @param string $key
     * @return bool
     */
    public function remove($key)
    {
        if (is_null($key)) {
            return false;
        }

        $keys = explode('.', $key);

        $node = &$this->tree;

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (! isset($node[0][$key])) {
                return false;
            }

            $node = &$node[0][$key];
        }

        unset($node[0][array_shift($keys)]);

        return true;
    }

    /**
     * Clear index
     *
     * @return void
     */
    public function clear()
    {
        $this->tree[0] = [];
        $this->tree[1] = null;
    }

    /**
     * Retrieve the list of cache keys
     *
     * @return array
     */
    public function keys()
    {
        $keys = [];

        $node = &$this->tree;

        $this->collectKeys($node, null, $keys);

        return $keys;
    }

    /**
     * Retrieve the list of child keys
     *
     * @param string $key
     * @return array
     */
    public function childKeys($key)
    {
        if (is_null($key)) {
            return [];
        }

        $keys = explode('.', $key);

        $node = &$this->tree;

        while ($k = array_shift($keys)) {
            if (! isset($node[0][$k])) {
                return [];
            }

            $node = &$node[0][$k];
        }

        $return = [];

        $this->collectKeys($node, null, $return);

        return array_map(function ($v) use ($key) {
            return $key.'.'.$v;
        }, $return);
    }

    /**
     * Collect cache keys into a list
     *
     * @param array $node
     * @param string $key
     * @param array $keys
     */
    protected function collectKeys(&$node, $key, array &$keys)
    {
        if (! is_null($key) && isset($node[1])) {
            $keys[] = ltrim($key, '.');
        }

        if (! isset($node[0])) {
            return;
        }

        foreach ($node[0] as $k => $v) {
            $this->collectKeys($v, $key.'.'.$k, $keys);
        }
    }
}