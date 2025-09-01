<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;

trait HasActions
{
    protected bool|Closure $newChatAction = false;

    protected bool|Closure $newGroupAction = false;

    protected bool|Closure $redirectToHomeAction = false;

    public function newChatAction(bool|Closure $condition = true): static
    {
        $this->newChatAction = $condition;

        return $this;
    }

    public function newGroupAction(bool|Closure $condition = true): static
    {
        $this->newGroupAction = $condition;

        return $this;
    }

    public function redirectToHomeAction(bool|Closure $condition = true): static
    {
        $this->redirectToHomeAction = $condition;

        return $this;
    }

    public function hasNewChatAction(): bool
    {
        return (bool) $this->evaluate($this->newChatAction);
    }

    public function hasNewGroupAction(): bool
    {
        return (bool) $this->evaluate($this->newGroupAction);
    }

    public function hasRedirectToHomeAction(): bool
    {
        return (bool) $this->evaluate($this->redirectToHomeAction);
    }
}
