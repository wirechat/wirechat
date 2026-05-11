<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\View\ComponentAttributeBag;

trait HasChatActions
{
    use InteractsWithIcons;

    protected bool|Closure $createChatAction = false;

    protected string|Htmlable|Closure|null $createChatActionIcon = 'wirechat::icons.message-plus-circle';

    protected array|Closure $createChatActionIconAttributes = [];

    protected bool|Closure $clearChatAction = true;

    protected string|Htmlable|Closure|null $clearChatActionIcon = 'wirechat::icons.trash';

    protected array|Closure $clearChatActionIconAttributes = [];

    protected bool|Closure $deleteChatAction = true;

    protected string|Htmlable|Closure|null $deleteChatActionIcon = 'wirechat::icons.trash';

    protected array|Closure $deleteChatActionIconAttributes = [];

    public function createChatAction(
        bool|Closure $condition = true,
        string|Htmlable|Closure|null $icon = null,
        array|Closure $iconAttributes = [],
    ): static {
        $this->createChatAction = $condition;

        if ($icon !== null) {
            $this->assertValidIcon($icon, 'createChatAction icon');
            $this->createChatActionIcon = $icon;
        }

        $this->createChatActionIconAttributes = $iconAttributes;

        return $this;
    }

    public function clearChatAction(
        bool|Closure $condition = true,
        string|Htmlable|Closure|null $icon = null,
        array|Closure $iconAttributes = [],
    ): static {
        $this->clearChatAction = $condition;

        if ($icon !== null) {
            $this->assertValidIcon($icon, 'clearChatAction icon');
            $this->clearChatActionIcon = $icon;
        }

        $this->clearChatActionIconAttributes = $iconAttributes;

        return $this;
    }

    public function deleteChatAction(
        bool|Closure $condition = true,
        string|Htmlable|Closure|null $icon = null,
        array|Closure $iconAttributes = [],
    ): static {
        $this->deleteChatAction = $condition;

        if ($icon !== null) {
            $this->assertValidIcon($icon, 'deleteChatAction icon');
            $this->deleteChatActionIcon = $icon;
        }

        $this->deleteChatActionIconAttributes = $iconAttributes;

        return $this;
    }

    /***
     * Action check
     */
    public function hasCreateChatAction(): bool
    {
        return (bool) $this->evaluate($this->createChatAction);
    }

    public function hasClearChatAction(): bool
    {
        return (bool) $this->evaluate($this->clearChatAction);
    }

    public function hasDeleteChatAction(): bool
    {
        return (bool) $this->evaluate($this->deleteChatAction);
    }

    /**
     * Icons
     */
    public function createChatActionIcon(): string|Htmlable|null
    {
        return $this->resolveIcon($this->createChatActionIcon, 'createChatAction icon');
    }

    public function clearChatActionIcon(): string|Htmlable|null
    {
        return $this->resolveIcon($this->clearChatActionIcon, 'clearChatAction icon');
    }

    public function deleteChatActionIcon(): string|Htmlable|null
    {
        return $this->resolveIcon($this->deleteChatActionIcon, 'deleteChatAction icon');
    }

    /**
     * Icon attributes (return arrays so Blade can wrap/merge them safely)
     */
    public function createChatActionIconAttributes(): ComponentAttributeBag
    {
        $attrs = $this->evaluate($this->createChatActionIconAttributes);

        return new ComponentAttributeBag(is_array($attrs) ? $attrs : []);

    }

    public function clearChatActionIconAttributes(): ComponentAttributeBag
    {
        $attrs = $this->evaluate($this->clearChatActionIconAttributes);

        return new ComponentAttributeBag(is_array($attrs) ? $attrs : []);

    }

    public function deleteChatActionIconAttributes(): ComponentAttributeBag
    {
        $attrs = $this->evaluate($this->deleteChatActionIconAttributes);

        return new ComponentAttributeBag(is_array($attrs) ? $attrs : []);

    }
}
