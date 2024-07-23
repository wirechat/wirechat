<?php

namespace Orchestra\Testbench\Dusk\Foundation\Console;

use Orchestra\Testbench\Foundation\Console\CreateSqliteDbCommand as Command;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'package:create-sqlite-db', description: 'Create sqlite database file')]
class CreateSqliteDbCommand extends Command
{
    //
}
