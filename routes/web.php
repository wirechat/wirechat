<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Namu\WireChat\Livewire\Pages\Chat;
use Namu\WireChat\Livewire\Pages\Chats;


//Route::middleware(config('wirechat.routes.middleware'))
//    ->prefix(config('wirechat.routes.prefix'))
//    ->group(function () {
//        Route::get('/', Chats::class)->name('chats');
//        Route::get('/{conversation}', Chat::class)->middleware('belongsToConversation')->name('chat');
//    });
//
//
Route::middleware(config('wirechat.routes.middleware'))
    ->prefix('chats')
    ->group(function () {
        Route::get('/', Chats::class)->name('chats');
        Route::get('/{conversation}', Chat::class)->middleware('belongsToConversation')->name('chat');
    });


Route::as('wirechat.')
    ->group(function () {
        $panels = app('wirechatPanelRegistry')->all();
        Log::info('WireChat panels registered:', ['panels' => array_keys($panels)]);

        if (empty($panels)) {
            Log::warning('No panels registered in wirechatPanelRegistry');
        }

        foreach ($panels as $panel) {
            Route::prefix($panel->getRoutePrefix())
                ->name("{$panel->getId()}.")
                ->middleware($panel->getMiddleware())
                ->group(function () use ($panel) {
//                    Route::get('/', function () use ($panel) {
//                        return new Chats(['panel' => $panel]);
//                    })->name('chats');
//                    Route::get('/{conversation}', function ($conversation) use ($panel) {
//                        return new Chat(['panel' => $panel, 'conversation' => $conversation]);
//                    })->middleware('belongsToConversation')->name('chat');
//                    Route::get('/',function()use ($panel){
//
//                        dd($panel);
//
//                        $component = app(Chats::class, ['panel' => $panel]);
//                        return $component(['panel' => $panel]);
//                    })->name('chats');

                     Route::view('/','wirechat::pages.chats', ['panel' => $panel]);
                     Route::view('/{conversation}','wirechat::pages.chat', ['panel' => $panel])->name('chat');




               //     Route::get('/{conversation}', Chat::class)->middleware('belongsToConversation')->name('chat');
                });
        }
    });
