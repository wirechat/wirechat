<?php

namespace Wirechat\Wirechat\Livewire\Chats\Settings;

use Wirechat\Wirechat\Livewire\Concerns\HasPanel;
use Wirechat\Wirechat\Livewire\Concerns\ModalComponent;
use Wirechat\Wirechat\Livewire\Concerns\Widget;

abstract class SettingsPage extends ModalComponent
{
    use HasPanel;
    use Widget;

    public function mount(): void
    {
        abort_unless(auth()->check(), 401);

        $this->initializePanel($this->panel);

        abort_unless($this->panel()?->hasSettings(), 404);
    }

    public static function modalAttributes(): array
    {
        return [
            'closeOnEscape' => true,
            'closeOnEscapeIsForceful' => false,
            'destroyOnClose' => true,
            'closeOnClickAway' => true,
        ];
    }
}
