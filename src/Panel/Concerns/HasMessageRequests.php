<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;

trait HasMessageRequests
{
    protected bool|Closure $hasMessageRequests = false;

    public function messageRequests(bool|Closure $condition = true): static
    {
        $this->hasMessageRequests = $condition;

        return $this;
    }

    public function hasMessageRequests(): bool
    {
        return (bool) $this->evaluate($this->hasMessageRequests);
    }
}
