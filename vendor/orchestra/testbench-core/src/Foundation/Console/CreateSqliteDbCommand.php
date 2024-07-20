<?php

namespace Orchestra\Testbench\Foundation\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

use function Illuminate\Filesystem\join_paths;

#[AsCommand(name: 'package:create-sqlite-db', description: 'Create sqlite database file')]
class CreateSqliteDbCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'package:create-sqlite-db {--force : Overwrite the database file}';

    /**
     * Execute the console command.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $filesystem
     * @return int
     */
    public function handle(Filesystem $filesystem)
    {
        $databasePath = $this->laravel->databasePath();

        /** @var bool $force */
        $force = $this->option('force');

        $from = $filesystem->exists(join_paths($databasePath, 'database.sqlite.example'))
            ? join_paths($databasePath, 'database.sqlite.example')
            : (string) realpath(join_paths(__DIR__, 'stubs', 'database.sqlite.example'));

        $to = join_paths($databasePath, 'database.sqlite');

        (new Actions\GeneratesFile(
            filesystem: $filesystem,
            components: $this->components,
            force: $force,
            workingPath: $this->laravel->basePath(),
        ))->handle($from, $to);

        return Command::SUCCESS;
    }
}
