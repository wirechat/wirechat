<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Wirechat\Wirechat\WirechatServiceProvider;

if (! function_exists('wirechat_remove_path')) {
    function wirechat_remove_path(string $path): void
    {
        if (is_link($path) || is_file($path)) {
            @unlink($path);

            return;
        }

        if (! is_dir($path)) {
            return;
        }

        try {
            $items = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($items as $item) {
                if ($item->isDir() && ! $item->isLink()) {
                    @rmdir($item->getPathname());
                } else {
                    @unlink($item->getPathname());
                }
            }

            @rmdir($path);
        } catch (UnexpectedValueException) {
            // Another cleanup may have already removed the directory.
        }
    }
}

if (! function_exists('wirechat_copy_directory_with_retry')) {
    function wirechat_copy_directory_with_retry(string $source, string $target, int $attempts = 5): void
    {
        for ($attempt = 1; $attempt <= $attempts; $attempt++) {
            try {
                $previousHandler = set_error_handler(static function (int $severity, string $message, string $file = '', int $line = 0): bool {
                    throw new ErrorException($message, 0, $severity, $file, $line);
                });

                try {
                    File::copyDirectory($source, $target);
                } finally {
                    restore_error_handler();
                    unset($previousHandler);
                }

                return;
            } catch (Throwable $e) {
                if ($attempt === $attempts) {
                    throw $e;
                }

                wirechat_remove_path($target);
                usleep(50_000 * $attempt);
            }
        }
    }
}

if (! function_exists('wirechat_create_filesystem_sandbox')) {
    function wirechat_create_filesystem_sandbox(): string
    {
        $sandbox = sys_get_temp_dir().'/wirechat-command-sandbox-'.bin2hex(random_bytes(8));

        foreach ([
            'app' => app_path(),
            'bootstrap' => base_path('bootstrap'),
            'config' => config_path(),
            'database' => database_path(),
            'lang' => lang_path(),
            'public' => public_path(),
        ] as $directory => $source) {
            $target = $sandbox.'/'.$directory;

            if (is_dir($source)) {
                wirechat_copy_directory_with_retry($source, $target);
            } else {
                File::ensureDirectoryExists($target);
            }
        }

        File::ensureDirectoryExists($sandbox.'/storage');

        wirechat_sanitize_filesystem_sandbox($sandbox);

        app()->useAppPath($sandbox.'/app');
        app()->useBootstrapPath($sandbox.'/bootstrap');
        app()->useConfigPath($sandbox.'/config');
        app()->useDatabasePath($sandbox.'/database');
        app()->useLangPath($sandbox.'/lang');
        app()->usePublicPath($sandbox.'/public');
        app()->useStoragePath($sandbox.'/storage');

        foreach ([
            'bootstrap/cache',
            'storage/app/public',
            'storage/framework/cache/data',
            'storage/framework/sessions',
            'storage/framework/testing',
            'storage/framework/views',
            'storage/logs',
            'lang/vendor',
            'public/js/wirechat',
        ] as $directory) {
            File::ensureDirectoryExists($sandbox.'/'.$directory);
        }

        config()->set('filesystems.disks.public.root', storage_path('app/public'));
        config()->set('filesystems.links', [
            public_path('storage') => storage_path('app/public'),
        ]);

        wirechat_repoint_publish_paths();

        app()->terminating(static function () use ($sandbox): void {
            wirechat_remove_path($sandbox);
        });

        return $sandbox;
    }
}

if (! function_exists('wirechat_sanitize_filesystem_sandbox')) {
    function wirechat_sanitize_filesystem_sandbox(string $sandbox): void
    {
        wirechat_remove_path($sandbox.'/app/Providers/Wirechat');
        wirechat_remove_path($sandbox.'/config/wirechat.php');
        wirechat_remove_path($sandbox.'/lang/vendor/wirechat');
        wirechat_remove_path($sandbox.'/public/storage');
        wirechat_remove_path($sandbox.'/public/js/wirechat');
        wirechat_remove_path($sandbox.'/public/sw.js');

        foreach (glob($sandbox.'/database/migrations/*wirechat*') ?: [] as $path) {
            wirechat_remove_path($path);
        }

        File::ensureDirectoryExists($sandbox.'/bootstrap');
        File::put($sandbox.'/bootstrap/providers.php', "<?php\n\nreturn [\n];\n");

        $appConfigPath = $sandbox.'/config/app.php';
        if (is_file($appConfigPath)) {
            $appConfig = file_get_contents($appConfigPath);

            if ($appConfig !== false) {
                $appConfig = preg_replace(
                    "/^\s*App\\\\Providers\\\\Wirechat\\\\[A-Za-z0-9_]+::class,\R/m",
                    '',
                    $appConfig
                ) ?? $appConfig;

                file_put_contents($appConfigPath, $appConfig);
            }
        }
    }
}

if (! function_exists('wirechat_repoint_publish_paths')) {
    function wirechat_repoint_publish_paths(): void
    {
        $packageRoot = dirname(__DIR__, 2);
        $provider = WirechatServiceProvider::class;

        ServiceProvider::$publishes[$provider] = [
            $packageRoot.'/resources/views' => resource_path('views/vendor/wirechat'),
            $packageRoot.'/lang' => lang_path('vendor/wirechat'),
            $packageRoot.'/config/wirechat.php' => config_path('wirechat.php'),
            $packageRoot.'/database/migrations' => database_path('migrations'),
            $packageRoot.'/stubs/upgradeMorphColumns.stub' => database_path('migrations/'.date('Y_m_d_His').'_upgrade_wirechat_morph_columns.php'),
            $packageRoot.'/stubs/add_participant_id_to_messages_table.stub' => database_path('migrations/'.date('Y_m_d_His').'_add_participant_id_to_messages_table.php'),
        ];

        ServiceProvider::$publishGroups['wirechat-views'] = [
            $packageRoot.'/resources/views' => resource_path('views/vendor/wirechat'),
        ];
        ServiceProvider::$publishGroups['wirechat-translations'] = [
            $packageRoot.'/lang' => lang_path('vendor/wirechat'),
        ];
        ServiceProvider::$publishGroups['wirechat-config'] = [
            $packageRoot.'/config/wirechat.php' => config_path('wirechat.php'),
        ];
        ServiceProvider::$publishGroups['wirechat-migrations'] = [
            $packageRoot.'/database/migrations' => database_path('migrations'),
        ];
        ServiceProvider::$publishGroups['wirechat-update-morphs-migration'] = [
            $packageRoot.'/stubs/upgradeMorphColumns.stub' => database_path('migrations/'.date('Y_m_d_His').'_upgrade_wirechat_morph_columns.php'),
        ];
        ServiceProvider::$publishGroups['wirechat-upgrade-0.4'] = [
            $packageRoot.'/stubs/add_participant_id_to_messages_table.stub' => database_path('migrations/'.date('Y_m_d_His').'_add_participant_id_to_messages_table.php'),
        ];
    }
}
