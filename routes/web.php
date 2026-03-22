<?php

use Illuminate\Support\Facades\Route;
use Wirechat\Wirechat\Http\Controllers\InviteController;
use Wirechat\Wirechat\PanelRegistry;

Route::name('wirechat.')
    ->group(function () {
        $panels = app(PanelRegistry::class)->all();
        if (empty($panels)) {
            \Log::warning('No panels registered in wirechatPanelRegistry');

            return;
        }

        foreach ($panels as $panel) {
            if (! $panel->hasRoutes()) {
                continue;
            }

            Route::prefix($panel->getRoutePrefix())
                ->name("{$panel->getPath()}.")
                ->middleware([
                    'web',
                    "wirechat.setPanel:{$panel->getId()}",
                ])
                ->group(function () use ($panel) {
                    if ($panel->hasGroupInvitations()) {
                        Route::get('/invites/{token}', [InviteController::class, 'show'])
                            ->middleware('throttle:wirechat-invite')
                            ->where('token', '[A-Za-z0-9]{16,64}')
                            ->name('invite.show');
                        Route::post('/invites/{token}/join', [InviteController::class, 'join'])
                            ->middleware('throttle:wirechat-invite')
                            ->where('token', '[A-Za-z0-9]{16,64}')
                            ->name('invite.join');
                    }
                });

            Route::prefix($panel->getRoutePrefix())
                ->name("{$panel->getPath()}.")
                ->middleware(array_merge(
                    ['web'],
                    $panel->getMiddleware(),
                    [
                        "wirechat.setPanel:{$panel->getId()}",
                        "wirechat.panelAccess:{$panel->getId()}",
                    ]
                ))
                ->group(function () use ($panel) {
                    Route::view('/', 'wirechat::pages.chats', ['panel' => $panel->getId()])
                        ->name('chats');

                    Route::view('/{conversation}', 'wirechat::pages.chat', ['panel' => $panel->getId()])
                        ->middleware($panel->getChatMiddleware())
                        ->name('chat');
                });
        }
    });
