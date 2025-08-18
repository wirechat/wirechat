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
trait HasSearchableFields
{
    /**
     * Fields used to search models.
     *
     * @var array<string>|Closure|null
     */
    protected array|Closure|null $searchableFields = null;

    /**
     * Set the model’s searchable fields.
     */
    public function searchableFields(array|Closure $fields): static
    {
        $this->searchableFields = $fields;
        return $this;
    }

    /**
     * Get the model’s searchable fields.
     */
    public function getSearchableFields(): array
    {
        return (array) $this->evaluate($this->searchableFields ?? ['name']);
    }
}
