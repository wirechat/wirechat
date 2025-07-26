<?php

namespace Workbench\App\Providers\WireChat;

use Namu\WireChat\Panel;
use Namu\WireChat\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->middleware(['web']);
    }
}
