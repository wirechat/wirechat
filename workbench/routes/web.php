<?php


use Illuminate\Support\Facades\Route;
use Namu\WireChat\Livewire\Chat\Chat;
use Namu\WireChat\Livewire\Chat\Chats;
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




// Route::middleware(['auth'])->group(function (){


// Route::get('/chats',Chats::class)->name('wirechat');
// Route::get('/chats/{chat}',Chat::class)->name('wirechat.chat');
    

// });
Route::middleware('guest')->get('/login',function(){


return "login page";

})->name("login");

