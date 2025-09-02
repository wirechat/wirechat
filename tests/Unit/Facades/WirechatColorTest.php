<?php

use Wirechat\Wirechat\Support\Color;

test('it returns default colors after boot', function () {

    $colors = [
        'primary' => Color::Blue,
        'danger' => Color::Red,
        'success' => Color::Green,
        'warning' => Color::Amber,
        'info' => Color::Blue,
        'gray' => Color::Zinc,
    ];

    expect(\Wirechat\Wirechat\Facades\WirechatColor::all())->toBe($colors);

});

test('it returns blue as primary color', function () {

    expect(\Wirechat\Wirechat\Facades\WirechatColor::primary())->toBe(Color::Blue['500']);

});

test('panel color can override default color when color is updated in panel', function () {

    testPanelProvider()->colors([
        'primary' => Color::Red,
    ]);

    expect(\Wirechat\Wirechat\Facades\WirechatColor::primary())->toBe(Color::Red['500']);

});
