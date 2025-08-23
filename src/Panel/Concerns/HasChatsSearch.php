<?php

namespace Namu\WireChat\Panel\Concerns;

use Closure;

trait HasChatsSearch
{
    /**
     * Enable or disable chat list search.
     *
     * @var bool|Closure
     */
    protected bool|Closure $hasChatsSearch = false;

    public function chatsSearch(bool|Closure $condition = true): static
    {
        $this->hasChatsSearch = $condition;
        return $this;
    }

    public function hasChatsSearch(): bool
    {
        return (bool) $this->evaluate($this->hasChatsSearch);
    }
}
