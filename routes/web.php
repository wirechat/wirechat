<?php

use Illuminate\Support\Facades\Route;
use Namu\WireChat\Livewire\Chat\Chat;
use Namu\WireChat\Livewire\Chat\Chats;



Route::middleware(['auth','web'])->group(function (){


Route::get('/chats',Chats::class)->name('wirechat');
Route::get('/chats/{chat}',Chat::class)->name('wirechat.chat');
    
});

