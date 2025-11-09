<?php

namespace Wirechat\Wirechat;

use Illuminate\Support\ServiceProvider;

abstract class PanelProvider extends ServiceProvider
{
    abstract public function panel(Panel $panel): Panel;

    public function register(): void
    {
        $panel = $this->panel(Panel::make());
<<<<<<< HEAD
=======

        Log::debug('Registering panel via provider', ['id' => $panel->getId()]);

>>>>>>> origin/0.3x
        app(PanelRegistry::class)->register($panel);
    }
}
