<?php

namespace Namu\WireChat\Panel\Concerns;

use Closure;

/**
 * Trait HasGroups
 *
 * Enables group chat functionality with support for closures
 * and configuration of group limits.
 *
 * @method mixed evaluate(mixed $value)
 */
trait HasGroups
{
    /**
     * Whether group functionality is enabled.
     *
     * @var bool|Closure
     */
    protected bool|Closure $hasGroups = false;

    /**
     * The maximum number of members allowed in a group.
     *
     * @var int
     */
    protected int $maxGroupMembers = 50;

    /**
     * Enable or disable groups.
     *
     * @param  bool|Closure  $condition
     * @return static
     */
    public function groups(bool|Closure $condition = true): static
    {
        $this->hasGroups = $condition;
        return $this;
    }

    /**
     * Check if groups are enabled.
     *
     * @return bool
     */
    public function hasGroups(): bool
    {
        return (bool) $this->evaluate($this->hasGroups);
    }

    /**
     * Set the maximum number of group members.
     *
     * @param  int  $max
     * @return static
     */
    public function maxGroupMembers(int $max): static
    {
        $this->maxGroupMembers = $max;
        return $this;
    }

    /**
     * Get the maximum number of group members.
     *
     * @return int
     */
    public function getMaxGroupMembers(): int
    {
        return $this->maxGroupMembers;
    }
}
