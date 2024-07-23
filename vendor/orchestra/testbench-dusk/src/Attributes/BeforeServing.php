<?php

namespace Orchestra\Testbench\Dusk\Attributes;

use Attribute;
use Orchestra\Testbench\Contracts\Attributes\Invokable as InvokableContract;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
final class BeforeServing implements InvokableContract
{
    /**
     * Construct a new attribute.
     *
     * @param  string  $method
     */
    public function __construct(public string $method)
    {
        //
    }

    /**
     * Handle the attribute.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return string
     */
    public function __invoke($app): string
    {
        return $this->method;
    }
}
