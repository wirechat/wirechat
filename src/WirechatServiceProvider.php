<?php

namespace Wirechat\Wirechat;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Wirechat\Wirechat\Console\Commands\InstallWirechat;
use Wirechat\Wirechat\Console\Commands\MakePanelCommand;
use Wirechat\Wirechat\Console\Commands\MigrateConfigToPanelCommand;
use Wirechat\Wirechat\Console\Commands\SetupNotifications;
use Wirechat\Wirechat\Console\Commands\UpgradeMorphColumns;
use Wirechat\Wirechat\Console\Commands\UpgradeNamespaceCommand;
use Wirechat\Wirechat\Facades\WirechatColor;
use Wirechat\Wirechat\Livewire\Chat\Chat;
use Wirechat\Wirechat\Livewire\Chat\Drawer;
use Wirechat\Wirechat\Livewire\Chat\Group\Info as GroupInfo;
use Wirechat\Wirechat\Livewire\Chat\Group\JoinFromInvite;
use Wirechat\Wirechat\Livewire\Chat\Group\JoinRequests;
use Wirechat\Wirechat\Livewire\Chat\Group\Links\Create;
use Wirechat\Wirechat\Livewire\Chat\Group\Links\Links;
use Wirechat\Wirechat\Livewire\Chat\Group\Links\Send;
use Wirechat\Wirechat\Livewire\Chat\Group\Links\Show;
use Wirechat\Wirechat\Livewire\Chat\Group\Members\AddMembers;
use Wirechat\Wirechat\Livewire\Chat\Group\Members\BlockedMembers;
use Wirechat\Wirechat\Livewire\Chat\Group\Members\Members;
use Wirechat\Wirechat\Livewire\Chat\Group\Members\PastMembers;
use Wirechat\Wirechat\Livewire\Chat\Group\Permissions;
use Wirechat\Wirechat\Livewire\Chat\Info;
use Wirechat\Wirechat\Livewire\Chats\Chats;
use Wirechat\Wirechat\Livewire\Modals\Modal;
use Wirechat\Wirechat\Livewire\New\Chat as NewChat;
use Wirechat\Wirechat\Livewire\New\Group as NewGroup;
use Wirechat\Wirechat\Livewire\Pages\Chat as View;
use Wirechat\Wirechat\Livewire\Pages\Chats as Index;
use Wirechat\Wirechat\Livewire\Widgets\Wirechat;
use Wirechat\Wirechat\Middleware\BelongsToConversation;
use Wirechat\Wirechat\Middleware\EnsureWirechatPanelAccess;
use Wirechat\Wirechat\Middleware\SetCurrentPanel;
use Wirechat\Wirechat\Services\ColorService;
use Wirechat\Wirechat\Services\WirechatService;
use Wirechat\Wirechat\Support\Color;

