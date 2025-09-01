<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;

/**
 * Trait HasLayout
 *
 * Supports setting a static or dynamic layout view.
 *
 * @method mixed evaluate(mixed $value)
 */
trait HasLayout
{
    /**
     * The layout view to use.
     */
    protected string|Closure|null $layout = 'wirechat::layouts.app';

    /**
     * Set the layout view.
     */
    public function layout(string|Closure|null $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    /**
     * Get the resolved layout view.
     */
    public function getLayout(): ?string
    {
        return $this->evaluate($this->layout);
    }

    /**
     * Check if a layout is set.
     */
    public function hasLayout(): bool
    {
        return filled($this->evaluate($this->layout));
    }
}
