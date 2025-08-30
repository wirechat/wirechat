<?php

namespace Namu\WireChat\Panel\Concerns;

use Closure;

trait HasEmojiPicker
{
    /**
     * Enable or disable emoji picker .
     */
    protected bool|Closure $hasEmojiPicker = false;

    public function emojiPicker(bool|Closure $condition = true): static
    {
        $this->hasEmojiPicker = $condition;

        return $this;
    }

    public function hasEmojiPicker(): bool
    {
        return (bool) $this->evaluate($this->hasEmojiPicker);
    }
}
