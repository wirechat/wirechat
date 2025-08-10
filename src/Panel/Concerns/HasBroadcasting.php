<?php

namespace Namu\WireChat\Panel\Concerns;

use Closure;

trait HasBroadcasting
{
    protected bool|Closure $hasBroadcasting = true;

    protected string|Closure $messagesQueue = 'messages';

    protected string|Closure $eventsQueue = 'default';

    public function broadcasting(bool|Closure $condition = true): static
    {
        $this->hasBroadcasting = $condition;
        return $this;
    }

    public function hasBroadcasting(): bool
    {
        return (bool) $this->evaluate($this->hasBroadcasting);
    }

    public function getMessagesQueue(): string
    {
        return (string) $this->evaluate($this->messagesQueue);
    }

    public function getEventsQueue(): string
    {
        return (string) $this->evaluate($this->eventsQueue);
    }

    public function messagesQueue(string|Closure $queue): static
    {
        $this->messagesQueue = $queue;
        return $this;
    }

    public function eventsQueue(string|Closure $queue): static
    {
        $this->eventsQueue = $queue;
        return $this;
    }
}
