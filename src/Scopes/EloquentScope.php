<?php

declare(strict_types=1);

namespace Rudnev\Settings\Scopes;

use Illuminate\Database\Eloquent\Model;

class EloquentScope extends EntityScope
{
    /**
     * EloquentScope constructor.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function __construct(Model $model)
    {
        $this->entityId = (string) $model->getKey();
        $this->entityClass = $model->getMorphClass();
        $this->hash = str_replace('\\', '.', strtolower(get_class($model)).'_'.$this->entityId);
    }
}
