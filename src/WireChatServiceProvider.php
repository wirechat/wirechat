<?php

namespace Namu\WireChat;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Namu\WireChat\Console\Commands\InstallWireChat;
use Namu\WireChat\Console\Commands\MakePanelCommand;
use Namu\WireChat\Console\Commands\SetupNotifications;
use Namu\WireChat\Facades\WireChat as FacadesWireChat;
use Namu\WireChat\Facades\WireChatColor;
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
use Namu\WireChat\Middleware\SetCurrentPanel;
use Namu\WireChat\Services\ColorService;
use Namu\WireChat\Services\WireChatService;
use Namu\WireChat\Support\Color;

class WireChatServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        /**
         * Register default colors first so that when panel colors are registerd they would take presedence
         */
        $this->bootColors();

        // Register the command if we are using the application via the CLI
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallWireChat::class,
                SetupNotifications::class,
                MakePanelCommand::class,
            ]);
        }

        // Trigger auto-discovery
        app('wirechatPanelRegistry')->autoDiscover();

        logger('WireChatServiceProvider booted, auto-discovery completed');

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

    protected function bootColors()
    {

        WireChatColor::register([
            'primary' => Color::Blue,
            'danger' => Color::Red,
            'success' => Color::Green,
            'warning' => Color::Amber,
            'info' => Color::Blue,
            'gray' => Color::Zinc,
        ]);
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

        $this->app->singleton(ColorService::class, fn () => new ColorService);

        // Register PanelRegistry with auto-discovery
        // Bind PanelRegistry to the container
        // Bind PanelRegistry to the container
        $this->app->singleton('wirechatPanelRegistry', function () {
            logger('Binding wirechatPanelRegistry');

            return new PanelRegistry;
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
        $router = $this->app->make(\Illuminate\Routing\Router::class);

        $router->aliasMiddleware('belongsToConversation', BelongsToConversation::class);
        $router->aliasMiddleware('wirechat.setPanel', SetCurrentPanel::class);
    }

    protected function loadAssets(): void
    {
        Blade::directive('wirechatAssets', function (Panel|string|null $panel = null) {

            // Check if panel param s set
            if (isset($panel)) {
                $currentPanel = \Namu\WireChat\Facades\WireChat::getPanel($panel);
            } else {
                $currentPanel = \Namu\WireChat\Facades\WireChat::currentPanel(); // This gets panel according to route or default
            }

            $hasWebPushNotifications = $currentPanel->hasWebPushNotifications();
            $panelId = \Namu\WireChat\Facades\WireChat::currentPanel()?->getId();
            $userId = auth()->id();
            $encodedType = \Namu\WireChat\Helpers\MorphClassResolver::encode(auth()->user()?->getMorphClass());

            $script = '';

            if ($hasWebPushNotifications) {
                $script = <<<HTML
                             <script>
                                document.addEventListener("DOMContentLoaded", function() {


                                   if ('serviceWorker' in navigator) {
                                        window.addEventListener('load', async () => {
                                            try {
                                                const registrations = await navigator.serviceWorker.getRegistrations();

                                                // Remove any old Wirechat SW
                                                const oldSw = registrations.find(reg =>
                                                    reg.active?.scriptURL.includes("{$currentPanel->getServiceWorkerPath()}")
                                                );
                                                if (oldSw) await oldSw.unregister();

                                                // Register the current SW
                                                await navigator.serviceWorker.register("{$currentPanel->getServiceWorkerPath()}");
                                                console.log('Wirechat Service Worker registered/updated');
                                            } catch (err) {
                                                console.error('Wirechat Service Worker registration failed:', err);
                                            }
                                        });
                                    }





                                    Echo.private(`{$panelId}.participant.{$encodedType}.{$userId}`)
                                        .listen('.Namu\\\\WireChat\\\\Events\\\\NotifyParticipant', (e) => {

                                            if (e.redirect_url !== window.location.href) {
                                                if (Notification.permission === 'granted') {
                                                    showNotification(e);
                                                } else if (Notification.permission !== 'denied') {
                                                    Notification.requestPermission().then(permission => {
                                                        if (permission === 'granted') {
                                                            showNotification(e);
                                                        }
                                                    });
                                                }
                                            }
                                        });

                                    function showNotification(e) {
                                        let title = e.message.sendable?.display_name || 'User';
                                        let body  = e.message.body;
                                        let icon  = e.message.sendable?.cover_url;

                                        if (e.message.conversation.type === 'group') {
                                            title = e.message.conversation?.group?.name;
                                            body  = e.message.sendable?.display_name + ': ' + e.message.body;
                                            icon  = e.message.conversation?.group?.cover_url;
                                        }

                                        const options = {
                                            body: body,
                                            icon: icon,
                                            vibrate: [200, 100, 200],
                                            tag: 'wirechat-notification-' + e.message.conversation_id,
                                            renotify: true,
                                            data: {
                                                url: e.redirect_url,
                                                type: 'SHOW_NOTIFICATION',
                                                tab:`{$panelId}-wirechat-tab`,
                                                tag: 'wirechat-notification-' + e.message.conversation_id
                                            }
                                        };

                                        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
                                            navigator.serviceWorker.controller.postMessage({
                                                type: 'SHOW_NOTIFICATION',
                                                title: title,
                                                options: options
                                            });
                                        } else {
                                            new Notification(title, options);
                                        }
                                    }
                                    });
                             </script>
                          HTML;
            }

            return <<<HTML
                <?php if(auth()->check()): ?>
                    <?php
                        echo Blade::render('@livewire("wirechat.modal")');
                        echo Blade::render('<x-wirechat::toast/>');
                    ?>



                    {$script}

               <?php endif; ?>
        HTML;
        });
    }

    // load assets
    protected function loadStyles(): void
    {

        Blade::directive('wirechatStyles',function (string|null $panel = null) {

            // Check if panel param s set
            if (isset($panel)) {
                $currentPanel = \Namu\WireChat\Facades\WireChat::getPanel($panel);
            } else {
                $currentPanel = \Namu\WireChat\Facades\WireChat::currentPanel(); // This gets panel according to route or default
            }

           $primaryColor= isset( $currentPanel->getColors()['primary'])? $currentPanel->getColors()['primary'][500]:'oklch(0.623 0.214 259.815)';

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
