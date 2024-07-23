<?php

namespace Orchestra\DuskUpdater\Concerns;

use Exception;
use InvalidArgumentException;
use Orchestra\DuskUpdater\OperatingSystem;
use Symfony\Component\Process\Process;

/**
 * @copyright Originally created by Jonas Staudenmeir: https://github.com/staudenmeir/dusk-updater
 */
trait DetectsChromeVersion
{
    /**
     * The legacy versions for the ChromeDriver.
     *
     * @var array<int, string>
     */
    protected array $legacyVersions = [
        43 => '2.20',
        44 => '2.20',
        45 => '2.20',
        46 => '2.21',
        47 => '2.21',
        48 => '2.21',
        49 => '2.22',
        50 => '2.22',
        51 => '2.23',
        52 => '2.24',
        53 => '2.26',
        54 => '2.27',
        55 => '2.28',
        56 => '2.29',
        57 => '2.29',
        58 => '2.31',
        59 => '2.32',
        60 => '2.33',
        61 => '2.34',
        62 => '2.35',
        63 => '2.36',
        64 => '2.37',
        65 => '2.38',
        66 => '2.40',
        67 => '2.41',
        68 => '2.42',
        69 => '2.44',
    ];

    /**
     * Find selected ChromeDriver version URL.
     *
     * @throws \Exception
     */
    protected function findVersionUrl(?string $version): string
    {
        if (! $version) {
            return $this->latestVersion();
        }

        if (! ctype_digit((string) $version)) {
            return $version;
        }

        $version = (int) $version;

        if ($version < 70) {
            return $this->legacyVersions[$version];
        } elseif ($version < 115) {
            return $this->fetchChromeVersionFromUrl($version);
        }

        $milestones = $this->resolveChromeVersionsPerMilestone();

        return $milestones['milestones'][$version]['version']
            ?? throw new Exception('Could not determine the ChromeDriver version.');
    }

    /**
     * Get the latest stable ChromeDriver version.
     *
     * @throws \Exception
     */
    protected function latestVersion(): string
    {
        $versions = json_decode($this->fetchUrl('https://googlechromelabs.github.io/chrome-for-testing/last-known-good-versions-with-downloads.json'), true);

        return $versions['channels']['Stable']['version']
            ?? throw new Exception('Could not get the latest ChromeDriver version.');
    }

    /**
     * Detect the installed Chrome/Chromium version.
     *
     * @return array<string, mixed>
     */
    protected function installedChromeVersion(string $operatingSystem, ?string $directory = null): array
    {
        if ($directory) {
            if ($operatingSystem === 'win') {
                throw new InvalidArgumentException('Chrome version cannot be detected in custom installation path on Windows.');
            }

            $commands = [$directory.' --version'];
        } else {
            $commands = OperatingSystem::chromeVersionCommands($operatingSystem);
        }

        foreach ($commands as $command) {
            $process = Process::fromShellCommandline($command);

            $process->run();

            if ($process->getExitCode() != 0) {
                continue;
            }

            preg_match('/(\d+)\.(\d+)\.(\d+)(\.\d+)?/', $process->getOutput(), $matches);

            if (! isset($matches[1])) {
                continue;
            }

            $semver = implode('.', [$matches[1], $matches[2], $matches[3]]);

            return [
                'full' => $matches[0],
                'semver' => $semver,
                'major' => (int) $matches[1],
                'minor' => (int) $matches[2],
                'patch' => (int) $matches[3],
            ];
        }

        throw new InvalidArgumentException('Chrome version could not be detected. Please submit an issue: https://github.com/orchestral/dusk-updater');
    }

    /**
     * Detect the installed ChromeDriver version.
     *
     * @return array<string, mixed>|null
     */
    protected function installedChromeDriverVersion(string $operatingSystem, string $directory): ?array
    {
        $filename = OperatingSystem::chromeDriverBinary($operatingSystem);

        if (! file_exists($directory.$filename)) {
            return [
                'full' => null,
                'semver' => null,
                'major' => null,
                'minor' => null,
                'patch' => null,
            ];
        }

        $command = $directory.$filename.' --version';
        $process = Process::fromShellCommandline($command);

        $process->run();

        if ($process->getExitCode() == 0) {
            preg_match('/ChromeDriver\s(\d+)\.(\d+)\.(\d+)(\.\d+)?\s[\w\D]+/', $process->getOutput(), $matches);

            if (isset($matches[1])) {
                $semver = implode('.', [$matches[1], $matches[2], $matches[3]]);

                return [
                    'full' => $semver,
                    'semver' => $semver,
                    'major' => (int) $matches[1],
                    'minor' => (int) $matches[2],
                    'patch' => (int) $matches[3],
                ];
            }
        }

        throw new InvalidArgumentException('ChromeDriver version could not be detected. Please submit an issue: https://github.com/orchestral/dusk-updater');
    }

    /**
     * Get the chrome version from URL.
     */
    protected function fetchChromeVersionFromUrl(int $version): string
    {
        return trim((string) $this->fetchUrl(
            sprintf('https://chromedriver.storage.googleapis.com/LATEST_RELEASE_%d', $version)
        ));
    }

    /**
     * Get the chrome versions per milestone.
     */
    protected function resolveChromeVersionsPerMilestone(): array
    {
        return json_decode(
            $this->fetchUrl('https://googlechromelabs.github.io/chrome-for-testing/latest-versions-per-milestone-with-downloads.json'), true
        );
    }

    /**
     * Get contents from URL.
     *
     * @throws \Exception
     */
    abstract protected function fetchUrl(string $url): string;
}
