<?php

use Illuminate\Support\Facades\Route;
use Namu\WireChat\Livewire\Chat\Chat;
use Namu\WireChat\Livewire\Chat\Index;


Route::middleware(['auth','web'])->group(function (){


Route::get('/chat',Index::class)->name('wirechat');
Route::get('/chat/{chat}',Chat::class)->name('wirechat.chat');
    

});