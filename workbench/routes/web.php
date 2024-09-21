<?php

use App\Livewire\Test;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use Namu\WireChat\Livewire\Chat\Chat;
use Namu\WireChat\Livewire\Chat\Chats;
use Namu\WireChat\Livewire\Chat\Index;
use Namu\WireChat\Livewire\Chat\View;

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

Route::get('/', function () {
    return view('welcome');
});



Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');


//Route::get('/test',Test::class);

// Route::middleware(['auth'])->group(function (){


// Route::get('/chats',Chats::class)->name('wirechat');
// Route::get('/chats/{chat}',Chat::class)->name('wirechat.chat');
    

// });
Route::middleware('guest')->get('/login',function(){

return "login page";

})->name("login");
 
Route::middleware(['auth','web'])->group(function (){


    Route::get('/chats',Index::class)->name('wirechat');
    Route::get('/chats/{chat}',View::class)->name('wirechat.chat');
        
    });
    
    
