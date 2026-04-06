<?php

namespace Wirechat\Wirechat\Traits;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Wirechat\Wirechat\Facades\Wirechat;

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
        return $this->morphMany(Wirechat::actionModelClass(), 'actionable', 'actionable_type', 'actionable_id', 'id');
    }
}
