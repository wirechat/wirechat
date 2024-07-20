<?php

declare(strict_types=1);

namespace Pest\Laravel;

use Illuminate\Foundation\Testing\Concerns\InteractsWithDeprecationHandling;
use Illuminate\Foundation\Testing\Concerns\InteractsWithExceptionHandling;
use Pest\Contracts\Plugins\HandlesArguments;
use Pest\Plugins\Concerns\HandleArguments;
use Pest\TestSuite;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
final class Plugin implements HandlesArguments
{
    use HandleArguments;

    public function handleArguments(array $arguments): array
    {
        if ($this->hasArgument('--with-exception-handling', $arguments)) {
            $arguments = $this->popArgument('--with-exception-handling', $arguments);

            $interactsWithExceptionHandling = function (TestCase $testCase): bool {
                return function_exists('trait_uses_recursive') && trait_uses_recursive($testCase, InteractsWithExceptionHandling::class);
            };

            uses()->beforeEach(function () use ($interactsWithExceptionHandling) {
                /** @var TestCase $this */
                if ($interactsWithExceptionHandling($this)) {
                    /** @var TestCase&InteractsWithExceptionHandling $this */
                    $this->withExceptionHandling();
                }
            })->in(TestSuite::getInstance()->rootPath);
        }

        if ($this->hasArgument('--without-exception-handling', $arguments)) {
            $arguments = $this->popArgument('--without-exception-handling', $arguments);

            $interactsWithExceptionHandling = function (TestCase $testCase): bool {
                return function_exists('trait_uses_recursive') && trait_uses_recursive($testCase, InteractsWithExceptionHandling::class);
            };

            uses()->beforeEach(function () use ($interactsWithExceptionHandling) {
                /** @var TestCase $this */
                if ($interactsWithExceptionHandling($this)) {
                    /** @var TestCase&InteractsWithExceptionHandling $this */
                    $this->withoutExceptionHandling();
                }
            })->in(TestSuite::getInstance()->rootPath);
        }

        if ($this->hasArgument('--with-deprecation-handling', $arguments)) {
            $arguments = $this->popArgument('--with-deprecation-handling', $arguments);

            $interactsWithDeprecationHandling = function (TestCase $testCase): bool {
                return function_exists('trait_uses_recursive') && trait_uses_recursive($testCase, InteractsWithDeprecationHandling::class);
            };

            uses()->beforeEach(function () use ($interactsWithDeprecationHandling) {
                /** @var TestCase $this */
                if ($interactsWithDeprecationHandling($this)) {
                    /** @var TestCase&InteractsWithDeprecationHandling $this */
                    $this->withDeprecationHandling();
                }
            })->in(TestSuite::getInstance()->rootPath);
        }

        if ($this->hasArgument('--without-deprecation-handling', $arguments)) {
            $arguments = $this->popArgument('--without-deprecation-handling', $arguments);

            $interactsWithDeprecationHandling = function (TestCase $testCase): bool {
                return function_exists('trait_uses_recursive') && trait_uses_recursive($testCase, InteractsWithDeprecationHandling::class);
            };

            uses()->beforeEach(function () use ($interactsWithDeprecationHandling) {
                /** @var TestCase $this */
                if ($interactsWithDeprecationHandling($this)) {
                    /** @var TestCase&InteractsWithDeprecationHandling $this */
                    $this->withoutDeprecationHandling();
                }
            })->in(TestSuite::getInstance()->rootPath);
        }

        return $arguments;
    }
}
