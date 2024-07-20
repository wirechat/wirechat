<?php

namespace Orchestra\Testbench\Attributes;

use Attribute;
use Closure;
use Orchestra\Testbench\Contracts\Attributes\Invokable as InvokableContract;
use Orchestra\Testbench\Foundation\Env;
use Orchestra\Testbench\Foundation\UndefinedValue;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class WithEnv implements InvokableContract
{
    /**
     * Construct a new attribute.
     *
     * @param  string  $key
     * @param  string|null  $value
     */
    public function __construct(
        public string $key,
        public ?string $value
    ) {
        //
    }

    /**
     * Handle the attribute.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return (\Closure():(void))|null
     */
    public function __invoke($app): ?Closure
    {
        $key = $this->key;
        $value = Env::get($key, new UndefinedValue());

        Env::set($key, $this->value ?? '(null)');

        return static function () use ($key, $value) {
            if ($value instanceof UndefinedValue) {
                Env::forget($key);
            } else {
                Env::set($key, Env::encode($value));
            }
        };
    }
}
