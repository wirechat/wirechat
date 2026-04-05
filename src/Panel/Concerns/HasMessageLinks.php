<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;

trait HasMessageLinks
{
    protected bool|Closure $parseUrls = false;

    /**
     * Enable or disable URL parsing/linkification in message bodies.
     */
    public function parseUrls(bool|Closure $condition = true): static
    {
        $this->parseUrls = $condition;

        return $this;
    }

    public function canParseUrls(): bool
    {
        return (bool) $this->evaluate($this->parseUrls);
    }
}
