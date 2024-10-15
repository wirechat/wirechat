<?php

namespace Namu\WireChat;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Namu\WireChat\Livewire\Chat\View;
use Namu\WireChat\Livewire\Chat\Chat;
use Namu\WireChat\Livewire\Chat\Chats;
use Namu\WireChat\Livewire\Chat\Index;
use Namu\WireChat\Livewire\Info\Info;
use Namu\WireChat\Livewire\Components\NewChat;
use Namu\WireChat\Livewire\Components\NewGroup;
use Namu\WireChat\Livewire\Info\AddMembers;
use Namu\WireChat\Livewire\Modals\ChatModal;
use Namu\WireChat\Livewire\Modals\Modal;
use Namu\WireChat\Services\WireChatService;
use Namu\WireChat\View\Components\ChatBox\Image;

class WireChatServiceProvider extends ServiceProvider 
{


    function boot()  {

        $this->loadLivewireComponents();


        Blade::component('wirechat::chatbox.image', Image::class);


        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'wirechat');

        $this->publishes([
            __DIR__.'/../config/wirechat.php' => config_path('wirechat.php'),
        ],'wirechat-config');
    

    }


    //custom methods for livewire components
    protected function loadLivewireComponents()  {
        Livewire::component('index', Index::class);
        Livewire::component('view', View::class);

        Livewire::component('chat', Chat::class);
        Livewire::component('chats', Chats::class);

        //wirechat  modal 
        Livewire::component('wirechat-modal', Modal::class);
        Livewire::component('chat-modal', ChatModal::class);


        Livewire::component('new-chat', NewChat::class);
        Livewire::component('new-group', NewGroup::class);
        Livewire::component('info', Info::class);
        Livewire::component('add-members', AddMembers::class);


    }


    function register() {


        $this->mergeConfigFrom(
            __DIR__.'/../config/wirechat.php', 'wirechat'
        );

        //register facades
        $this->app->singleton('wirechat', function ($app) {
            return new WireChatService();
        });
    
  //      $this->app->register(LivewireModalServiceProvider::class);
        
    }

}
