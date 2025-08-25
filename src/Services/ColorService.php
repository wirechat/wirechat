<?php

namespace Namu\WireChat\Services;

use Namu\WireChat\Facades\WireChat;

class ColorService
{
    // Base colors registered globally (not tied to any panel)
    protected array $colors = [];

    /**
     * Register global default colors.
     */
    public function register(array $map): void
    {
        foreach ($map as $name => $palette) {
            if (is_array($palette)) {
                $this->colors[$name] = $palette;
            }
        }
    }

    /**
     * Get a single color by name + shade (default: 500).
     */
    public function get(string $name, int $shade = 500): ?string
    {
        $colors = $this->all();

        $palette = $colors[$name] ?? null;

        if (! $palette) {
            return null;
        }

        return $palette[$shade] ?? ($palette[500] ?? null);
    }

    /**
     * Get the full palette for a single color.
     */
    public function palette(string $name): ?array
    {
        return $this->all()[$name] ?? null;
    }

    /**
     * Return all available colors:
     * - colors (global)
     * - merged with current panel overrides
     */
    public function all(): array
    {
        $panel = WireChat::currentPanel();

        if ($panel) {
            // panel colors override colors
            return array_merge($this->colors, $panel->getColors());
        }

        return $this->colors;
    }

    // === Convenience shortcuts for common colors ===

    /** Get the "primary" color. */
    public function primary(int $shade = 500): ?string
    {
        return $this->get('primary', $shade);
    }

    /** Get the "danger" color. */
    public function danger(int $shade = 500): ?string
    {
        return $this->get('danger', $shade);
    }

    /** Get the "warning" color. */
    public function warning(int $shade = 500): ?string
    {
        return $this->get('warning', $shade);
    }
}
