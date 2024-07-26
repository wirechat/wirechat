<?php

namespace Christophrumpel\MissingLivewireAssertions;

use Livewire\Component;
use Livewire\Mechanisms\ComponentRegistry;

class ComponentName
{
    public static function resolve(Component|string $component): ?string
    {
        if (is_subclass_of($component, Component::class)) {
            $component = app(ComponentRegistry::class)->getName($component);
        }

        return $component;
    }
}
