<?php

namespace Wirechat\Wirechat\Services;

use Wirechat\Wirechat\Facades\Wirechat;

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
        $colors = $this->colors;
        $panel = Wirechat::currentPanel();

        if ($panel) {
            foreach ($panel->getColors() as $name => $palette) {
                if (isset($colors[$name]) && is_array($colors[$name]) && is_array($palette)) {
                    $colors[$name] = array_replace($colors[$name], $palette);

                    continue;
                }

                $colors[$name] = $palette;
            }
        }

        return $colors;
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

    /** Get the "success" color. */
    public function success(int $shade = 500): ?string
    {
        return $this->get('success', $shade);
    }

    /** Get the "info" color. */
    public function info(int $shade = 500): ?string
    {
        return $this->get('info', $shade);
    }

    /** Get the "warning" color. */
    public function warning(int $shade = 500): ?string
    {
        return $this->get('warning', $shade);
    }

    /** Get the "gray" color. */
    public function gray(int $shade = 500): ?string
    {
        return $this->get('gray', $shade);
    }

    /** Get the "dark" color. */
    public function dark(int $shade = 500): ?string
    {
        return $this->get('dark', $shade);
    }
}
