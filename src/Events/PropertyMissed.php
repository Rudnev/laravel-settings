<?php

declare(strict_types=1);

namespace Rudnev\Settings\Events;

class PropertyMissed extends StoreEvent
{
    /**
     * The key of the item.
     *
     * @var string
     */
    public $key;

    /**
     * PropertyMissed constructor.
     *
     * @param string $key
     */
    public function __construct(string $key)
    {
        $this->key = $key;
    }
}
