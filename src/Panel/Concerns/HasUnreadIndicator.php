<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;
use Wirechat\Wirechat\Support\Enums\UnreadIndicatorType;
use Wirechat\Wirechat\Support\Enums\UnReadType;

trait HasUnreadIndicator
{
    /**
     * Enable or disable the unread indicator.
     */
    protected bool|Closure $hasUnreadIndicator = true;

    /**
     * Configure how unread state should be shown in the chats list.
     */
    protected UnreadIndicatorType|UnReadType|string|Closure|null $unreadIndicatorType = UnreadIndicatorType::Dot;

    public function unreadIndicator(
        bool|Closure $condition = true,
        UnreadIndicatorType|UnReadType|string|Closure|null $type = UnreadIndicatorType::Dot
    ): static {
        $this->hasUnreadIndicator = $condition;
        $this->unreadIndicatorType = $type;

        return $this;
    }

    public function hasUnreadIndicator(): bool
    {
        return (bool) $this->evaluate($this->hasUnreadIndicator);
    }

    public function getUnreadIndicatorType(): UnreadIndicatorType
    {
        $type = $this->evaluate($this->unreadIndicatorType);

        return $this->normalizeUnreadIndicatorType($type);
    }

    /**
     * @deprecated Use unreadIndicator() instead.
     */
    public function unReadMessages(
        bool|Closure $condition = true,
        UnreadIndicatorType|UnReadType|string|Closure|null $type = UnReadType::Dot
    ): static {
        return $this->unreadIndicator($condition, $type);
    }

    /**
     * @deprecated Use hasUnreadIndicator() instead.
     */
    public function hasUnReadMessages(): bool
    {
        return $this->hasUnreadIndicator();
    }

    /**
     * @deprecated Use getUnreadIndicatorType() instead.
     */
    public function getUnReadMessagesType(): UnReadType
    {
        return UnReadType::from($this->getUnreadIndicatorType()->value);
    }

    protected function normalizeUnreadIndicatorType(mixed $type): UnreadIndicatorType
    {
        if ($type instanceof UnreadIndicatorType) {
            return $type;
        }

        if ($type instanceof UnReadType) {
            return UnreadIndicatorType::from($type->value);
        }

        if (is_string($type)) {
            return UnreadIndicatorType::tryFrom($type) ?? UnreadIndicatorType::Dot;
        }

        return UnreadIndicatorType::Dot;
    }
}
