<?php

use Illuminate\Support\Facades\File;
use Wirechat\Wirechat\Console\Commands\MakePanelCommand;

use function Pest\Laravel\artisan;

beforeEach(function () {
    $this->id = 'testPanel';
    $this->className = 'TestPanelPanelProvider';
    $this->namespace = 'App\\Providers\\Wirechat';
    $this->filePath = app_path("Providers/Wirechat/{$this->className}.php");
    $this->displayPath = Str::after($this->filePath, base_path().DIRECTORY_SEPARATOR);
    $this->providerClass = 'App\\Providers\\Wirechat\\'.$this->className;
    $this->stubPath = dirname(__DIR__, 3).'/stubs/PanelProvider.stub';

    $this->isLaravel11OrHigherWithBootstrapFile = version_compare(App::version(), '11.0', '>=') &&
        /** @phpstan-ignore-next-line */
        file_exists(App::getBootstrapProvidersPath());
});

afterEach(function () {
    // Delete the generated panel provider file
    if (File::exists($this->filePath)) {
        File::delete($this->filePath);
    }

    // Clean up the providers file based on Laravel version
    if ($this->isLaravel11OrHigherWithBootstrapFile) {
        $providersFile = App::getBootstrapProvidersPath();
    } else {
        $providersFile = config_path('app.php');
    }

    if (File::exists($providersFile)) {
        $content = File::get($providersFile);
        // Remove the provider class entry, handling comma and whitespace carefully
        $pattern = "/\s*".preg_quote("App\\Providers\\Wirechat\\{$this->className}::class")."\s*,?\s*\n/";
        $content = preg_replace($pattern, '', $content);
        // Fix trailing comma before closing array, ensuring valid syntax
        $content = preg_replace("/,\s*\n\s*\];/", "\n];", $content);
        // Ensure no empty array is left with just whitespace
        $content = preg_replace("/'providers' => \[\s*\]/", "'providers' => []", $content);
        File::put($providersFile, $content);
    }
});

it('creates a new wirechat panel provider using a fresh ID', function () {
    $artisan = $this->artisan('make:wirechat-panel', ['id' => $this->id])
        ->assertExitCode(0)
        ->expectsOutput("Wirechat panel [$this->providerClass] created successfully.");

    if ($this->isLaravel11OrHigherWithBootstrapFile) {
        $artisan->expectsOutput("We’ve tried to add [{$this->displayPath}] into your [bootstrap/providers.php] file. If you encounter errors accessing your panel, the automatic registration may have failed. In that case, please add it manually to the returned array.");
    } else {
        $artisan->expectsOutput("We’ve attempted to register [{$this->displayPath}] in your [config/app.php] providers list. If you run into issues, the change might not have applied correctly — you can always insert it yourself in the 'providers' array.");
    }
});

it('does not overwrite existing file if user cancels', function () {
    File::ensureDirectoryExists(dirname($this->filePath));
    File::put($this->filePath, 'OLD');

    $this->artisan('make:wirechat-panel')
        ->expectsQuestion('What is the panel ID?', $this->id)
        ->expectsConfirmation("The file [$this->displayPath] already exists. Do you want to overwrite it?", 'no')
        ->expectsOutput('Operation cancelled.')
        ->assertExitCode(0);
});

it('overwrites existing file when user confirms', function () {
    File::ensureDirectoryExists(dirname($this->filePath));
    File::put($this->filePath, 'OLD');

    artisan('make:wirechat-panel')
        ->expectsQuestion('What is the panel ID?', $this->id)
        ->expectsConfirmation("The file [$this->displayPath] already exists. Do you want to overwrite it?", 'yes')
        ->expectsOutput("Wirechat panel [{$this->providerClass}] created successfully.")
        ->assertExitCode(0);

    expect(File::get($this->filePath))->not->toBe('OLD');
    expect(File::get($this->filePath))->toContain("class {$this->className}");
});

it('shows validation error for invalid ID', function () {
    $this->artisan('make:wirechat-panel')
        ->expectsQuestion('What is the panel ID?', '1234bad')
        ->expectsOutput('The ID must start with a letter and contain only letters, numbers, or underscores.')
        ->assertExitCode(1);

    expect(File::exists(app_path('Providers/Wirechat/1234badPanelProvider.php')))->toBeFalse();
});

it('shows error when stub file is missing', function () {
    File::delete($this->filePath);
    $command = new MakePanelCommand;

    // Set a fake, non-existent stub path
    $fakeStubPath = '/non/existent/path/PanelProvider.stub';
    $command->stubPath = $fakeStubPath;

    // Bind the mocked command to the container
    app()->bind(MakePanelCommand::class, function () use ($command) {
        return $command;
    });

    $this->artisan('make:wirechat-panel', ['id' => $this->id])
        ->expectsOutput("Stub file not found at: {$fakeStubPath}")
        ->assertExitCode(1);
});
