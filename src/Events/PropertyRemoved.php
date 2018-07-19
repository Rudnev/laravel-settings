<?php

namespace Rudnev\Settings\Events;

class PropertyRemoved extends StoreEvent
{
    /**
     * The key of the item.
     *
     * @var string
     */
    public $key;

    /**
     * PropertyRemoved constructor.
     *
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = $key;
    }
}