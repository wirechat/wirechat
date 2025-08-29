<?php

use Illuminate\Support\Facades\File;
use Namu\WireChat\Console\Commands\MakePanelCommand;

use function Pest\Laravel\artisan;

beforeEach(function () {
    $this->id = 'testPanel';
    $this->className = 'TestPanelPanelProvider';
    $this->namespace = 'App\\Providers\\WireChat';
    $this->filePath = app_path("Providers/WireChat/{$this->className}.php");
    $this->displayPath = Str::after($this->filePath, base_path().DIRECTORY_SEPARATOR);
    $this->providerClass = 'App\\Providers\\WireChat\\'.$this->className;
    // Just reference the existing stub; no need to overwrite it
    $this->stubPath = dirname(__DIR__, 3).'/stubs/PanelProvider.stub';

    $this->isLaravel11OrHigherWithBootstrapFile = version_compare(App::version(), '11.0', '>=') &&
        /** @phpstan-ignore-next-line */
        file_exists(App::getBootstrapProvidersPath());
    File::delete($this->filePath);

});

afterEach(function () {});

it('creates a new wirechat panel provider using a fresh ID', function () {

    // Ensure file doesn't exist before test

    $id = 'temp';
    $className = 'TempPanelProvider';
    $providerClass = 'App\\Providers\\WireChat\\'.$className;
    $filePath = app_path("Providers/WireChat/{$className}.php");
    $displayPath = Str::after($filePath, base_path().DIRECTORY_SEPARATOR);

    $artisan = artisan('make:wirechat-panel', ['id' => $id])
        ->assertExitCode(0)
        ->expectsOutput("WireChat panel [$providerClass] created successfully.");

    if ($this->isLaravel11OrHigherWithBootstrapFile) {
        $artisan->expectsOutput("We’ve tried to add [{$displayPath}] into your [bootstrap/providers.php] file.If you encounter errors accessing your panel, the automatic registration may have failed. In that case, please add it manually to the returned array.");
    } else {
        $artisan->expectsOutput("We’ve attempted to register [{$displayPath}] in your [config/app.php] providers list. If you run into issues, the change might not have applied correctly — you can always insert it yourself in the 'providers' array.");
    }

    expect(file_exists($filePath))->toBeTrue()
        ->and(File::get($filePath))
        ->toContain("namespace {$this->namespace}")
        ->toContain("class {$className}");

    if (file_exists($filePath)) {
        File::delete($filePath); // Use unlink instead of File::delete for consistency
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
        ->expectsOutput("WireChat panel [{$this->providerClass}] created successfully.")
        ->assertExitCode(0);

    expect(File::get($this->filePath))->not->toBe('OLD');
    expect(File::get($this->filePath))->toContain("class {$this->className}");
});

// it('shows validation error for invalid ID', function () {
//
//    $this->artisan('make:wirechat-panel')
//        ->expectsQuestion('What is the panel ID?', '1234bad')
//        ->expectsOutput('The ID must start with a letter.')
//        ->assertExitCode(1);
//    File::delete($this->filePath);
// });

it('shows validation error for invalid ID', function () {

    $this->artisan('make:wirechat-panel')
        ->expectsQuestion('What is the panel ID?', '1234bad')
        ->expectsOutput('The ID must start with a letter and contain only letters, numbers, or underscores.')
        ->assertExitCode(1);

    expect(File::exists(app_path('Providers/WireChat/1234badPanelProvider.php')))->toBeFalse();
    File::delete($this->filePath);

});

it('shows error when stub file is missing', function () {
    File::delete($this->filePath);
    // Mock panel doesnt exists  facade for stub path
    //    File::shouldReceive('exists')
    //        ->with($this->filePath)
    //        ->andReturn(false);

    // Set stubPath to match command's default
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
