<?php

use Illuminate\Support\Facades\File;

use function Pest\Laravel\artisan;

beforeEach(function () {
    $this->id = 'testpanel';
    $this->className = 'TestpanelPanelProvider';
    $this->namespace = 'App\\Providers\\WireChat';
    $this->filePath = app_path("Providers/WireChat/{$this->className}.php");

    $this->stubPath = dirname(__DIR__, 3).'/stubs/PanelProvider.stub';
    File::ensureDirectoryExists(dirname($this->stubPath));
    File::put($this->stubPath, <<<'PHP'
<?php

namespace {{ namespace }};

class {{ className }}
{
    public string $id = '{{ panelId }}';
}
PHP);
});

afterEach(function () {
    File::delete($this->filePath);
    File::delete($this->stubPath);
});

it('creates a new wirechat panel provider using a fresh ID', function () {
    if (File::exists($this->filePath)) {
        File::delete($this->filePath);
    }

    artisan('make:wirechat-panel', ['id' => $this->id])
        ->assertExitCode(0)
        ->expectsOutput("Panel provider [{$this->filePath}] created successfully.");

    expect(File::exists($this->filePath))->toBeTrue()
        ->and(File::get($this->filePath))
        ->toContain("namespace {$this->namespace}")
        ->toContain("class {$this->className}")
        ->toContain("public string \$id = '{$this->id}'");
});

it('does not overwrite existing file if user cancels', function () {
    File::ensureDirectoryExists(dirname($this->filePath));
    File::put($this->filePath, 'OLD');

    $this->artisan('make:wirechat-panel')
        ->expectsQuestion('What is the panel ID?', $this->id)
        ->expectsConfirmation("The file {$this->filePath} already exists. Do you want to overwrite it?", 'no')
        ->expectsOutput('Operation cancelled.')
        ->assertExitCode(0);

});

it('overwrites existing file when user confirms', function () {
    File::ensureDirectoryExists(dirname($this->filePath));
    File::put($this->filePath, 'OLD');

    artisan('make:wirechat-panel')
        ->expectsQuestion('What is the panel ID?', $this->id)
        ->expectsConfirmation("The file {$this->filePath} already exists. Do you want to overwrite it?", 'yes')
        ->expectsOutput("Panel provider [{$this->filePath}] created successfully.")
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
    File::delete($this->filePath);

    $this->artisan('make:wirechat-panel')
        ->expectsQuestion('What is the panel ID?', '1234bad')
        ->expectsOutput('The ID must start with a letter and contain only letters, numbers, or underscores.')
        ->assertExitCode(1);

    expect(File::exists(app_path('Providers/WireChat/1234badPanelProvider.php')))->toBeFalse();
    File::delete($this->filePath);

});

it('shows error when stub file is missing', function () {
    File::delete($this->stubPath);

    $this->artisan('make:wirechat-panel')
        ->expectsQuestion('What is the panel ID?', 'ValidPanel')
        ->expectsOutput("Stub file not found at: {$this->stubPath}")
        ->assertExitCode(1);
});
