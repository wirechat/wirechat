<?php

declare(strict_types=1);

namespace Pest\Laravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Pest\TestSuite;

use function Pest\testDirectory;

/**
 * @internal
 */
final class PestDatasetCommand extends Command
{
    /**
     * The Console Command name.
     *
     * @var string
     */
    protected $signature = 'pest:dataset {name : The name of the dataset}
                                         {--test-directory=tests : The name of the tests directory}';

    /**
     * The Console Command description.
     *
     * @var string
     */
    protected $description = 'Create a new dataset file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        /** @var string $testDirectory */
        $testDirectory = $this->option('test-directory');

        TestSuite::getInstance(base_path(), $testDirectory);

        /** @var string $name */
        $name = $this->argument('name');

        $relativePath = sprintf(testDirectory('Datasets/%s.php'), ucfirst($name));

        $target = base_path($relativePath);

        if (File::exists($target)) {
            $this->components->warn(sprintf('[%s] already exist', $target));

            return 1;
        }

        if (! File::exists(dirname($relativePath))) {
            File::makeDirectory(dirname($relativePath));
        }

        $contents = File::get(implode(DIRECTORY_SEPARATOR, [
            dirname(__DIR__, 3),
            'pest',
            'stubs',
            'Dataset.php',
        ]));

        $name = mb_strtolower($name);
        $contents = str_replace('{dataset_name}', $name, $contents);

        $element = Str::singular($name);
        $contents = str_replace('{dataset_element}', $element, $contents);
        File::put($target, str_replace('{dataset_name}', $name, $contents));
        $message = sprintf('[%s] created successfully.', $relativePath);

        $this->components->info($message);

        return 0;
    }
}
