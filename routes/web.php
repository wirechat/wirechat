<?php

use Illuminate\Support\Facades\Route;
use Wirechat\Wirechat\PanelRegistry;

Route::name('wirechat.')
    ->group(function () {
        $panels = app(PanelRegistry::class)->all();
        if (empty($panels)) {
            \Log::warning('No panels registered in wirechatPanelRegistry');

            return;
        }
        foreach ($panels as $panel) {
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
