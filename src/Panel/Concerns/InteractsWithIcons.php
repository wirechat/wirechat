<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;

trait InteractsWithIcons
{
    /**
     * Normalize + validate an icon value that may be:
     * - string: Blade component name (e.g. "wirechat::icons.plus")
     * - Htmlable: trusted raw SVG/HTML (e.g. new HtmlString('<svg ...>'))
     * - Closure: evaluated later by $this->evaluate()
     */
    protected function assertValidIcon(string|Htmlable|Closure|null $icon, string $for = 'icon'): void
    {
        if ($icon === null) {
            return;
        }

        // Closures are evaluated later; validate after evaluation too.
        if ($icon instanceof Closure) {
            return;
        }

        if ($icon instanceof Htmlable) {
            return;
        }

        // At this point, $icon must be a string (after excluding null, Closure, and Htmlable)
        $trim = ltrim($icon);

        // Block raw markup passed as a plain string.
        if ($trim !== '' && str_starts_with($trim, '<')) {
            throw new InvalidArgumentException(
                "Wirechat: {$for} string must be a Blade component name (e.g. \"wirechat::icons.plus\"). ".
                'To pass raw SVG/HTML, wrap it in HtmlString/Htmlable.'
            );
        }
    }

    /**
     * Resolve an icon value that may be Closure|string|Htmlable|null, and validate it.
     */
    protected function resolveIcon(string|Htmlable|Closure|null $icon, string $for = 'icon'): string|Htmlable|null
    {
        /** @var string|Htmlable|null $resolved */
        $resolved = $this->evaluate($icon);

        $this->assertValidIcon($resolved, $for);

        return $resolved;
    }
}
