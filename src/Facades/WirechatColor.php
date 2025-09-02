<?php

namespace Wirechat\Wirechat\Facades;

use Illuminate\Support\Facades\Facade;
use Wirechat\Wirechat\Services\ColorService;

/**
 * @method static string|null primary(int $shade = 500)
 * @method static string|null danger(int $shade = 500)
 * @method static string|null info(int $shade = 500)
 * @method static string|null success(int $shade = 500)
 * @method static string|null warning(int $shade = 500)
 * @method static string|null gray(int $shade = 500)
 * @method static array|null palette(string $name)
 * @method static void register(array $map)
 */
class WirechatColor extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ColorService::class;
    }
}
