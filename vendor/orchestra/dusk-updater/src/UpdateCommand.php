<?php

namespace Orchestra\DuskUpdater;

use Exception;
use RuntimeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

/**
 * @copyright Originally created by Jonas Staudenmeir: https://github.com/staudenmeir/dusk-updater
 */
class UpdateCommand extends Command
{
    /**
     * Configure the command options.
     */
    protected function configure(): void
    {
        $this->setName('update')
            ->setDescription('Install the ChromeDriver binary.')
            ->addArgument('version', InputArgument::OPTIONAL)
            ->addOption('all', null, InputOption::VALUE_NONE, 'Install a ChromeDriver binary for every OS');

        parent::configure();
    }

    /**
     * Execute the command.
     *
     * @return int 0 if everything went fine, or an exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $version = $this->version($input);
        $all = $input->getOption('all');
        $currentOS = OperatingSystem::id();

        foreach (OperatingSystem::all() as $operatingSystem) {
            if ($all || ($operatingSystem === $currentOS)) {
                $archive = $this->download($version, $operatingSystem);
                $binary = $this->extract($version, $archive);
                $this->rename($binary, $operatingSystem);
            }
        }

        $output->writeln(sprintf(
            '<info>ChromeDriver %s successfully installed for version %s.</info>', $all ? 'binaries' : 'binary', $version
        ));

        return self::SUCCESS;
    }

    /**
     * Get the desired ChromeDriver version.
     */
    protected function version(InputInterface $input): string
    {
        return $this->findVersionUrl($input->getArgument('version'));
    }

    /**
     * Download the ChromeDriver archive.
     *
     * @throws \RuntimeException
     */
    protected function download(string $version, string $operatingSystem): string
    {
        $url = $this->resolveChromeDriverDownloadUrl($version, $operatingSystem);

        try {
            download($url, $archive = $this->directory.'chromedriver.zip', $this->httpProxy, $this->withSslVerification);
        } catch (Exception $e) {
            throw new RuntimeException("Unable to retrieve ChromeDriver [{$version}].");
        }

        return $archive;
    }

    /**
     * Extract the ChromeDriver binary from the archive and delete the archive.
     *
     * @throws \RuntimeException
     */
    protected function extract(string $version, string $archive): string
    {
        if (\is_null($this->directory)) {
            throw new RuntimeException("Unable to extract {$archive} without --install-dir");
        }

        $zip = new ZipArchive();

        $zip->open($archive);

        $zip->extractTo($this->directory);

        $binary = $zip->getNameIndex(version_compare($version, '115.0', '<') ? 0 : 1);

        $zip->close();

        unlink($archive);

        return (string) $binary;
    }

    /**
     * Rename the ChromeDriver binary and make it executable.
     *
     * @throws \RuntimeException
     */
    protected function rename(string $binary, string $operatingSystem): void
    {
        if (\is_null($this->directory)) {
            throw new RuntimeException("Unable to rename {$binary} without --install-dir");
        }

        $newName = rename_chromedriver_binary($binary, $operatingSystem);

        rename($this->directory.$binary, $this->directory.$newName);

        chmod($this->directory.$newName, 0755);
    }
}
