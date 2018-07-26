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
     * The scope.
     *
     * @var
     */
    protected $scope = '';

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
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * Set the scope.
     *
     * @param string $scope
     */
    public function setScope($scope)
    {
        $this->scope = $scope;
    }
}
