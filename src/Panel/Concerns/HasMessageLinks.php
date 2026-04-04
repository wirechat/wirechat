<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;

trait HasMessageLinks
{
    protected bool|Closure $linkifyMessages = false;

    /**
     * Enable or disable URL parsing/linkification in message bodies.
     */
    public function linkifyMessages(bool|Closure $condition = true): static
    {
        $this->linkifyMessages = $condition;

        return $this;
    }

    public function canLinkifyMessages(): bool
    {
        return (bool) $this->evaluate($this->linkifyMessages);
    }
}
