<?php

declare(strict_types=1);

namespace Pest\Arch\Expectations;

use Pest\Arch\GroupArchExpectation;
use Pest\Arch\SingleArchExpectation;
use Pest\Expectation;

/**
 * @internal
 */
final class ToBeUsedIn
{
    /**
     * Creates an "ToBeUsedIn" expectation.
     *
     * @param  array<int, string>|string  $targets
     */
    public static function make(Expectation $expectation, array|string $targets): GroupArchExpectation
    {
        assert(is_string($expectation->value) || is_array($expectation->value));

        $targets = is_string($targets) ? [$targets] : $targets;

        return GroupArchExpectation::fromExpectations(
            $expectation,
            array_map(
                static fn ($target): SingleArchExpectation => ToUse::make(expect($target), $expectation->value), $targets
            )
        );
    }
}
