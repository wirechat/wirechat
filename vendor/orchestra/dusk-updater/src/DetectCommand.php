<?php

namespace Orchestra\DuskUpdater;

use Composer\Semver\Comparator;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @copyright Originally created by Jonas Staudenmeir: https://github.com/staudenmeir/dusk-updater
 */
class DetectCommand extends Command
{
    /**
     * Configure the command options.
     */
    protected function configure(): void
    {
        $this->setName('detect')
            ->setDescription('Detect the installed Chrome/Chromium version.')
            ->addOption('chrome-dir', null, InputOption::VALUE_OPTIONAL, 'Detect the installed Chrome/Chromium version, optionally in a custom path')
            ->addOption('auto-update', null, InputOption::VALUE_NONE, 'Auto update ChromeDriver binary if outdated');

        parent::configure();
    }

    /**
     * Execute the command.
     *
     * @return int 0 if everything went fine, or an exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $chromeDirectory = $input->getOption('chrome-dir');
        $driverDirectory = $input->getOption('install-dir');
        $autoUpdate = $input->getOption('auto-update');

        $currentOS = OperatingSystem::id();

        $chromeVersions = $this->installedChromeVersion($currentOS, $chromeDirectory);
        $driverVersions = $this->installedChromeDriverVersion($currentOS, (string) $driverDirectory);

        $updated = Comparator::equalTo(
            isset($driverVersions['semver']) ? $driverVersions['semver'] : '',
            isset($chromeVersions['semver']) ? $chromeVersions['semver'] : ''
        );

        $io->block(sprintf('Running PHP %s on Platform [%s]', PHP_VERSION, $currentOS));

        $io->table(['Tool', 'Version'], [
            ['Chrome/Chromium', $chromeVersions['semver'] ?? '<fg=yellow>✖ N/A</>'],
            ['ChromeDriver', $driverVersions['semver'] ?? '<fg=yellow>✖ N/A</>'],
        ]);

        if (! $updated) {
            if (! $autoUpdate) {
                $io->caution('ChromeDriver is outdated!');
            }

            if ($autoUpdate || $io->confirm('Do you want to update ChromeDriver?')) {
                $this->updateChromeDriver($input, $output, $driverDirectory, $chromeVersions['major']);
            }
        }

        return self::SUCCESS;
    }

    /**
     * Update ChromeDriver.
     */
    protected function updateChromeDriver(InputInterface $input, OutputInterface $output, string $directory, int $version): int
    {
        /** @var \Symfony\Component\Console\Application $console */
        $console = $this->getApplication();

        $command = $console->find('update');

        $arguments = array_merge([
            'command' => 'update',
            'version' => $version,
            '--install-dir' => $directory,
        ], array_filter([
            '--proxy' => $input->getOption('proxy'),
            '--ssl-no-verify' => $input->getOption('ssl-no-verify'),
        ]));

        return $command->run(new ArrayInput($arguments), $output);
    }
}
