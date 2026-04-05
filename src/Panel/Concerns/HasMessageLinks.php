<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;

trait HasMessageLinks
{
    protected bool|Closure $parseMessageUrls = false;

    /**
     * Enable or disable URL parsing/linkification in message bodies.
     */
    public function parseMessageUrls(bool|Closure $condition = true): static
    {
        $this->parseMessageUrls = $condition;

        return $this;
    }

    public function canParseMessageUrls(): bool
    {
        return (bool) $this->evaluate($this->parseMessageUrls);
    }
}
