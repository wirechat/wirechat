<?php

namespace Wirechat\Wirechat\Livewire\Concerns;

use Livewire\Attributes\Locked;

trait InteractsWithUI
{
    #[Locked]
    public string $class = '';

    /**
     * @var array<int|string, mixed>|string|null
     */
    #[Locked]
    public array|string|null $styles = null;

    public function getUiClass(): string
    {
        return trim($this->class);
    }

    public function getUiStyles(): ?string
    {
        $declarations = [];

        foreach ($this->normalizeUiStyles($this->styles) as $declaration) {
            $declarations[] = rtrim($declaration, ';');
        }

        if ($declarations === []) {
            return null;
        }

        return implode('; ', $declarations).';';
    }

    /**
     * @param  array<int|string, mixed>|string|null  $styles
     * @return array<int, string>
     */
    protected function normalizeUiStyles(array|string|null $styles): array
    {
        if ($styles === null || $styles === '') {
            return [];
        }

        if (is_string($styles)) {
            $styles = [$styles];
        }

        $declarations = [];

        foreach ($styles as $property => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            if (is_int($property)) {
                $declarations[] = trim((string) $value);

                continue;
            }

            $declarations[] = trim((string) $property).': '.trim((string) $value);
        }

        return $declarations;
    }
}
