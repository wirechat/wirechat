<?php

use Illuminate\Support\Facades\Route;
use Namu\WireChat\PanelRegistry;

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

Route::name('wirechat.')
    ->group(function () {
        $panels = app(PanelRegistry::class)->all();
        if (empty($panels)) {
            \Log::warning('No panels registered in wirechatPanelRegistry');

            return;
        }
        foreach ($panels as $panel) {
            Route::prefix($panel->getRoutePrefix())
                ->name("{$panel->getPath()}.")
                ->middleware(array_merge(
                    $panel->getMiddleware(),
                    ["wirechat.setPanel:{$panel->getId()}"]
                ))
                ->group(function () use ($panel) {
                    Route::view('/', 'wirechat::pages.chats', ['panel' => $panel->getId()])
                        ->name('chats');
                    Route::view('/{conversation}', 'wirechat::pages.chat', ['panel' => $panel->getId()])
                        ->middleware($panel->getChatMiddleware())
                        ->name('chat');

                });
        }
    });
