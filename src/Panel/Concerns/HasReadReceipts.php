<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;

trait HasReadReceipts
{
    protected bool|Closure $isEnabled = false;

    public function readReceipts(bool|Closure $condition = true): static
    {
        $this->isEnabled = $condition;

        return $this;
    }

    public function hasReadReceipts(): bool
    {
        return (bool) $this->evaluate($this->isEnabled);
    }
}
