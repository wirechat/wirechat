<?php

use Orchestra\Testbench\Dusk\DuskServer;

use function Illuminate\Filesystem\join_paths;

// Simple server script, which pulls in large part from the framework.
// It has been adapted so we can reconstruct the application to the
// required state on each request (based on the calling Test Class)

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.

$uri = urldecode(
    (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// =========================================================================================
// Laravel routes through public/index.php but we need to build the application differently.
// =========================================================================================

define('LARAVEL_START', microtime(true));

require is_file(__DIR__.'/../vendor/autoload.php')
    ? __DIR__.'/../vendor/autoload.php'
    : __DIR__.'/../../../autoload.php';

// Identify the calling test class based on the content of the stash,
// use it to retrieve the built application as specificed in all
// the setup methods for the test class
$orchestraServer = new DuskServer($_SERVER['SERVER_NAME'], $_SERVER['SERVER_PORT']);

/** @var class-string<\Orchestra\Testbench\Dusk\TestCase> $originatingTestClass */
$originatingTestClass = $orchestraServer->getStash('class');

$app = (new $originatingTestClass('laravel'))->createServingApplicationForDuskServer($orchestraServer);

// Emulation of mod_rewrite, but we use the applications set base path
if ($uri !== '/' && file_exists(join_paths($app->basePath(), 'public', $uri))) {
    return false;
}

// Process the request as per a normal Laravel request
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

$response->send();

$kernel->terminate($request, $response);
