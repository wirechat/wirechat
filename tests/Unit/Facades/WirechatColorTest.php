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
        'dark' => Color::Zinc,
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

test('panel color can extend a default palette without losing missing shades', function () {
    $customDark = 'oklch(0.18 0.01 285.9)';

    testPanelProvider()->colors([
        'dark' => [
            900 => $customDark,
        ],
    ]);

    expect(\Wirechat\Wirechat\Facades\WirechatColor::palette('dark'))
        ->toBe(array_replace(Color::Zinc, [900 => $customDark]));
});
