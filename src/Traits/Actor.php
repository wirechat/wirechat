<?php

namespace Wirechat\Wirechat\Traits;

use Wirechat\Wirechat\Facades\Wirechat;

/**
 * Trait Actionable
 */
trait Actor
{
    /**
     * ----------------------------------------
     * ----------------------------------------
     * Actions - that were performed by this model
     * --------------------------------------------
     */
    public function performedActions()
    {
        return $this->morphMany(Wirechat::actionModelClass(), 'actor', 'actor_type', 'actor_id', 'id');
    }
}
