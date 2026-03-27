<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;
use Wirechat\Wirechat\Support\Enums\UnReadType;

trait HasUnReadMessages
{
    /**
     * Enable or disable unread messages indicator.
     */
    protected bool|Closure $hasUnReadMessages = true;

    /**
     * Configure how unread messages should be shown.
     */
    protected UnReadType|string|Closure|null $unReadMessagesType = UnReadType::Dot;

    public function unReadMessages(
        bool|Closure $condition = true,
        UnReadType|string|Closure|null $type = UnReadType::Dot
    ): static {
        $this->hasUnReadMessages = $condition;
        $this->unReadMessagesType = $type;

        return $this;
    }

    public function hasUnReadMessages(): bool
    {
        return (bool) $this->evaluate($this->hasUnReadMessages);
    }

    public function getUnReadMessagesType(): UnReadType
    {
        $type = $this->evaluate($this->unReadMessagesType);

        if ($type instanceof UnReadType) {
            return $type;
        }

        if (is_string($type)) {
            return UnReadType::tryFrom($type) ?? UnReadType::Dot;
        }

        return UnReadType::Dot;
    }
}
