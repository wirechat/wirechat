<?php

namespace Namu\WireChat\Livewire\Concerns;

use Livewire\Attributes\Computed;
use Namu\WireChat\Exceptions\NoPanelProvidedException;
use Namu\WireChat\Facades\WireChat;
use Namu\WireChat\Panel;

trait HasPanel
{
    // Initialize with null to avoid uninitialized property error
    public Panel|string|null $panel = null;

    /**
     * Resolve and assign the panel ID during mount.
     *
     * @param mixed ...$params
     */
    public function mountHasPanel(...$params): void
    {
        $panelParam = collect($params)->first(fn ($param) => $param instanceof Panel || is_string($param));

        if ($panelParam instanceof Panel) {
            $this->panel = $panelParam->getId();
        } elseif (is_string($panelParam) && filled($panelParam)) {
            $this->panel = $panelParam;
        } else {
            $this->panel = WireChat::getDefaultPanel()?->getId();
        }

        if (! $this->panel || ! WireChat::getPanel($this->panel)) {
            throw NoPanelProvidedException::make();
        }

    }

    #[Computed(cache: true)]
    public function panel(): ?Panel
    {
        return $this->panel ? WireChat::getPanel($this->panel) : null;

    }
}
