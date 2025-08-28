<?php

namespace Namu\WireChat\Panel\Concerns;

use Closure;
use Namu\WireChat\Http\Resources\WireChatUserResource;

trait HasUsersSearch
{
    protected ?Closure $searchCallback = null;

    public function searchUsersUsing(Closure $callback): static
    {
        $this->searchCallback = $callback;

        return $this;
    }

    /**
     * Search for chatable users and return a standardized JSON resource collection.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function searchUsers(?string $needle)
    {
        return WireChatUserResource::collection(
            $this->runSearchCallback($needle)
        );
    }

    /**
     * Execute the search logic and return a collection of models.
     *
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
        // @phpstan-ignore-next-line
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
