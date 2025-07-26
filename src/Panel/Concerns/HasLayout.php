<?php

namespace Namu\WireChat\Panel\Concerns;

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
     *
     * @var string|Closure|null
     */
    protected string|Closure|null $layout = null;

    /**
     * Set the layout view.
     *
     * @param  string|Closure|null  $layout
     * @return static
     */
    public function layout(string|Closure|null $layout): static
    {
        $this->layout = $layout;
        return $this;
    }

    /**
     * Get the resolved layout view.
     *
     * @return string|null
     */
    public function getLayout(): ?string
    {
        return $this->evaluate($this->layout);
    }

    /**
     * Check if a layout is set.
     *
     * @return bool
     */
    public function hasLayout(): bool
    {
        return filled($this->evaluate($this->layout));
    }
}
