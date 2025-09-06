<?php

namespace Wirechat\Wirechat\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;

class MigrateConfigToPanelCommand extends Command
{
    protected $signature = 'wirechat:upgrade-to-v0.3x {--dry-run : Show what would be done without making changes}';

    protected $description = 'Upgrade Wirechat by creating a panel provider and migrating old config values';

    public function handle()
    {
        $this->info('Starting Wirechat upgrade to panel...');

        $id = 'chats';
        $className = Str::studly($id).'PanelProvider';
        $namespace = 'App\\Providers\\Wirechat';
        $path = app_path("Providers/Wirechat/{$className}.php");
        $displayPath = Str::after($path, base_path().DIRECTORY_SEPARATOR);

        // Exit if panel file already exists
        if (file_exists($path)) {
            $this->error("Panel provider already exists at: {$displayPath}. Aborting upgrade.");

            return 1;
        }

        // Prepare old config
        $config = config('wirechat');

        // Define default values
        $defaults = [
            'routes' => [
                'prefix' => 'chats',
                'middleware' => ['web', 'auth'],
                'guards' => ['web'],
            ],
            'home_route' => '/',
            'layout' => 'wirechat::layouts.app',
            'max_group_members' => 1000,
            'attachments' => [
                'max_uploads' => 10,
                'media_mimes' => ['png', 'jpg', 'jpeg', 'gif', 'mov', 'mp4'],
                'media_max_upload_size' => 12288,
                'file_mimes' => ['zip', 'rar', 'txt', 'pdf'],
                'file_max_upload_size' => 12288,
            ],
            'notifications' => [
                'enabled' => true,
                'main_sw_script' => 'sw.js',
            ],
            'show_new_chat_modal_button' => true,
            'show_new_group_modal_button' => true,
            'allow_chats_search' => true,
            'allow_media_attachments' => true,
            'allow_file_attachments' => true,
        ];

        // Build panel configuration, omitting defaults
        $panelConfig = [];
        $panelConfig[] = "->id('".$id."')";
        $panelConfig[] = "->path('".($config['routes']['prefix'] ?? 'chats')."')";

        if (($config['routes']['middleware'] ?? ['web', 'auth']) !== $defaults['routes']['middleware']) {
            $panelConfig[] = '->middleware('.$this->arrayExport($config['routes']['middleware'] ?? ['web', 'auth']).')';
        }
        if (($config['routes']['guards'] ?? ['web']) !== $defaults['routes']['guards']) {
            $panelConfig[] = '->guards('.$this->arrayExport($config['routes']['guards'] ?? ['web']).')';
        }
        if (($config['home_route'] ?? '/') !== $defaults['home_route']) {
            $panelConfig[] = "->homeUrl('".($config['home_route'] ?? '/')."')";
        }
        if (($config['layout'] ?? 'wirechat::layouts.app') !== $defaults['layout']) {
            $panelConfig[] = "->layout('".($config['layout'] ?? 'wirechat::layouts.app')."')";
        }

        $panelConfig[] = '->emojiPicker()';

        $panelConfig[] = "->colors([\n                'primary' => Color::Blue,\n            ])";
        if (($config['show_new_chat_modal_button'] ?? true) !== $defaults['show_new_chat_modal_button']) {
            $panelConfig[] = '->newChatAction()';
        }
        if (($config['show_new_group_modal_button'] ?? true) !== $defaults['show_new_group_modal_button']) {
            $panelConfig[] = '->newGroupAction()';
        }

        if (($config['allow_chats_search'] ?? true) !== $defaults['allow_chats_search']) {
            $panelConfig[] = '->chatsSearch()';
        }
        if (($config['allow_media_attachments'] ?? true) !== $defaults['allow_media_attachments']) {
            $panelConfig[] = '->mediaAttachments()';
        }
        if (($config['allow_file_attachments'] ?? true) !== $defaults['allow_file_attachments']) {
            $panelConfig[] = '->fileAttachments()';
        }
        if (($config['max_group_members'] ?? 1000) !== $defaults['max_group_members']) {
            $panelConfig[] = '->maxGroupMembers('.($config['max_group_members'] ?? 1000).')';
        }
        if (($config['attachments']['max_uploads'] ?? 10) !== $defaults['attachments']['max_uploads']) {
            $panelConfig[] = '->maxUploads('.($config['attachments']['max_uploads'] ?? 10).')';
        }
        if (($config['attachments']['media_mimes'] ?? ['png', 'jpg', 'jpeg', 'gif', 'mov', 'mp4']) !== $defaults['attachments']['media_mimes']) {
            $panelConfig[] = '->mediaMimes('.$this->arrayExport($config['attachments']['media_mimes'] ?? ['png', 'jpg', 'jpeg', 'gif', 'mov', 'mp4']).')';
        }
        if (($config['attachments']['media_max_upload_size'] ?? 12288) !== $defaults['attachments']['media_max_upload_size']) {
            $panelConfig[] = '->mediaMaxUploadSize('.($config['attachments']['media_max_upload_size'] ?? 12288).')';
        }
        if (($config['attachments']['file_mimes'] ?? ['zip', 'rar', 'txt', 'pdf']) !== $defaults['attachments']['file_mimes']) {
            $panelConfig[] = '->fileMimes('.$this->arrayExport($config['attachments']['file_mimes'] ?? ['zip', 'rar', 'txt', 'pdf']).')';
        }
        if (($config['attachments']['file_max_upload_size'] ?? 12288) !== $defaults['attachments']['file_max_upload_size']) {
            $panelConfig[] = '->fileMaxUploadSize('.($config['attachments']['file_max_upload_size'] ?? 12288).')';
        }
        if (($config['notifications']['enabled'] ?? true) !== $defaults['notifications']['enabled'] ||
            ($config['notifications']['main_sw_script'] ?? 'sw.js') !== $defaults['notifications']['main_sw_script']) {
            $panelConfig[] = '->webPushNotifications()';
            if (($config['notifications']['main_sw_script'] ?? 'sw.js') !== $defaults['notifications']['main_sw_script']) {
                $panelConfig[] = "->serviceWorkerPath(asset('".($config['notifications']['main_sw_script'] ?? 'sw.js')."'))";
            }
        }

        // Generate panel file content
        $panelCode = '<?php

namespace '.$namespace.";

use Wirechat\Wirechat\Panel;
use Wirechat\Wirechat\PanelProvider;
use Wirechat\Wirechat\Support\Color;

class ".$className.' extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            '.implode("\n            ", $panelConfig).'
            ->default();
    }
}';

        if ($this->option('dry-run')) {
            $this->info("Dry run: Panel provider would be created at: {$displayPath}");
            $this->info("Generated panel provider code:\n".$panelCode);
            $this->info("Provider would be registered: {$namespace}\\{$className}");
            $this->info('Dry run complete! No changes were made.');

            return 0;
        }

        // Ensure directory exists
        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        // Write panel file
        file_put_contents($path, $panelCode);
        $this->info("Panel provider created at: {$displayPath}");

        // Update namespaces
        $this->updateNamespaces();

        // Register provider
        $this->registerProvider($namespace, $className);

        $this->info('Wirechat upgrade complete! Review the panel for any custom logic.');
    }

    protected function updateNamespaces()
    {
        $files = [];
        $command = "find . -type f -name '*.php' -not -path './vendor/*' -not -path './storage/*' -exec grep -l 'Namu\\\\WireChat' {} \;";
        exec($command, $files);

        if ($this->option('dry-run')) {
            if (empty($files)) {
                $this->info('Dry run: No files found with Namu\\WireChat to update.');
            } else {
                $this->info('Dry run: Files that would be updated:');
                foreach ($files as $file) {
                    $this->info($file);
                }
            }

            return;
        }

        $command = "find . -type f -name '*.php' -not -path './vendor/*' -not -path './storage/*' -exec sed -i '' 's/Namu\\\\WireChat/Wirechat\\\\Wirechat/g' {} \;";
        exec($command);

        if (empty($files)) {
            $this->info('No files found with Namu\\WireChat to update.');
        } else {
            $this->info('Updated namespaces in the following files:');
            foreach ($files as $file) {
                $this->info($file);
            }
        }
    }

    protected function registerProvider(string $namespace, string $className)
    {
        $providerClass = "{$namespace}\\{$className}";

        if ($this->option('dry-run')) {

            $this->info(
                'Dry run: Would register provider in '.
                (version_compare(App::version(), '11.0', '>=')
                    ? 'bootstrap/providers.php'
                    : 'config/app.php')
            );

            return;
        }

        if (version_compare(App::version(), '11.0', '>=') && file_exists(App::getBootstrapProvidersPath())) {
            $bootstrapPath = App::getBootstrapProvidersPath();
            $contents = file_get_contents($bootstrapPath);
            if (! str_contains($contents, $providerClass)) {
                file_put_contents($bootstrapPath, str_replace(
                    'return [',
                    "return [\n    {$providerClass}::class,",
                    $contents
                ));
            }
        } else {
            $appConfigPath = config_path('app.php');
            $contents = file_get_contents($appConfigPath);
            if (! str_contains($contents, $providerClass)) {
                file_put_contents($appConfigPath, str_replace(
                    'App\\Providers\\RouteServiceProvider::class,',
                    "{$providerClass}::class,".PHP_EOL.'        App\\Providers\\RouteServiceProvider::class,',
                    $contents
                ));
            }
        }

        $this->info("Registered provider: {$providerClass}");
    }

    protected function arrayExport(array $arr): string
    {
        if (empty($arr)) {
            return '[]';
        }

        // Map each item to a quoted string and join with commas
        $items = array_map(function ($item) {
            return "'".addslashes($item)."'";
        }, $arr);

        return '['.implode(',', $items).']';
    }
}
