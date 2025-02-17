<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Namu\WireChat\Livewire\Chat\Chats;
use Namu\WireChat\WireChatServiceProvider;

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

        // $this->app->register(WireChatServiceProvider::class);
        // $this->app->register(LivewireServiceProvider::class);

        // Register the WireChatServiceProvider

    }
}
