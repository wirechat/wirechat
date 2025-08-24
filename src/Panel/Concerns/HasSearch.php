<?php

namespace Namu\WireChat\Panel\Concerns;

use Closure;
use Namu\WireChat\Http\Resources\ChatableResource;

trait HasSearch
{
    protected ?Closure $searchCallback = null;

    public function searchUsing(Closure $callback): static
    {
        $this->searchCallback = $callback;
        return $this;
    }

    /**
     * Search for chatable users and return a standardized JSON resource collection.
     *
     * @param  string|null  $needle
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function search(?string $needle)
    {
        return ChatableResource::collection(
            $this->runSearchCallback($needle)
        );
    }

    /**
     * Execute the search logic and return a collection of models.
     *
     * @param  string|null  $needle
     * @return \Illuminate\Support\Collection
     */
    protected function runSearchCallback(?string $needle)
    {
        if (blank($needle)) {
            return collect();
        }

        if ($this->searchCallback) {
            // Expect the callback to return a Collection of models
            return ($this->searchCallback)($needle);
        }

        // Default search: limit 20 results and return a collection
        return \App\Models\User::query()
            ->where(function ($q) use ($needle) {
                foreach ($this->getSearchableAttributes() as $field) {
                    $q->orWhere($field, 'like', "%{$needle}%");
                }
            })
            ->limit(20)
            ->get();
    }
}
