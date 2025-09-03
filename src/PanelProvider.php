<?php

namespace Wirechat\Wirechat;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

abstract class PanelProvider extends ServiceProvider
{
    abstract public function panel(Panel $panel): Panel;

    public function register(): void
    {
        $panel = $this->panel(Panel::make());

        Log::info('Registering panel via provider', ['id' => $panel->getId()]);
        app(PanelRegistry::class)->register($panel);
    }
}
