<?php

namespace Orchestra\Testbench\Dusk\Foundation\Console;

use Orchestra\Testbench\Foundation\Console\DropSqliteDbCommand as Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'package:drop-sqlite-db', description: 'Drop sqlite database file')]
class DropSqliteDbCommand extends Command
{
    //
}
