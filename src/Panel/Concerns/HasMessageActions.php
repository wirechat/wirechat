<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;

trait HasMessageActions
{
    protected bool|Closure $deleteMessageActions = true;

    protected bool|Closure $messageReplyAction = true;

    public function deleteMessageActions(bool|Closure $condition = true): static
    {
        $this->deleteMessageActions = $condition;

        return $this;
    }

    public function messageReplyAction(bool|Closure $condition = true): static
    {
        $this->messageReplyAction = $condition;

        return $this;
    }

    public function hasDeleteMessageActions(): bool
    {
        return (bool) $this->evaluate($this->deleteMessageActions);
    }

    public function hasMessageReplyAction(): bool
    {
        return (bool) $this->evaluate($this->messageReplyAction);
    }
}
