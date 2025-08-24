<?php

namespace Namu\WireChat\Panel\Concerns;

use Closure;
use Illuminate\Support\Collection;

trait HasSearch
{
    /**
     * Holds the custom search callback if defined.
     *
     * When set, this callback will be executed instead of the default
     * search logic. The callback should accept the search term (`string|null`)
     * and return a `Collection` of results.
     */
    protected ?Closure $searchCallback = null;

    /**
     * Define a custom search callback.
     *
     * This allows consumers of the trait to fully override the default
     * search behavior. The callback must return a `Collection` of
     * chatable users or models.
     *
     * @param  \Closure  $callback  The custom search handler.
     * @return static
     */
    public function searchUsing(Closure $callback): static
    {
        $this->searchCallback = $callback;

        return $this;
    }

    /**
     * Search for chatable users.
     *
     * If a custom callback has been defined via {@see searchUsing()},
     * it will be executed. Otherwise, the trait falls back to the
     * default search logic provided in {@see runSearchCallback()}.
     *
     * @param  string|null  $needle  The search term (nullable).
     * @return \Illuminate\Support\Collection
     */
    public function search(?string $needle): Collection
    {
        return $this->runSearchCallback($needle);
    }

    /**
     * Execute the search callback or fall back to the default search logic.
     *
     * - Returns an empty collection if the search term is blank.
     * - Executes the user-defined callback if provided.
     * - Otherwise, defaults to querying the `User` model against
     *   the fields returned by {@see getSearchableAttributes()}.
     *
     * @param  string|null  $needle  The search term (nullable).
     * @return \Illuminate\Support\Collection
     */
    protected function runSearchCallback(?string $needle): Collection
    {
        if (blank($needle)) {
            return collect();
        }

        if ($this->searchCallback) {
            return ($this->searchCallback)($needle);
        }

        // Default search using the User model and the defined searchable attributes.
        return \App\Models\User::query()
            ->where(function ($q) use ($needle) {
                foreach ($this->getSearchableAttributes() as $field) {
                    $q->orWhere($field, 'like', "%{$needle}%");
                }
            })
            ->get();
    }
}
