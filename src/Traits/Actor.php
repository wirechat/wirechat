<?php

namespace Wirechat\Wirechat\Traits;

use Wirechat\Wirechat\Services\WirechatService;

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
        return $this->morphMany(WirechatService::actionModelClass(), 'actor', 'actor_type', 'actor_id', 'id');
    }
}
