<?php

namespace Rudnev\Settings\Events;

use Rudnev\Settings\Scopes\Scope;

class StoreEvent
{
    /**
     * The store name.
     *
     * @var string
     */
    protected $storeName;

    /**
     * The scope.
     *
     * @var \Rudnev\Settings\Scopes\Scope
     */
    protected $scope;

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

    /**
     * Get the scope.
     *
     * @return \Rudnev\Settings\Scopes\Scope
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Set the scope.
     *
     * @param \Rudnev\Settings\Scopes\Scope $scope
     */
    public function setScope(Scope $scope)
    {
        $this->scope = $scope;
    }
}
