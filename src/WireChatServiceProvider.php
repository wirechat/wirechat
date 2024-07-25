<?php

namespace Namu\WireChat;


use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Namu\WireChat\Livewire\Chat\Chat;
use Namu\WireChat\Livewire\Chat\ChatBox;
use Namu\WireChat\Livewire\Chat\ChatList;
use Namu\WireChat\Livewire\Chat\Chats;

class WireChatServiceProvider extends ServiceProvider 
{


    function boot()  {

        $this->loadLivewireComponents();


        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'wirechat');

        $this->publishes([
            __DIR__.'/../config/wirechat.php' => config_path('wirechat.php'),
        ]);
    

    }


    protected function loadLivewireComponents()  {
        Livewire::component('chat', Chat::class);
        Livewire::component('chat-list', ChatList::class);

        Livewire::component('chat-box', ChatBox::class);

        Livewire::component('chats', Chats::class);

    }


    function register() {


        $this->mergeConfigFrom(
            __DIR__.'/../config/wirechat.php', 'wirechat'
        );
    
        
    }

}
