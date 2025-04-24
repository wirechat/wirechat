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
use Namu\WireChat\Livewire\Chat\Group\Info as GroupInfo;
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
    public function boot(): void
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

        // publish views
        if ($this->app->runningInConsole()) {
            // Publish views
            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/wirechat'),
            ], 'wirechat-views');

            // Publish language files
            $this->publishes([
                __DIR__.'/../lang' => lang_path('vendor/wirechat'),
            ], 'wirechat-translations');

            // publish config
            $this->publishes([
                __DIR__.'/../config/wirechat.php' => config_path('wirechat.php'),
            ], 'wirechat-config');

            // publish migrations
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'wirechat-migrations');
        }

        /* Load channel routes */
        $this->loadRoutesFrom(__DIR__.'/../routes/channels.php');

        // load assets
        $this->loadAssets();

        // load styles
        $this->loadStyles();

        // load middleware
        $this->registerMiddlewares();

        // load translations
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'wirechat');
    }

    public function register()
    {

        $this->mergeConfigFrom(
            __DIR__.'/../config/wirechat.php',
            'wirechat'
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
        Livewire::component('wirechat.chat.group.info', GroupInfo::class);
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
                        --wc-brand-primary: {$primaryColor};
                        
                        --wc-light-primary: #fff;  /* white */
                        --wc-light-secondary: oklch(0.967 0.003 264.542);/* --color-gray-100 */
                        --wc-light-accent: oklch(0.985 0.002 247.839);/* --color-gray-50 */
                        --wc-light-border: oklch(0.928 0.006 264.531);/* --color-gray-200 */

                        --wc-dark-primary: oklch(0.21 0.034 264.665); /* --color-zinc-900 */
                        --wc-dark-secondary: oklch(0.278 0.033 256.848);/* --color-zinc-800 */
                        --wc-dark-accent: oklch(0.373 0.034 259.733);/* --color-zinc-700 */
                        --wc-dark-border: oklch(0.373 0.034 259.733);/* --color-zinc-700 */
                    }
                    [x-cloak] {
                        display: none !important;
                    }
                </style>
            EOT; ?>";
        });
    }
}
