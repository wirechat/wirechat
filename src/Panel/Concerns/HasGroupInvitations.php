<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;

/**
 * @method mixed evaluate(mixed $value)
 */
trait HasGroupInvitations
{
    protected bool|Closure $hasGroupInvitations = false;

    protected string|Closure|null $invitePageLayout = 'wirechat::layouts.app';

    public function groupInvitations(bool|Closure $condition = true): static
    {
        $this->hasGroupInvitations = $condition;

        return $this;
    }

    public function hasGroupInvitations(): bool
    {
        return (bool) $this->evaluate($this->hasGroupInvitations);
    }

    public function invitePageLayout(string|Closure|null $layout): static
    {
        $this->invitePageLayout = $layout;

        return $this;
    }

    public function getInvitePageLayout(): ?string
    {
        return $this->evaluate($this->invitePageLayout);
    }
}
