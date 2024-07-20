<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Artisan::command('inspire', function () {
    $this->comment('pest');
});
