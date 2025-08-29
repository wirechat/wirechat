<?php

namespace Namu\WireChat\Livewire\Concerns;

use Livewire\Attributes\Computed;
use Namu\WireChat\Exceptions\NoPanelProvidedException;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Panel;

trait HasPanel
{
    public Panel|string|null $panel = null;

    /**
     * Resolve and assign the panel during mount.
     */
    public function mountHasPanel(): void
    {
        // If already set by Livewire/public property, use it
        $this->initializePanel($this->panel);
    }

    /**
     * Initialize the panel manually (can be called anywhere).
     *
     * @throws NoPanelProvidedException
     */
    public function initializePanel(Panel|string|null $panelId = null): void
    {
        if ($panelId instanceof Panel) {
            $this->panel = $panelId->getId();
        } elseif (is_string($panelId) && filled($panelId)) {
            $this->panel = $panelId;
        } else {
            $this->panel = WireChat::getDefaultPanel()?->getId();
        }

        if (! $this->panel || ! WireChat::getPanel($this->panel)) {
            throw NoPanelProvidedException::make();
        }

        app(\Namu\WireChat\PanelRegistry::class)->setCurrent($this->panel);
    }

    #[Computed(cache: false)]
    public function panel(): ?Panel
    {
        return $this->panel ? WireChat::getPanel($this->panel) : null;
    }
}
