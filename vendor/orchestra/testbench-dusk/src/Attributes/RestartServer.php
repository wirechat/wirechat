<?php

namespace Orchestra\Testbench\Dusk\Attributes;

use Attribute;
use Closure;
use Orchestra\Testbench\Contracts\Attributes\Actionable as ActionableContract;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class RestartServer implements ActionableContract
{
    /**
     * Handle the attribute.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @param  \Closure(string, array<int, mixed>):void  $action
     */
    public function handle($app, Closure $action): void
    {
        \call_user_func($action, 'reloadServing', []);
    }
}
