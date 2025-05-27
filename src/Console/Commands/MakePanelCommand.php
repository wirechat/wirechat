<?php

namespace Namu\WireChat\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use function Laravel\Prompts\text;
use function Laravel\Prompts\confirm;

class MakePanelCommand extends Command
{
    protected $signature = 'make:wirechat-panel {id?}';
    protected $description = 'Create a new WireChat panel provider';

    public function handle()
    {
        // Get the panel ID from argument or prompt
        $id = $this->argument('id') ?? text(
            label: 'What is the panel ID?',
            placeholder: 'e.g., admin',
            required: true,
            validate: fn (string $value) => match (true) {
                preg_match('/^[a-zA-Z].*/', $value) !== false => null,
                default => 'The ID must start with a letter.',
            },
        );

        // Generate class name and file path
        $id = Str::kebab($id); // Ensure ID is kebab-case (e.g., 'admin')
        $className = Str::studly($id) . 'PanelProvider';
        $namespace = 'App\\Providers\\WireChat';
        $path = app_path("Providers/WireChat/{$className}.php");

        // Check if file exists and ask to overwrite
        if (file_exists($path)) {
            $overwrite = confirm(
                label: "The file {$path} already exists. Do you want to overwrite it?",
                default: false
            );

            if (! $overwrite) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // Read the stub file
        $stubPath = dirname(__DIR__, 3) . '/stubs/PanelProvider.stub';
        if (! file_exists($stubPath)) {
            $this->error("Stub file not found at: $stubPath");
            return 1;
        }
        $stub = file_get_contents($stubPath);

        // Replace placeholders
        $stub = str_replace('{{ namespace }}', $namespace, $stub);
        $stub = str_replace('{{ className }}', $className, $stub);
        $stub = str_replace('{{ panelId }}', $id, $stub);

        // Ensure the directory exists
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Write the file
        file_put_contents($path, $stub);

        // Register the provider automatically
        $this->registerProvider($namespace, $className);

        // Output success message
        $this->info("Panel provider [{$path}] created successfully.");
        return 0;
    }

    protected function registerProvider(string $namespace, string $className)
    {
        $providerClass = "{$namespace}\\{$className}";
        $isLaravel11OrHigher = version_compare(app()->version(), '11.0', '>=') && file_exists(base_path('bootstrap/providers.php'));

        if ($isLaravel11OrHigher) {
            $bootstrapPath = base_path('bootstrap/providers.php');
            $content = file_get_contents($bootstrapPath);

            if (! Str::contains($content, $providerClass)) {
                $content = str_replace(
                    "return [\n",
                    "return [\n    {$providerClass}::class,\n",
                    $content
                );
                file_put_contents($bootstrapPath, $content);
                $this->info("Registered provider [{$providerClass}] in bootstrap/providers.php.");
            }
        } else {
            $appConfigPath = config_path('app.php');
            $appConfig = file_get_contents($appConfigPath);

            if (! Str::contains($appConfig, $providerClass)) {
                $appConfig = str_replace(
                    "'providers' => [\n",
                    "'providers' => [\n        {$providerClass}::class,\n",
                    $appConfig
                );
                file_put_contents($appConfigPath, $appConfig);
                $this->info("Registered provider [{$providerClass}] in config/app.php.");
            }
        }
    }
}
