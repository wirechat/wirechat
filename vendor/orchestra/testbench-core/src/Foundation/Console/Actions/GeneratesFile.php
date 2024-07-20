<?php

namespace Orchestra\Testbench\Foundation\Console\Actions;

use Illuminate\Console\View\Components\Factory as ComponentsFactory;
use Illuminate\Filesystem\Filesystem;

use function Laravel\Prompts\confirm;

class GeneratesFile extends Action
{
    /**
     * Construct a new action instance.
     *
     * @param  \Illuminate\Filesystem\Filesystem  $filesystem
     * @param  \Illuminate\Console\View\Components\Factory|null  $components
     * @param  bool  $force
     * @param  string|null  $workingPath
     * @param  bool  $confirmation
     */
    public function __construct(
        public Filesystem $filesystem,
        public ?ComponentsFactory $components = null,
        public bool $force = false,
        ?string $workingPath = null,
        public bool $confirmation = false
    ) {
        $this->workingPath = $workingPath;
    }

    /**
     * Handle the action.
     *
     * @param  string  $from
     * @param  string  $to
     * @return void
     */
    public function handle(string $from, string $to): void
    {
        if (! $this->filesystem->exists($from)) {
            $this->components?->twoColumnDetail(
                sprintf('Source file [%s] doesn\'t exists', $this->pathLocation($from)),
                '<fg=yellow;options=bold>SKIPPED</>'
            );

            return;
        }

        $location = $this->pathLocation($to);

        if (! $this->force && $this->filesystem->exists($to)) {
            $this->components?->twoColumnDetail(
                sprintf('File [%s] already exists', $location),
                '<fg=yellow;options=bold>SKIPPED</>'
            );

            return;
        }

        if ($this->confirmation === true && confirm(sprintf('Generate [%s] file?', $location)) === false) {
            return;
        }

        $this->filesystem->copy($from, $to);

        $this->components?->task(
            sprintf('File [%s] generated', $location)
        );
    }
}
