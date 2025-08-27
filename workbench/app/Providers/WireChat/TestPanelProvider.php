<?php

namespace Workbench\App\Providers\WireChat;

use Namu\WireChat\Http\Resources\ChatableResource;
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
            ->searchChatablesUsing(function ($needle) {
                return ChatableResource::collection(
                    User::query()
                        ->where(function ($q) use ($needle) {
                            foreach (['name'] as $field) {
                                $q->orWhere($field, 'like', "%{$needle}%");
                            }
                        })
                        ->get()
                );

            })
            ->middleware(['web','auth'])
            ->webPushNotifications()
            ->default();
    }
}
