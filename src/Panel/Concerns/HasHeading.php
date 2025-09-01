<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;

/**
 * Trait HasHeading
 *
 * Manages a heading which can be a string, Closure, explicit null,
 * or default fallback when not set.
 *
 * @method mixed evaluate(mixed $value)
 */
trait HasHeading
{
    /**
     * @var string|Closure|null|false
     *                                - false = not set
     *                                - null  = explicitly no heading
     *                                - string|Closure = custom heading
     */
    protected string|Closure|null|false $heading = false;

    /**
     * Tracks if heading() setter was called.
     */
    protected bool $headingWasSet = false;

    /**
     * Set the heading (string, Closure, or null) and mark as explicitly set.
     */
    public function heading(string|Closure|null $value): static
    {
        $this->headingWasSet = true;
        $this->heading = $value;

        return $this;
    }

    /**
     * Get the heading, or null if explicitly set to null, or fallback if not set.
     */
    public function getHeading(): ?string
    {
        // Grab original default via reflection
        $defaults = (new \ReflectionClass($this))->getDefaultProperties();
        $original = $defaults['heading'];

        // If current value differs from default, setter ran
        if ($this->heading !== $original) {
            // explicit hide?
            if ($this->heading === null) {
                return null;
            }

            // custom string or Closure
            return $this->evaluate($this->heading);
        }

        // Never touched: fallback translation
        return __('wirechat::chats.labels.heading');
    }
}
