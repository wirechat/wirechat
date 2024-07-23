<?php

namespace Orchestra\DuskUpdater;

use InvalidArgumentException;

class OperatingSystem
{
    /**
     * List of available Operating System platforms.
     *
     * @var array<string, array{slug: string, binary: string, commands: array<int, string>}>
     */
    protected static $platforms = [
        'linux' => [
            'slug' => 'linux64',
            'binary' => 'chromedriver-linux',
            'commands' => [
                '/usr/bin/google-chrome --version',
                '/usr/bin/chromium-browser --version',
                '/usr/bin/chromium --version',
                '/usr/bin/google-chrome-stable --version',
            ],
        ],
        'mac' => [
            'slug' => 'mac-x64',
            'binary' => 'chromedriver-mac',
            'commands' => [
                '/Applications/Google\ Chrome\ for\ Testing.app/Contents/MacOS/Google\ Chrome\ for\ Testing --version',
                '/Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome --version',
            ],
        ],
        'mac-intel' => [
            'slug' => 'mac-x64',
            'binary' => 'chromedriver-mac-intel',
            'commands' => [
                '/Applications/Google\ Chrome\ for\ Testing.app/Contents/MacOS/Google\ Chrome\ for\ Testing --version',
                '/Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome --version',
            ],
        ],
        'mac-arm' => [
            'slug' => 'mac-arm64',
            'binary' => 'chromedriver-mac-arm',
            'commands' => [
                '/Applications/Google\ Chrome\ for\ Testing.app/Contents/MacOS/Google\ Chrome\ for\ Testing --version',
                '/Applications/Google\ Chrome.app/Contents/MacOS/Google\ Chrome --version',
            ],
        ],
        'win' => [
            'slug' => 'win32',
            'binary' => 'chromedriver-win.exe',
            'commands' => [
                'reg query "HKEY_CURRENT_USER\Software\Google\Chrome\BLBeacon" /v version',
            ],
        ],
    ];

    /**
     * Resolve Chrome version commands.
     *
     * @return array<int, string>
     */
    public static function chromeVersionCommands(string $operatingSystem): array
    {
        $commands = static::$platforms[$operatingSystem]['commands'] ?? null;

        if (\is_null($commands)) {
            throw new InvalidArgumentException("Unable to find commands for Operating System [{$operatingSystem}]");
        }

        return $commands;
    }

    /**
     * Resolve ChromeDriver binary.
     */
    public static function chromeDriverBinary(string $operatingSystem): string
    {
        $binary = static::$platforms[$operatingSystem]['binary'] ?? null;

        if (\is_null($binary)) {
            throw new InvalidArgumentException("Unable to find ChromeDriver binary for Operating System [{$operatingSystem}]");
        }

        return $binary;
    }

    /**
     * Resolve ChromeDriver slug.
     *
     * @param  string|null  $version
     */
    public static function chromeDriverSlug(string $operatingSystem, $version = null): string
    {
        $slug = static::$platforms[$operatingSystem]['slug'] ?? null;

        if (\is_null($slug)) {
            throw new InvalidArgumentException("Unable to find ChromeDriver slug for Operating System [{$operatingSystem}]");
        }

        if (! \is_null($version) && version_compare($version, '115.0', '<')) {
            if ($slug === 'mac-arm64') {
                return version_compare($version, '106.0.5249', '<') ? 'mac64_m1' : 'mac_arm64';
            } elseif ($slug === 'mac-x64') {
                return 'mac64';
            }
        }

        return $slug;
    }

    /**
     * Returns all possible OS.
     *
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_keys(static::$platforms);
    }

    /**
     * Returns the current OS identifier.
     */
    public static function id(): string
    {
        if (static::onWindows()) {
            return 'win';
        } elseif (static::onMac()) {
            return static::macArchitectureId();
        }

        return 'linux';
    }

    /**
     * Determine if the operating system is Windows or Windows Subsystem for Linux.
     */
    public static function onWindows(): bool
    {
        if (\defined('PHP_OS_FAMILY')) {
            return PHP_OS_FAMILY === 'Windows';
        }

        return PHP_OS === 'WINNT' || mb_strpos(php_uname(), 'Microsoft') !== false;
    }

    /**
     * Determine if the operating system is macOS.
     */
    public static function onMac(): bool
    {
        if (\defined('PHP_OS_FAMILY')) {
            return PHP_OS_FAMILY === 'Darwin';
        }

        return PHP_OS === 'Darwin';
    }

    /**
     * Mac platform architecture ID.
     */
    public static function macArchitectureId(): string
    {
        switch (php_uname('m')) {
            case 'arm64':
                return 'mac-arm';
            case 'x86_64':
                return 'mac-intel';
            default:
                return 'mac';
        }
    }
}
