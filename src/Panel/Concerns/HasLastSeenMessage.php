<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;

trait HasLastSeenMessage
{
    protected bool|Closure $isEnabled = false;

    public function canViewLastSeenMessage(bool|Closure $condition = true): static
    {
        $this->isEnabled = $condition;

        return $this;
    }
}
