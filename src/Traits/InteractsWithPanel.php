<?php

namespace Namu\WireChat\Traits;

use Namu\WireChat\Exceptions\NoPanelProvidedException;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Panel;

trait InteractsWithPanel
{
    public ?string $panel;

    /**
     * Set the panel from provided value or default.
     *
     * @throws NoPanelProvidedException
     * @throws \Exception
     */
    public function resolvePanel(?string $panel = null): void
    {
        if (is_string($panel) && filled($panel)) {
            $this->panel = WireChat::getPanel($panel)->getId();
        } else {
            $this->panel = WireChat::getDefaultPanel()->getId();
        }

        if (! $this->panel) {
            throw NoPanelProvidedException::make();
        }
    }

    /**
     * Get the resolved panel instance.
     */
    public function getPanel(): ?Panel
    {
        return WireChat::getPanel($this->panel);
    }
}
