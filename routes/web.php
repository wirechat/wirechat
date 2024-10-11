<?php

use Illuminate\Support\Facades\Route;
use Namu\WireChat\Livewire\Chat\View;
use Namu\WireChat\Livewire\Chat\Index;


Route::middleware(config('wirechat.routes.middleware'))
    ->prefix(config('wirechat.routes.prefix'))
    ->group(function () {
        Route::get('/', Index::class)->name('wirechat');
        Route::get('/{conversation_id}', View::class)->name('wirechat.chat');
    });

