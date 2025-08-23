<?php

namespace Namu\WireChat\Panel\Concerns;

use Closure;
use Illuminate\Support\Collection;

trait HasChatablesSearch
{
    /**
     * The callback used for searching chatable users.
     */
    protected ?Closure $searchCallback = null;

    /**
     * Define a custom search callback.
     */
    public function searchChatablesUsing(Closure $callback): static
    {
        $this->searchCallback = $callback;

        return $this;
    }

    /**
     * Search for chatable users.
     */
    public function searchChatables(?string $query): Collection
    {
        return $this->runChatablesSearchCallback($query);
    }

    /**
     * Execute the search callback or default search logic.
     */
    protected function runChatablesSearchCallback(?string $query): Collection
    {
        if (blank($query)) {
            return collect();
        }

        if ($this->searchCallback) {
            return ($this->searchCallback)($query);
        }

        // Default search using User model and existing getSearchableFields()
        return \App\Models\User::query()
            ->where(function ($q) use ($query) {
                foreach ($this->getSearchableFields() as $field) {
                    $q->orWhere($field, 'like', "%{$query}%");
                }
            })
            ->get();
    }
}
