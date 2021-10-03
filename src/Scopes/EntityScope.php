<?php

declare(strict_types=1);

namespace Rudnev\Settings\Scopes;

class EntityScope extends Scope
{
    /**
     * The name of the entity class.
     *
     * @var string
     */
    public $entityClass;

    /**
     * The identifier of entity.
     *
     * @var string
     */
    public $entityId;

    /**
     * EntityScope constructor.
     *
     * @param  string  $entityClass
     * @param  string  $entityId
     * @return void
     */
    public function __construct(string $entityClass, string $entityId)
    {
        $this->entityId = $entityId;
        $this->entityClass = $entityClass;
        $this->hash = str_replace('\\', '.', strtolower($entityClass).'_'.$entityId);
    }
}
