<?php

test('wirechat translations are published', function () {
    // Define the expected path
    $expectedPath = lang_path('vendor/wirechat/en/validation.php');

    // Ensure the file does not exist before publishing
    if (file_exists($expectedPath)) {
        unlink($expectedPath); // Remove it if it already exists
    }

    // Run the artisan command to publish translations
    $this->artisan('vendor:publish', ['--tag' => 'wirechat-translations']);

    // Assert that the translation file exists after publishing
    expect(file_exists($expectedPath))->toBeTrue();
});
