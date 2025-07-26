<?php

namespace Namu\WireChat\Panel\Concerns;

use Closure;

/**
 * Trait HasParticipantableSearchColumns
 *
 * Defines the searchable columns for participantable models (e.g., User).
 *
 * @method mixed evaluate(mixed $value)
 */
trait HasParticipantableSearchColumns
{
    /**
     * Columns used to search participantable models.
     *
     * @var array<string>|Closure|null
     */
    protected array|Closure|null $participantableSearchColumns = null;

    /**
     * Set the participantable model search columns.
     */
    public function participantableSearchColumns(array|Closure $columns): static
    {
        $this->participantableSearchColumns = $columns;
        return $this;
    }

    /**
     * Get the participantable model search columns.
     */
    public function getParticipantableSearchColumns(): array
    {
        return (array) $this->evaluate($this->participantableSearchColumns ?? ['name']);
    }
}
