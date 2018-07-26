<?php

namespace Rudnev\Settings\Events;

class PropertyReceived extends StoreEvent
{
    /**
     * The key of the item.
     *
     * @var string
     */
    public $key;

    /**
     * The value that was retrieved.
     *
     * @var mixed
     */
    public $value;

    /**
     * PropertyReceived constructor.
     *
     * @param string $key
     * @param mixed $value
     */
    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }
}
