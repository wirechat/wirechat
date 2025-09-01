<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;

trait HasColors
{
    protected array $colors = [];

    /**
     * @param  array<string, array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string} | string> | Closure  $colors
     */
    public function colors(array|Closure $colors): static
    {
        $this->colors[] = $colors;

        return $this;
    }

    /**
     * @return array<string, array{50: string, 100: string, 200: string, 300: string, 400: string, 500: string, 600: string, 700: string, 800: string, 900: string, 950: string} | string>
     */
    public function getColors(): array
    {
        $colors = [];

        foreach ($this->colors as $set) {
            $set = $this->evaluate($set);

            foreach ($set as $name => $palette) {
                // Preserve numeric keys (50, 100, etc.) while merging
                $colors[$name] = ($colors[$name] ?? []) + $palette;
            }
        }

        return $colors;
    }
}
