<?php

namespace Namu\WireChat;

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Namu\WireChat\Console\Commands\InstallWireChat;
use Namu\WireChat\Console\Commands\SetupNotifications;
use Namu\WireChat\Facades\WireChat as FacadesWireChat;
use Namu\WireChat\Livewire\Chat\Chat;
use Namu\WireChat\Livewire\Chat\Drawer;
use Namu\WireChat\Livewire\Chat\Group\AddMembers;
use Namu\WireChat\Livewire\Chat\Group\Members;
use Namu\WireChat\Livewire\Chat\Group\Permissions;
use Namu\WireChat\Livewire\Chat\Info;
use Namu\WireChat\Livewire\Chats\Chats;
use Namu\WireChat\Livewire\Modals\Modal;
use Namu\WireChat\Livewire\New\Chat as NewChat;
use Namu\WireChat\Livewire\New\Group as NewGroup;
use Namu\WireChat\Livewire\Pages\Chat as View;
use Namu\WireChat\Livewire\Pages\Chats as Index;
use Namu\WireChat\Livewire\Widgets\WireChat;
use Namu\WireChat\Middleware\BelongsToConversation;
use Namu\WireChat\Services\WireChatService;

class WireChatServiceProvider extends ServiceProvider
{
    public function boot()
    {

        // Register the command if we are using the application via the CLI
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallWireChat::class,
                SetupNotifications::class,
            ]);
        }

        $this->loadLivewireComponents();

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'wirechat');

        // publish config
        $this->publishes([
            __DIR__.'/../config/wirechat.php' => config_path('wirechat.php'),
        ], 'wirechat-config');

        // publish migrations
        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'wirechat-migrations');

        // publish views
        if ($this->app->runningInConsole()) {
            // Publish views
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/wirechat'),
            ], 'wirechat-views');

        }

        /* Load channel routes */
        $this->loadRoutesFrom(__DIR__.'/../routes/channels.php');

        //load translations
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'wirechat-translations');

        // load assets
        $this->loadAssets();

        // load styles
        $this->loadStyles();

        // load middleware
        $this->registerMiddlewares();

    }

    public function register()
    {

        $this->mergeConfigFrom(
            __DIR__.'/../config/wirechat.php', 'wirechat'
        );

        // register facades
        $this->app->singleton('wirechat', function ($app) {
            return new WireChatService;
        });

    }

    // custom methods for livewire components
    protected function loadLivewireComponents(): void
    {
        // Pages
        Livewire::component('wirechat.pages.index', Index::class);
        Livewire::component('wirechat.pages.view', View::class);

        // Chats
        Livewire::component('wirechat.chats', Chats::class);

        // modal
        Livewire::component('wirechat.modal', Modal::class);

        Livewire::component('wirechat.new.chat', NewChat::class);
        Livewire::component('wirechat.new.group', NewGroup::class);

        // Chat/Group related components
        Livewire::component('wirechat.chat', Chat::class);
        Livewire::component('wirechat.chat.info', Info::class);
        Livewire::component('wirechat.chat.drawer', Drawer::class);
        Livewire::component('wirechat.chat.group.add-members', AddMembers::class);
        Livewire::component('wirechat.chat.group.members', Members::class);
        Livewire::component('wirechat.chat.group.permissions', Permissions::class);

        // stand alone widget component
        Livewire::component('wirechat', WireChat::class);

    }

    protected function registerMiddlewares(): void
    {

        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('belongsToConversation', BelongsToConversation::class);

    }

    protected function loadAssets(): void
    {
        Blade::directive('wirechatAssets', function () {
            return "<?php if(auth()->check()): ?>
                        <?php 
                            echo Blade::render('@livewire(\'wirechat.modal\')');
                            echo Blade::render('<x-wirechat::toast/>');
                            echo Blade::render('<x-wirechat::notification/>');
                        ?>
                <?php endif; ?>";
        });
    }

    // load assets
    protected function loadStyles(): void
    {

        $primaryColor = FacadesWireChat::getColor();
        Blade::directive('wirechatStyles', function () use ($primaryColor) {
            return "<?php echo <<<EOT
                <style>
                    :root {
                        --wirechat-primary-color: {$primaryColor};
                    }
                    [x-cloak] {
                        display: none !important;
                    }
                </style>
            EOT; ?>";
        });
    }
}
