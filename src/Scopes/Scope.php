<?php

declare(strict_types=1);

namespace Rudnev\Settings\Scopes;

class Scope
{
    /**
     * The hash of this scope.
     *
     * @var string
     */
    public $hash;

    /**
     * Scope constructor.
     *
     * @param string $identifier
     * @return void
     */
    public function __construct(string $identifier = 'default')
    {
        $this->hash = $identifier;
    }
}