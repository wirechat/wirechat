<?php

use Illuminate\Support\Facades\Route;
use Namu\WireChat\Livewire\Pages\Chat;
use Namu\WireChat\Livewire\Pages\Chats;

Route::middleware(config('wirechat.routes.middleware'))
    ->prefix(config('wirechat.routes.prefix'))
    ->group(function () {
        Route::get('/', Chats::class)->name('chats');
        Route::get('/{conversation}', Chat::class)->middleware('belongsToConversation')->name('chat');
    });
