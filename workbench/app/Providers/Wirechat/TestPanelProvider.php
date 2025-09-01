<?php

namespace Workbench\App\Providers\Wirechat;

use Wirechat\Wirechat\Http\Resources\WirechatUserResource;
use Wirechat\Wirechat\Panel;
use Wirechat\Wirechat\PanelProvider;
use Workbench\App\Models\User;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('test')
            ->path('test')
            ->chatsSearch(true)
            ->searchUsersUsing(function ($needle) {
                return WirechatUserResource::collection(
                    User::query()
                        ->where(function ($q) use ($needle) {
                            foreach (['name'] as $field) {
                                $q->orWhere($field, 'like', "%{$needle}%");
                            }
                        })
                        ->get()
                );

            })
            ->middleware(['web', 'auth'])
            ->webPushNotifications()
            ->default();
    }
}
