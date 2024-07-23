<?php

namespace Orchestra\Testbench\Dusk\Foundation\Console;

use Orchestra\Testbench\Foundation\Console\PurgeSkeletonCommand as Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'package:purge-skeleton', description: 'Purge skeleton folder to original state')]
class PurgeSkeletonCommand extends Command
{
    //
}
