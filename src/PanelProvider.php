<?php

namespace Namu\WireChat;

use Illuminate\Support\ServiceProvider;

abstract class PanelProvider extends ServiceProvider
{
    abstract public function panel(Panel $panel): Panel;

    public function register(): void
    {
        $panel = $this->panel(Panel::make());
        Log::info('Registering panel via provider', ['id' => $panel->getId()]);
        app('wirechatPanelRegistry')->register($panel);
    }
}
