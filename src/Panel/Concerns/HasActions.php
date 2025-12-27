<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;

trait HasActions
{
    protected bool|Closure $redirectToHomeAction = false;

    protected bool|Closure $showLeftActions = true;

    protected bool|Closure $showTextArea = true;

    protected bool|Closure $showRightActions = true;

    protected bool|Closure $enabledShiftEnter = true;

    public function redirectToHomeAction(bool|Closure $condition = true): static
    {
        $this->redirectToHomeAction = $condition;

        return $this;
    }

    public function hasRedirectToHomeAction(): bool
    {
        return (bool) $this->evaluate($this->redirectToHomeAction);
    }

    public function showLeftActions(bool|Closure $condition = true): static
    {
        $this->showLeftActions = $condition;

        return $this;
    }

    public function isShowLeftActions(): bool
    {
        return (bool) $this->evaluate($this->showLeftActions);
    }

    public function showTextArea(bool|Closure $condition = true): static
    {
        $this->showTextArea = $condition;

        return $this;
    }

    public function isShowTextArea(): bool
    {
        return (bool) $this->evaluate($this->showTextArea);
    }

    public function showRightActions(bool|Closure $condition = true): static
    {
        $this->showRightActions = $condition;

        return $this;
    }

    public function isShowRightActions(): bool
    {
        return (bool) $this->evaluate($this->showRightActions);
    }

    public function enableShiftEnter(bool|Closure $condition = true): static
    {
        $this->enabledShiftEnter = $condition;

        return $this;
    }

    public function isEnabledShiftEnter(): bool
    {
        return (bool) $this->evaluate($this->enabledShiftEnter);
    }
}
