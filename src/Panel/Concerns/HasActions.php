<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;

trait HasActions
{
    protected bool|Closure $redirectToHomeAction = false;

    /**
     * The home URL for the panel, which can be a string, Closure, or null.
     */
    protected string|Closure|null $homeUrl = '/';

    public function redirectToHomeAction(bool|Closure $condition = true, string $url = '/'): static
    {
        $this->redirectToHomeAction = $condition;
        $this->homeUrl = $url;

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
}
