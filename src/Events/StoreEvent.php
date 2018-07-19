<?php

namespace Rudnev\Settings\Events;

class StoreEvent
{
    /**
     * The store name.
     *
     * @var string
     */
    protected $storeName;

    /**
     * Get the store name.
     *
     * @return string
     */
    public function getStoreName()
    {
        return $this->storeName;
    }

    /**
     * Set the store name.
     *
     * @param string $storeName
     */
    public function setStoreName($storeName)
    {
        $this->storeName = $storeName;
    }
}