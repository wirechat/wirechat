<?php

namespace Namu\WireChat\Traits;

use Namu\WireChat\Exceptions\NoPanelProvidedException;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Panel;

trait InteractsWithPanel
{
    public Panel|string|null $panel = null;

    /**
     * Set the panel from provided value or default.
     */
    public function setPanel(Panel|string|null $panel = null): void
    {
        if ($panel instanceof Panel) {
            $this->panel = $panel;
        } elseif (is_string($panel) && filled($panel)) {
            $this->panel = WireChat::getPanel($panel);
        } else {
            $this->panel = WireChat::getDefaultPanel();
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
        return $this->panel;
    }
}
