<?php

namespace Namu\WireChat\Panel\Concerns;

use Closure;

/**
 * Trait HasSearchableFields
 *
 * Defines which fields are searchable for models (e.g., User).
 *
 * @method mixed evaluate(mixed $value)
 */
trait HasSearchableAttributes
{
    /**
     * Fields used to search models.
     *
     * @var array<string>|Closure|null
     */
    protected array|Closure|null $searchableAttributes = null;

    /**
     * Set the model’s searchable fields.
     */
    public function searchableAttributes(array|Closure $attributes): static
    {
        $this->searchableAttributes = $attributes;

        return $this;
    }

    /**
     * Get the model’s searchable fields.
     */
    public function getSearchableAttributes(): array
    {
        return (array) $this->evaluate($this->searchableAttributes ?? ['name']);
    }
}
