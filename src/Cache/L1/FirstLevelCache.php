<?php

declare(strict_types=1);

namespace Rudnev\Settings\Cache\L1;

class FirstLevelCache
{
    /**
     * The array of created regions.
     *
     * @var \Rudnev\Settings\Cache\L1\FirstLevelRegion[]
     */
    protected $regions = [];

    /**
     * Get the cache region by name.
     *
     * @param  string  $name
     * @return \Rudnev\Settings\Cache\L1\FirstLevelRegion
     */
    public function region(string $name): FirstLevelRegion
    {
        if (empty($this->regions[$name])) {
            $this->regions[$name] = new FirstLevelRegion($name);
        }

        return $this->regions[$name];
    }

    /**
     * Remove all items from the cache.
     *
     * @param  array  $except
     * @return void
     */
    public function flush(array $except = []): void
    {
        if ($except) {
            $regions = [];

            foreach ($except as $name) {
                $regions[$name] = $this->regions[$name];
            }

            $this->regions = $regions;
        } else {
            $this->regions = [];
        }
    }
}
