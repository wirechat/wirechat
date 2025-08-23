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
     * Resolve and assign the panel ID during mount.
     */
    public function mountHasPanel(...$params): void
    {
        $panelParam = collect($params)->first(fn ($param) => $param instanceof Panel || is_string($param));
        $this->initializePanel($panelParam);
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

        app('wirechatPanelRegistry')->setCurrent($this->panel);

    }

    #[Computed(cache: true)]
    public function panel(): ?Panel
    {
        return $this->panel ? WireChat::getPanel($this->panel) : null;
    }
}
