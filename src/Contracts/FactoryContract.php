<?php

namespace Rudnev\Settings\Contracts;

interface FactoryContract
{
    /**
     * Get a settings repository instance by name.
     *
     * @param  string|null $name
     * @return \Rudnev\Settings\Contracts\RepositoryContract
     */
    public function store($name = null);
}