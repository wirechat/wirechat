<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\ComponentAttributeBag;

trait HasActions
{
    use InteractsWithIcons;

    protected bool|Closure $redirectToHomeAction = false;

    /**
     * Icon for the redirect-to-home action.
     * - string: Blade component name, e.g. "wirechat::icons.logout"
     * - Htmlable: trusted raw SVG/HTML
     * - Closure: evaluated later
     */
    protected string|Htmlable|Closure|null $redirectToHomeActionIcon = 'wirechat::icons.home-01';

    /**
     * Additional attributes for the redirect-to-home action icon.
     * Can be an array or a Closure that returns an array.
     */
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

    /**
     * Sets the home URL for the panel.
     *
     * @param  string|Closure|null  $url  The home URL or a Closure that returns it.
     */
    public function homeUrl(string|Closure|null $url): static
    {
        $this->homeUrl = $url;

        return $this;
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
