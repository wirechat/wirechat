<?php

namespace Workbench\App\Providers\WireChat;

use Namu\WireChat\Http\Resources\WireChatUserResource;
use Namu\WireChat\Panel;
use Namu\WireChat\PanelProvider;
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
                return WireChatUserResource::collection(
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
