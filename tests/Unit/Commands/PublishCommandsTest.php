<?php

test('wirechat translations are published', function () {
    // Define the expected path
    $expectedPath = lang_path('vendor/wirechat/en/validation.php');

    // Run the artisan command to publish translations
    $this->artisan('vendor:publish', ['--tag' => 'wirechat-translations', '--force' => true]);

    // Assert that the translation file exists after publishing
    expect(file_exists($expectedPath))->toBeTrue();
});
