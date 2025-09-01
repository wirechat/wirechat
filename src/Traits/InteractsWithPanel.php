<?php

namespace Wirechat\Wirechat\Traits;

use Wirechat\Wirechat\Exceptions\NoPanelProvidedException;
use Wirechat\Wirechat\Facades\Wirechat;
use Wirechat\Wirechat\Panel;

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
            $this->panel = Wirechat::getPanel($panel)->getId();
        } else {
            $this->panel = Wirechat::getDefaultPanel()->getId();
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
        return Wirechat::getPanel($this->panel);
    }
}
