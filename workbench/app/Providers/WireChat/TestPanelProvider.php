<?php

namespace Workbench\App\Providers\WireChat;


use Namu\WireChat\Panel;
use Namu\WireChat\PanelProvider;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('test')
            ->path('wirechat')
            ->chatsSearch(true)
            ->middleware(['web'])
            ->webPushNotifications(true)
            ->default();
    }
}
