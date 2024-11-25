<?php

use Illuminate\Support\Facades\Route;
use Namu\WireChat\Livewire\Chat\Index;
use Namu\WireChat\Livewire\Chat\View;

Route::middleware(config('wirechat.routes.middleware'))
    ->prefix(config('wirechat.routes.prefix'))
    ->group(function () {
        Route::get('/', Index::class)->name('chats');
        Route::get('/{conversation_id}', View::class)->name('chat');
    });
