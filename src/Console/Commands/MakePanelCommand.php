<?php

namespace Wirechat\Wirechat\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Wirechat\Wirechat\Facades\Wirechat;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class MakePanelCommand extends Command
{
    protected $signature = 'make:wirechat-panel {id?}';

    protected $description = 'Create a new Wirechat panel provider';

    protected bool $isLaravel11OrHigherWithBootstrapFile;

    public string $stubPath;

    public function __construct()
    {
        parent::__construct();
        $this->stubPath = dirname(__DIR__, 3).'/stubs/PanelProvider.stub';
    }

    public function handle()
    {
        $id = $this->argument('id') ?? text(
            label: 'What is the panel ID?',
            placeholder: 'e.g., admin',
            required: true
        );

        $validator = Validator::make(['id' => $id], [
            'id' => [
                'required',
                'max:255',
                'regex:/^[a-zA-Z][a-zA-Z0-9_]*$/',
            ],
        ], [
            'id.regex' => 'The ID must start with a letter and contain only letters, numbers, or underscores.',
        ]);

        if ($validator->fails()) {
            $this->error($validator->errors()->first('id'));

            return 1;
        }

        $id = Str::kebab($id);
        $className = Str::studly($id).'PanelProvider';
        $namespace = 'App\\Providers\\Wirechat';
        $path = app_path("Providers/Wirechat/{$className}.php");
        $displayPath = Str::after($path, base_path().DIRECTORY_SEPARATOR);

        if (file_exists($path)) {
            $overwrite = confirm(
                label: "The file [{$displayPath}] already exists. Do you want to overwrite it?",
                default: false
            );

            if (! $overwrite) {
                $this->info('Operation cancelled.');

                return 0;
            }
        }

        if (! file_exists($this->stubPath)) {
            $this->error("Stub file not found at: $this->stubPath");

            return 1;
        }
        $stub = file_get_contents($this->stubPath);

        $panels = Wirechat::panels();
        $hasDefault = collect($panels)->contains(fn ($panel) => $panel->isDefault());
        $defaultFlag = $hasDefault ? '' : '->default()';

        $stub = str_replace('{{ namespace }}', $namespace, $stub);
        $stub = str_replace('{{ className }}', $className, $stub);
        $stub = str_replace('{{ panelId }}', $id, $stub);
        $stub = str_replace('{{ defaultFlag }}', $defaultFlag, $stub);

        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, $stub);

        $this->isLaravel11OrHigherWithBootstrapFile = version_compare(App::version(), '11.0', '>=') &&
            file_exists(App::getBootstrapProvidersPath());

        try {
            $this->registerProvider($namespace, $className);
        } catch (\Exception $e) {
            if (file_exists($path)) {
                unlink($path);
            }
            $this->error("Failed to register provider: {$e->getMessage()}");

            return 1;
        }

        if ($this->isLaravel11OrHigherWithBootstrapFile) {
            $this->warn("We’ve tried to add [{$displayPath}] into your [bootstrap/providers.php] file. If you encounter errors accessing your panel, the automatic registration may have failed. In that case, please add it manually to the returned array.");
        } else {
            $this->warn("We’ve attempted to register [{$displayPath}] in your [config/app.php] providers list. If you run into issues, the change might not have applied correctly — you can always insert it yourself in the 'providers' array.");
        }

        return 0;
    }

    protected function registerProvider(string $namespace, string $className): void
    {
        $providerClass = "{$namespace}\\{$className}";

        if ($this->isLaravel11OrHigherWithBootstrapFile) {
            $bootstrapPath = App::getBootstrapProvidersPath();
            ServiceProvider::addProviderToBootstrapFile($providerClass, $bootstrapPath);
        } else {
            // Skip config modification in test environment

            $appConfigPath = config_path('app.php');
            $appConfig = file_get_contents($appConfigPath);

            // Use a more generic anchor to avoid referencing RouteServiceProvider
            $anchor = "'providers' => [";
            if (! Str::contains($appConfig, $providerClass)) {
                file_put_contents(
                    $appConfigPath,
                    str_replace(
                        $anchor,
                        $anchor.PHP_EOL."        {$providerClass}::class,",
                        $appConfig
                    )
                );
            }
        }
        $this->info("Wirechat panel [{$providerClass}] created successfully.");
    }
}
