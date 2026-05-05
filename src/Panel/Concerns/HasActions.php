<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\ComponentAttributeBag;

trait HasActions
{
    use InteractsWithIcons;

    protected bool|Closure $redirectToHomeAction = false;

    protected bool|Closure $showLeftActions = true;

    protected bool|Closure $showTextArea = true;

    protected bool|Closure $showRightActions = true;

    protected bool|Closure $enabledShiftEnter = true;

    /**
     * Icon for the redirect-to-home action.
     * - string: Blade component name, e.g. "wirechat::icons.logout"
     * - Htmlable: trusted raw SVG/HTML
     * - Closure: evaluated later
     */
    protected string|Htmlable|Closure|null $redirectToHomeActionIcon = 'wirechat::icons.logout';

    protected array|Closure $redirectToHomeActionIconAttributes = [];

    /**
     * The home URL for the panel, which can be a string, Closure, or null.
     */
    protected string|Closure|null $homeUrl = '/';

    public function redirectToHomeAction(
        bool|Closure $condition = true,
        string|Closure|null $url = '/',
        string|Htmlable|Closure|null $icon = null,
        array|Closure $iconAttributes = [],
    ): static {
        $this->redirectToHomeAction = $condition;
        $this->homeUrl = $url;

        // Icons setup
        if ($icon !== null) {
            $this->assertValidIcon($icon, 'redirectToHomeAction icon');
            $this->redirectToHomeActionIcon = $icon;
        }
        $this->redirectToHomeActionIconAttributes = $iconAttributes;

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

    public function homeUrl(string|Closure|null $url): static
    {
        $this->homeUrl = $url;

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

    /**
     * Gets the evaluated home URL for the panel.
     *
     * @return string|null The home URL, or null if not set.
     */
    public function getHomeUrl(): ?string
    {
        return $this->evaluate($this->homeUrl);
    }

    /***
    * Icons setup
    */
    public function redirectToHomeActionIconAttributes(): ComponentAttributeBag
    {
        $attrs = $this->evaluate($this->redirectToHomeActionIconAttributes);

        return new ComponentAttributeBag(is_array($attrs) ? $attrs : []);
    }

    public function redirectToHomeActionIcon(): string|Htmlable|null
    {
        return $this->resolveIcon($this->redirectToHomeActionIcon, 'redirectToHomeAction icon');
    }
}
