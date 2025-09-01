<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;
use Wirechat\Wirechat\Support\Enums\EmojiPickerPosition;

trait HasEmojiPicker
{
    /**
     * Enable or disable emoji picker.
     */
    protected bool|Closure $hasEmojiPicker = false;

    /**
     * Emoji picker position (docked or floating).
     */
    protected EmojiPickerPosition|Closure|null $emojiPickerPosition = null;

    /**
     * Configure emoji picker.
     */
    public function emojiPicker(
        bool|Closure $condition = true,
        EmojiPickerPosition|string|Closure|null $position = EmojiPickerPosition::Floating
    ): static {
        $this->hasEmojiPicker = $condition;

        // Convert string to enum if needed
        if (is_string($position)) {
            $enum = EmojiPickerPosition::tryFrom($position);
            $this->emojiPickerPosition = $enum ?? EmojiPickerPosition::Floating;
        } else {
            $this->emojiPickerPosition = $position;
        }

        return $this;
    }

    public function hasEmojiPicker(): bool
    {
        return (bool) $this->evaluate($this->hasEmojiPicker);
    }

    public function emojiPickerPosition(): ?EmojiPickerPosition
    {
        return $this->evaluate($this->emojiPickerPosition);
    }
}
