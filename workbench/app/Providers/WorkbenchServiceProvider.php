<?php

namespace Workbench\App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Livewire\LivewireServiceProvider;
use Namu\WireChat\Livewire\Chat\ChatList;
use Namu\WireChat\WireChatServiceProvider;
use Workbench\App\Models\User;

class WorkbenchServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        

    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //


        Livewire::component('chat-list', ChatList::class);


        $this->app->register(WireChatServiceProvider::class);
        $this->app->register(LivewireServiceProvider::class);


          // Register the WireChatServiceProvider

        
    }
}
