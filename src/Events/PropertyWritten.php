<?php

declare(strict_types=1);

namespace Rudnev\Settings\Events;

class PropertyWritten extends StoreEvent
{
    /**
     * The key of the item.
     *
     * @var string
     */
    public $key;

    /**
     * The value that was written.
     *
     * @var mixed
     */
    public $value;

    /**
     * PropertyWritten constructor.
     *
     * @param  string  $key
     * @param  mixed  $value
     */
    public function __construct(string $key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }
}
