<?php

use Illuminate\Support\Facades\Route;
use Namu\WireChat\Livewire\Chat\View;
use Namu\WireChat\Livewire\Chat\Index;



Route::middleware(['auth','web'])->group(function (){


Route::get('/chats',Index::class)->name('wirechat');
Route::get('/chats/{chat}',View::class)->name('wirechat.chat');
    
});