class WirechatServiceProvider extends ServiceProvider
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
                InstallWirechat::class,
                SetupNotifications::class,
                MakePanelCommand::class,
                MigrateConfigToPanelCommand::class,
                UpgradeNamespaceCommand::class,
                UpgradeMorphColumns::class,
            ]);
        }

        // Trigger auto-discovery
        //        app(\Wirechat\Wirechat\PanelRegistry::class)->autoDiscover();

        $this->loadLivewireComponents();
        $this->configureRateLimiting();

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

            // update morphs column migration
            $this->publishes([
                __DIR__.'/../stubs/upgradeMorphColumns.stub' => database_path('migrations/'.date('Y_m_d_His').'_upgrade_wirechat_morph_columns.php'),
            ], 'wirechat-update-morphs-migration');

            $this->publishes([
                __DIR__.'/../stubs/add_participant_id_to_messages_table.stub' => database_path('migrations/'.date('Y_m_d_His').'_add_participant_id_to_messages_table.php'),
            ], 'wirechat-upgrade-0.4');

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

        WirechatColor::register([
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
            return new WirechatService;
        });

        $this->app->singleton(ColorService::class, fn () => new ColorService);

        // Register PanelRegistry with auto-discovery
        // Bind PanelRegistry to the container
        $this->app->singleton(PanelRegistry::class, function ($app) {
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
        Livewire::component('wirechat.chat.group.members.add', AddMembers::class);
        Livewire::component('wirechat.chat.group.members.list', Members::class);
        Livewire::component('wirechat.chat.group.members.past', PastMembers::class);
        Livewire::component('wirechat.chat.group.members.blocked', BlockedMembers::class);
        Livewire::component('wirechat.chat.group.permissions', Permissions::class);
        Livewire::component('wirechat.chat.group.links.links', Links::class);
        Livewire::component('wirechat.chat.group.links.list', \Wirechat\Wirechat\Livewire\Chat\Group\Links\List::class);
        Livewire::component('wirechat.chat.group.links.create', Create::class);
        Livewire::component('wirechat.chat.group.links.show', Show::class);
        Livewire::component('wirechat.chat.group.join-from-invite', JoinFromInvite::class);
        Livewire::component('wirechat.chat.group.join-requests', JoinRequests::class);
        Livewire::component('wirechat.chat.group.links.send', Send::class);

        // Backwards-compatible aliases for existing integrations.
        Livewire::component('wirechat.chat.group.add-members', AddMembers::class);
        Livewire::component('wirechat.chat.group.members', Members::class);
        Livewire::component('wirechat.chat.group.past-members', PastMembers::class);
        Livewire::component('wirechat.chat.group.blocked-members', BlockedMembers::class);
        Livewire::component('wirechat.chat.group.link.list', Links::class);
        Livewire::component('wirechat.chat.group.link.create', Create::class);
        Livewire::component('wirechat.chat.group.link.show', Show::class);
        Livewire::component('wirechat.chat.group.link.send', Send::class);
        Livewire::component('wirechat.chat.group.invite-link', Links::class);
        Livewire::component('wirechat.chat.group.create-invite-link', Create::class);
        Livewire::component('wirechat.chat.group.invite-link-details', Show::class);
        Livewire::component('wirechat.chat.group.send-invite-link', Send::class);

        // stand alone widget component
        Livewire::component('wirechat', Wirechat::class);
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('wirechat-invite', function (Request $request) {
            $token = (string) $request->route('token');
            $method = strtolower($request->method());
            $maxAttempts = $request->isMethod('POST') ? 10 : 30;

            return Limit::perMinute($maxAttempts)->by(implode(':', [
                'wirechat-invite',
                $method,
                $request->ip() ?? 'unknown',
                $token,
            ]));
        });
    }

    protected function registerMiddlewares(): void
    {
        $router = $this->app->make(\Illuminate\Routing\Router::class);

        $router->aliasMiddleware('belongsToConversation', BelongsToConversation::class);
        $router->aliasMiddleware('wirechat.setPanel', SetCurrentPanel::class);
        $router->aliasMiddleware('wirechat.panelAccess', EnsureWirechatPanelAccess::class);
    }

    protected function loadAssets(): void
    {
        Blade::directive('wirechatAssets', function (Panel|string|null $panel = null) {

            // Check if panel param s set
            if (isset($panel)) {
                $currentPanel = \Wirechat\Wirechat\Facades\Wirechat::getPanel($panel);
            } else {
                $currentPanel = \Wirechat\Wirechat\Facades\Wirechat::currentPanel(); // This gets panel according to route or default
            }

            $hasWebPushNotifications = $currentPanel->hasWebPushNotifications();
            $panelId = \Wirechat\Wirechat\Facades\Wirechat::currentPanel()?->getId();

            $script = '';

            if ($hasWebPushNotifications && auth()->check()) {
                $userId = auth()->id();
                $encodedType = \Wirechat\Wirechat\Helpers\MorphClassResolver::encode(auth()->user()?->getMorphClass());

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
                                        .listen('.Wirechat\\\\Wirechat\\\\Events\\\\NotifyParticipant', (e) => {

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
                                        let title = e.message.sendable?.wirechat_name || 'User';
                                        let body  = e.message.body;
                                        let icon  = e.message.sendable?.wirechat_avatar_url;

                                        if (e.message.conversation.type === 'group') {
                                            title = e.message.conversation?.group?.name;
                                            body  = e.message.sendable?.wirechat_name + ': ' + e.message.body;
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

        Blade::directive('wirechatStyles', function (?string $panel = null) {

            // Check if panel param s set
            if (isset($panel)) {
                $currentPanel = \Wirechat\Wirechat\Facades\Wirechat::getPanel($panel);
            } else {
                $currentPanel = \Wirechat\Wirechat\Facades\Wirechat::currentPanel(); // This gets panel according to route or default
            }

            $defaultPrimaryPalette = Color::Blue;
            $configuredPrimaryPalette = $currentPanel?->getColors()['primary'] ?? null;

            $primaryPalette = $defaultPrimaryPalette;

            if (is_array($configuredPrimaryPalette)) {
                $primaryPalette = array_replace($primaryPalette, $configuredPrimaryPalette);
            } elseif (is_string($configuredPrimaryPalette) && $configuredPrimaryPalette !== '') {
                $primaryPalette[500] = $configuredPrimaryPalette;
            }

            $primary50 = $primaryPalette[50];
            $primary100 = $primaryPalette[100];
            $primary200 = $primaryPalette[200];
            $primary300 = $primaryPalette[300];
            $primary400 = $primaryPalette[400];
            $primary500 = $primaryPalette[500];
            $primary600 = $primaryPalette[600];
            $primary700 = $primaryPalette[700];
            $primary800 = $primaryPalette[800];
            $primary900 = $primaryPalette[900];
            $primary950 = $primaryPalette[950];

            return "<?php echo <<<EOT
                    <style>
                        :root {
                            --wc-primary-50: {$primary50};
                            --wc-primary-100: {$primary100};
                            --wc-primary-200: {$primary200};
                            --wc-primary-300: {$primary300};
                            --wc-primary-400: {$primary400};
                            --wc-primary-500: {$primary500};
                            --wc-primary-600: {$primary600};
                            --wc-primary-700: {$primary700};
                            --wc-primary-800: {$primary800};
                            --wc-primary-900: {$primary900};
                            --wc-primary-950: {$primary950};
                            --wc-brand-primary: var(--wc-primary-500);

                            --wc-light-primary: #fff;  /* white */
                            --wc-light-secondary: oklch(0.967 0.001 286.375);/* --color-zinc-100 */
                            --wc-light-accent: oklch(0.985 0 0);/* --color-zinc-50 */
                            --wc-light-border: oklch(0.92 0.004 286.32);/* --color-zinc-200 */

                            --wc-dark-primary: oklch(0.21 0.006 285.885); /* --color-zinc-900 */
                            --wc-dark-secondary: oklch(0.274 0.006 286.033);/* --color-zinc-800 */
                            --wc-dark-accent: oklch(0.37 0.013 285.805);/* --color-zinc-700 */
                            --wc-dark-border: oklch(0.37 0.013 285.805);/* --color-zinc-700 */
                        }
                        [x-cloak] {
                            display: none !important;
                        }
                    </style>
            EOT; ?>";
        });
    }
}
