<?php

namespace Wirechat\Wirechat\Panel\Concerns;

use Closure;
use Wirechat\Wirechat\Enums\ColorTone;

trait HasColorTone
{
    protected ColorTone|string|Closure $colorTone = ColorTone::Soft;

    public function colorTone(ColorTone|string|Closure $tone = ColorTone::Soft): static
    {
        $this->colorTone = $tone;

        return $this;
    }

    public function getColorTone(): ColorTone
    {
        $tone = $this->evaluate($this->colorTone);

        if ($tone instanceof ColorTone) {
            return $tone;
        }

        if (is_string($tone)) {
            return ColorTone::tryFrom($tone) ?? ColorTone::Soft;
        }

        return ColorTone::Soft;
    }

    public function hasSoftColorTone(): bool
    {
        return $this->getColorTone() === ColorTone::Soft;
    }

    public function hasSolidColorTone(): bool
    {
        return $this->getColorTone() === ColorTone::Solid;
    }
}
