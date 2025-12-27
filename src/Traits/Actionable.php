<?php

namespace Wirechat\Wirechat\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Wirechat\Wirechat\Services\WirechatService;

/**
 * Trait Actionable
 */
trait Actionable
{
    /**
     * Actions - that were performed on this model
     */
    public function actions(): MorphMany
    {
        return $this->morphMany(WirechatService::actionModelClass(), 'actionable', 'actionable_type', 'actionable_id', 'id');
    }
}
