<?php

declare(strict_types=1);

namespace Pest\Laravel\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Console\PromptsForMissingInput;
use Illuminate\Support\Facades\File;
use Pest\Support\Str;
use Pest\TestSuite;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use function Laravel\Prompts\select;
use function Pest\testDirectory;

/**
 * @internal
 */
final class PestTestCommand extends Command implements PromptsForMissingInput
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'pest:test {name : The name of the file} {--unit : Create a unit test} {--dusk : Create a Dusk test} {--test-directory=tests : The name of the tests directory} {--force : Overwrite the existing test file with the same name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new test file';

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
        if (str_ends_with($name, '.php')) {
            $name = substr($name, 0, -4);
        }

        $type = ((bool) $this->option('unit')) ? 'Unit' : (((bool) $this->option('dusk')) ? 'Browser' : 'Feature');

        $relativePath = sprintf(
            testDirectory('%s/%s.php'),
            $type,
            ucfirst($name)
        );

        $target = base_path($relativePath);

        if (! File::isDirectory(dirname((string) $target))) {
            File::makeDirectory(dirname((string) $target), 0777, true, true);
        }

        if (File::exists($target) && ! (bool) $this->option('force')) {
            $this->components->warn(sprintf('[%s] already exist', $target));

            return 1;
        }

        $contents = File::get(implode(DIRECTORY_SEPARATOR, [
            dirname(__DIR__, 3),
            'pest',
            'stubs',
            sprintf('%s.php', $type),
        ]));

        $name = mb_strtolower($name);
        $name = Str::endsWith($name, 'test') ? mb_substr($name, 0, -4) : $name;

        File::put($target, str_replace('{name}', $name, $contents));
        $message = sprintf('[%s] created successfully.', $relativePath);

        $this->components->info($message);

        return 0;
    }

    protected function promptForMissingArgumentsUsing()
    {
        return [
            'name' => 'What should the test be named?',
        ];
    }

    /**
     * Interact further with the user if they were prompted for missing arguments.
     */
    protected function afterPromptingForMissingArguments(InputInterface $input, OutputInterface $output)
    {
        $type = select('Which type of test would you like?', [
            'feature' => 'Feature',
            'unit' => 'Unit',
            'dusk' => 'Dusk',
        ]);

        match ($type) {
            'feature' => null,
            'unit' => $input->setOption('unit', true),
            'dusk' => $input->setOption('dusk', true),
        };
    }
}
