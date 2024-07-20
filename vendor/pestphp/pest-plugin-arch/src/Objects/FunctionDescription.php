<?php

declare(strict_types=1);

namespace Pest\Arch\Objects;

use PHPUnit\Architecture\Asserts\Dependencies\Elements\ObjectUses;
use PHPUnit\Architecture\Elements\ObjectDescription;
use ReflectionFunction;

/**
 * @internal
 */
final class FunctionDescription extends ObjectDescription // @phpstan-ignore-line
{
    /**
     * {@inheritDoc}
     */
    public static function make(string $path): self
    {
        $description = new self();

        $description->path = $path;

        /** @var class-string<mixed> $path */
        $description->name = $path;
        $description->uses = new ObjectUses([]);
        // $description->reflectionClass = new ReflectionFunction($path);

        return $description;
    }
}
