<?php

namespace Wirechat\Wirechat;

use Illuminate\Support\ServiceProvider;

abstract class PanelProvider extends ServiceProvider
{
    abstract public function panel(Panel $panel): Panel;

    public function register(): void
    {
        $panel = $this->panel(Panel::make());
        app(PanelRegistry::class)->register($panel);
    }
}
