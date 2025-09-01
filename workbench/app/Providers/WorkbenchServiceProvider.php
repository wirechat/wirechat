<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Wirechat\Wirechat\Livewire\Chat\Chats;
use Wirechat\Wirechat\WirechatServiceProvider;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void {}

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
        //  \Livewire\Livewire::forceAssetInjection();

        //  Livewire::component('chat-list', Chats::class);

        // $this->app->register(WirechatServiceProvider::class);
        // $this->app->register(LivewireServiceProvider::class);

        // Register the WirechatServiceProvider

    }
}
