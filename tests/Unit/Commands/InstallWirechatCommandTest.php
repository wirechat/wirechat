<?php

beforeEach(function () {
    $this->sandbox = wirechat_create_filesystem_sandbox();

    $this->filePath = app_path('Providers/Wirechat/ChatsPanelProvider.php');
    $this->cssPath = resource_path('css/app.css');
    $this->isLaravel11OrHigherWithBootstrapFile = version_compare(App::version(), '11.0', '>=') &&
        /** @phpstan-ignore-next-line */
        file_exists(App::getBootstrapProvidersPath());

    // Save a copy of the original providers file
    $this->providersFile = $this->isLaravel11OrHigherWithBootstrapFile
        ? App::getBootstrapProvidersPath()
        : config_path('app.php');

    $this->originalProvidersContent = File::exists($this->providersFile)
        ? File::get($this->providersFile)
        : null;
    $this->originalCssContent = File::exists($this->cssPath)
        ? File::get($this->cssPath)
        : null;

    // Delete config
    if (File::exists(config_path('wirechat.php'))) {
        File::delete(config_path('wirechat.php'));
    }
});

afterEach(function () {
    // Delete the generated panel provider file
    if (File::exists($this->filePath)) {
        File::delete($this->filePath);
    }

    // Restore the original providers file contents
    if ($this->originalProvidersContent !== null) {
        File::ensureDirectoryExists(dirname($this->providersFile));
        File::put($this->providersFile, $this->originalProvidersContent);
    } elseif (File::exists($this->providersFile)) {
        File::delete($this->providersFile);
    }

    if ($this->originalCssContent !== null) {
        File::ensureDirectoryExists(dirname($this->cssPath));
        File::put($this->cssPath, $this->originalCssContent);
    } elseif (File::exists($this->cssPath)) {
        File::delete($this->cssPath);
    }
});

test('wirechat translations are published', function () {
    // Define the expected path
    $expectedPath = lang_path('vendor/wirechat/en/validation.php');

    // Run the artisan command to publish translations
    $this->artisan('vendor:publish', ['--tag' => 'wirechat-translations', '--force' => true]);

    // Assert that the translation file exists after publishing
    expect(file_exists($expectedPath))->toBeTrue();
});

test('it creates panel', function () {
    // Define the expected path
    $expectedPath = app_path('Providers/Wirechat/ChatsPanelProvider.php');

    // Ensure the file does not exist before publishing
    if (file_exists($expectedPath)) {
        // unlink($expectedPath); // Remove it if it already exists
        Illuminate\Support\Facades\File::delete($expectedPath);
    }

    $this->artisan('wirechat:install');

    // Assert that the translation file exists after publishing
    expect(file_exists($expectedPath))->toBeTrue();
});

test('it publishes config', function () {
    // Define the expected path
    $expectedPath = config_path('wirechat.php');

    // Ensure the file does not exist before publishing
    if (file_exists($expectedPath)) {
        unlink($expectedPath); // Remove it if it already exists
    }

    $this->artisan('wirechat:install');

    // Assert that the translation file exists after publishing
    expect(file_exists($expectedPath))->toBeTrue();
});

test('it creates storage symlink', function () {
    $linkPath = public_path('storage');

    // Clean up before test
    if (file_exists($linkPath)) {
        unlink($linkPath);
    }

    // Run the command (assuming it calls storage:link inside)
    $this->artisan('wirechat:install');

    // Assert that the symlink exists
    expect(is_link($linkPath))->toBeTrue();

    // Optionally assert that the symlink points to storage/app/public
    expect(readlink($linkPath))->toBe(storage_path('app/public'));
});

test('it adds the free package stylesheet import to app css', function () {
    File::ensureDirectoryExists(dirname($this->cssPath));
    File::put($this->cssPath, "@import 'tailwindcss';\n\n@source '../views/**/*.blade.php';\n");

    $this->artisan('wirechat:install');

    expect(File::get($this->cssPath))->toContain(implode(PHP_EOL, [
        "@import 'tailwindcss';",
        "@import '../../vendor/wirechat/wirechat/resources/css/app.css';",
        '',
        "@source '../views/**/*.blade.php';",
    ]));
});

test('it does not add the free package stylesheet import when pro stylesheet is configured', function () {
    File::ensureDirectoryExists(dirname($this->cssPath));
    File::put($this->cssPath, implode(PHP_EOL, [
        "@import 'tailwindcss';",
        "@import '../../vendor/wirechat/wirechat-pro/resources/css/app.css';",
        '',
    ]));

    $this->artisan('wirechat:install');

    expect(File::get($this->cssPath))
        ->toContain('vendor/wirechat/wirechat-pro/resources/css/app.css')
        ->not->toContain('vendor/wirechat/wirechat/resources/css/app.css');
});
