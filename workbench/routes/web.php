<?php

use Illuminate\Support\Facades\Route;
use Namu\WireChat\Livewire\Pages\Chat;
use Namu\WireChat\Livewire\Pages\Chats;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Needed for testing purposes
Route::get('/', function () {
    return 'welcome';
});

// Needed for testing purposes
Route::middleware('guest')->get('/login', function () {
    return 'login page';
})->name('login');

Route::middleware(config('wirechat.routes.middleware'))
    ->prefix(config('wirechat.routes.prefix'))
    ->group(function () {
        Route::get('/', Chats::class)->name('chats');
        Route::get('/{conversation}', Chat::class)->middleware('belongsToConversation')->name('chat');
    });
