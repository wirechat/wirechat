<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;

trait HasSettings
{
    protected bool|Closure $hasSettings = false;

    public function settings(bool|Closure $condition = true): static
    {
        $this->hasSettings = $condition;

        return $this;
    }

    public function hasSettings(): bool
    {
        return (bool) $this->evaluate($this->hasSettings);
    }
}
